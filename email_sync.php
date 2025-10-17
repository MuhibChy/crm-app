<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Invalid request method'], 405);
    exit;
}

$action = sanitizeInput($_POST['action'] ?? '');

switch ($action) {
    case 'fetch_all':
        try {
            $accounts = getAllEmailAccounts();
            $totalFetched = 0;
            $errors = [];
            
            foreach ($accounts as $account) {
                $result = fetchEmailsFromAccount($account);
                if ($result['success']) {
                    $totalFetched += $result['count'];
                } else {
                    $errors[] = "Account {$account['email']}: {$result['error']}";
                }
            }
            
            if (empty($errors)) {
                jsonResponse([
                    'success' => true, 
                    'message' => "Fetched {$totalFetched} new emails",
                    'count' => $totalFetched
                ]);
            } else {
                jsonResponse([
                    'success' => false, 
                    'error' => 'Some accounts failed: ' . implode('; ', $errors),
                    'partial_count' => $totalFetched
                ]);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Sync failed: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'fetch_account':
        $accountId = validateInt($_POST['account_id'] ?? 0);
        if (!$accountId) {
            jsonResponse(['success' => false, 'error' => 'Invalid account ID'], 400);
            exit;
        }
        
        $account = getEmailAccountById($accountId);
        if (!$account) {
            jsonResponse(['success' => false, 'error' => 'Account not found'], 404);
            exit;
        }
        
        try {
            $result = fetchEmailsFromAccount($account);
            jsonResponse($result);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Fetch failed: ' . $e->getMessage()], 500);
        }
        break;
        
    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
}

function fetchEmailsFromAccount(array $account): array {
    if (!function_exists('imap_open')) {
        return ['success' => false, 'error' => 'IMAP extension not available'];
    }
    
    try {
        $host = $account['imap_host'];
        $port = $account['imap_port'];
        $ssl = $account['imap_ssl'] ? '/ssl' : '';
        $email = $account['email'];
        $password = $account['password'];
        
        $mailbox = "{{$host}:{$port}/imap{$ssl}}INBOX";
        
        // Set timeouts
        if (function_exists('imap_timeout')) {
            @imap_timeout(1, 10); // OPENTIMEOUT
            @imap_timeout(2, 10); // READTIMEOUT
            @imap_timeout(3, 10); // WRITETIMEOUT
        }
        
        $connection = @imap_open($mailbox, $email, $password);
        if (!$connection) {
            $errors = imap_errors() ?: ['Connection failed'];
            return ['success' => false, 'error' => implode('; ', $errors)];
        }
        
        // Get recent emails (last 50)
        $totalEmails = imap_num_msg($connection);
        $startMsg = max(1, $totalEmails - 49);
        $newEmailsCount = 0;
        
        for ($msgNum = $startMsg; $msgNum <= $totalEmails; $msgNum++) {
            $header = imap_headerinfo($connection, $msgNum);
            if (!$header) continue;
            
            // Check if email already exists
            $messageId = $header->message_id ?? '';
            if (emailExists($messageId)) {
                continue;
            }
            
            // Get email body
            $body = getEmailBody($connection, $msgNum);
            
            // Save email to database
            $emailData = [
                'subject' => $header->subject ?? 'No Subject',
                'sender' => $header->fromaddress ?? 'Unknown Sender',
                'recipient' => $header->toaddress ?? $email,
                'body' => $body,
                'status' => 'New',
                'priority' => determinePriority($header->subject ?? ''),
                'email_account_id' => $account['id'],
                'message_id' => $messageId,
                'created_at' => date('Y-m-d H:i:s', $header->udate ?? time())
            ];
            
            if (saveEmailToDatabase($emailData)) {
                $newEmailsCount++;
            }
        }
        
        imap_close($connection);
        
        return [
            'success' => true, 
            'count' => $newEmailsCount,
            'message' => "Fetched {$newEmailsCount} new emails from {$email}"
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getEmailBody($connection, int $msgNum): string {
    try {
        $structure = imap_fetchstructure($connection, $msgNum);
        
        if (!$structure) {
            return imap_fetchbody($connection, $msgNum, "1");
        }
        
        // Handle multipart messages
        if (isset($structure->parts) && count($structure->parts)) {
            for ($i = 0; $i < count($structure->parts); $i++) {
                $part = $structure->parts[$i];
                
                // Look for text/plain or text/html
                if ($part->subtype === 'PLAIN' || $part->subtype === 'HTML') {
                    $body = imap_fetchbody($connection, $msgNum, ($i + 1));
                    
                    // Decode if needed
                    if ($part->encoding === 3) { // BASE64
                        $body = base64_decode($body);
                    } elseif ($part->encoding === 4) { // QUOTED-PRINTABLE
                        $body = quoted_printable_decode($body);
                    }
                    
                    return $body;
                }
            }
        }
        
        // Single part message
        $body = imap_fetchbody($connection, $msgNum, "1");
        
        if ($structure->encoding === 3) { // BASE64
            $body = base64_decode($body);
        } elseif ($structure->encoding === 4) { // QUOTED-PRINTABLE
            $body = quoted_printable_decode($body);
        }
        
        return $body;
        
    } catch (Exception $e) {
        return "Error reading email body: " . $e->getMessage();
    }
}

function determinePriority(string $subject): string {
    $subject = strtolower($subject);
    
    if (strpos($subject, 'urgent') !== false || strpos($subject, 'asap') !== false) {
        return 'Urgent';
    } elseif (strpos($subject, 'important') !== false || strpos($subject, 'priority') !== false) {
        return 'High';
    } elseif (strpos($subject, 'fyi') !== false || strpos($subject, 'info') !== false) {
        return 'Low';
    }
    
    return 'Medium';
}

function emailExists(string $messageId): bool {
    if (empty($messageId)) return false;
    
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM emails WHERE message_id = ?");
        $stmt->bind_param('s', $messageId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        $db->close();
        
        return $count > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function saveEmailToDatabase(array $emailData): bool {
    try {
        $db = getDatabaseConnection();
        
        $stmt = $db->prepare("
            INSERT INTO emails (subject, sender, recipient, body, status, priority, email_account_id, message_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param('sssssssss', 
            $emailData['subject'],
            $emailData['sender'],
            $emailData['recipient'],
            $emailData['body'],
            $emailData['status'],
            $emailData['priority'],
            $emailData['email_account_id'],
            $emailData['message_id'],
            $emailData['created_at']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        $db->close();
        
        return $result;
    } catch (Throwable $e) {
        error_log("Error saving email to database: " . $e->getMessage());
        return false;
    }
}
?>
