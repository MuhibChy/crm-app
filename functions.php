<?php
require_once __DIR__ . '/config.php';

function getDatabaseConnection(): mysqli {
    $connection = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($connection->connect_errno) {
        throw new RuntimeException('Database connection failed: ' . $connection->connect_error);
    }
    $connection->set_charset('utf8mb4');
    return $connection;
}

function ensureEmailAccountsTable(): void {
    try {
        $db = getDatabaseConnection();
        $db->query(
            "CREATE TABLE IF NOT EXISTS email_accounts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                label VARCHAR(100) NOT NULL,
                imap_host VARCHAR(255) NOT NULL,
                smtp_host VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                imap_port INT DEFAULT 993,
                smtp_port INT DEFAULT 587,
                imap_ssl TINYINT(1) DEFAULT 1,
                smtp_tls TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $db->close();
    } catch (Throwable $e) {
        // swallow
    }
}

ensureEmailAccountsTable();

function ensureOutlookAccountsTable(): void {
    try {
        $db = getDatabaseConnection();
        $db->query(
            "CREATE TABLE IF NOT EXISTS outlook_accounts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                display_name VARCHAR(255) DEFAULT NULL,
                provider VARCHAR(50) DEFAULT 'microsoft',
                access_token TEXT NOT NULL,
                refresh_token TEXT NOT NULL,
                expires_at INT NOT NULL,
                scope TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $db->close();
    } catch (Throwable $e) { /* swallow */ }
}

ensureOutlookAccountsTable();

// Outlook Graph features are deprecated in favor of custom domain IMAP/SMTP.
// Keep the table for now but omit UI links.
function saveGraphTokens(array $profile, array $tokens): bool {
    try {
        $email = $profile['mail'] ?: ($profile['userPrincipalName'] ?? '');
        $display = $profile['displayName'] ?? '';
        $access = $tokens['access_token'] ?? '';
        $refresh = $tokens['refresh_token'] ?? '';
        $expiresAt = time() + (int)($tokens['expires_in'] ?? 3600) - 60;

        $db = getDatabaseConnection();
        // upsert by email
        $stmt = $db->prepare("SELECT id FROM outlook_accounts WHERE email=?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $existing = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($existing) {
            $stmt = $db->prepare("UPDATE outlook_accounts SET display_name=?, access_token=?, refresh_token=?, expires_at=? WHERE id=?");
            $stmt->bind_param('sssii', $display, $access, $refresh, $expiresAt, $existing['id']);
        } else {
            $stmt = $db->prepare("INSERT INTO outlook_accounts (email, display_name, access_token, refresh_token, expires_at) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sssii', $email, $display, $access, $refresh, $expiresAt);
        }
        $ok = $stmt->execute();
        $stmt->close();
        $db->close();
        return $ok === true;
    } catch (Throwable $e) { return false; }
}

function getGraphAccountById(int $id): ?array {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT * FROM outlook_accounts WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        $db->close();
        return $row ?: null;
    } catch (Throwable $e) { return null; }
}

function listGraphAccounts(): array {
    try {
        $db = getDatabaseConnection();
        $rows = [];
        $res = $db->query("SELECT * FROM outlook_accounts ORDER BY id DESC");
        if ($res) while ($row = $res->fetch_assoc()) $rows[] = $row;
        $db->close();
        return $rows;
    } catch (Throwable $e) { return []; }
}

function refreshGraphTokenIfNeeded(array &$account): bool {
    if (time() < (int)$account['expires_at'] - 30) return true;
    $refresh = $account['refresh_token'];
    $body = http_build_query([
        'client_id' => GRAPH_CLIENT_ID,
        'client_secret' => GRAPH_CLIENT_SECRET,
        'grant_type' => 'refresh_token',
        'refresh_token' => $refresh,
        'scope' => 'offline_access Mail.Read Mail.ReadWrite Mail.Send'
    ]);
    $url = 'https://login.microsoftonline.com/' . rawurlencode(GRAPH_TENANT) . '/oauth2/v2.0/token';
    $opts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => $body, 'timeout' => 15]];
    $raw = @file_get_contents($url, false, stream_context_create($opts));
    if ($raw === false) return false;
    $data = json_decode($raw, true);
    if (!isset($data['access_token'])) return false;
    $account['access_token'] = $data['access_token'];
    $account['refresh_token'] = $data['refresh_token'] ?? $account['refresh_token'];
    $account['expires_at'] = time() + (int)($data['expires_in'] ?? 3600) - 60;

    // persist
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("UPDATE outlook_accounts SET access_token=?, refresh_token=?, expires_at=? WHERE id=?");
        $stmt->bind_param('ssii', $account['access_token'], $account['refresh_token'], $account['expires_at'], $account['id']);
        $ok = $stmt->execute();
        $stmt->close();
        $db->close();
        return $ok === true;
    } catch (Throwable $e) { return false; }
}

function graphApi(string $accessToken, string $endpoint, array $opts = []): array {
    $url = 'https://graph.microsoft.com/v1.0' . $endpoint;
    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ];
    $context = [
        'http' => [
            'method' => $opts['method'] ?? 'GET',
            'header' => implode("\r\n", $headers) . "\r\n",
            'timeout' => 20,
        ]
    ];
    if (isset($opts['body'])) $context['http']['content'] = json_encode($opts['body']);
    $raw = @file_get_contents($url, false, stream_context_create($context));
    if ($raw === false) return ['error' => 'request_failed'];
    $data = json_decode($raw, true);
    return $data ?? [];
}

// IMAP list helpers for custom accounts
function imapListMessages(array $account, string $folder = 'INBOX', int $limit = 25): array {
    if (!function_exists('imap_open')) return [];
    $host = $account['imap_host'];
    $port = (int)($account['imap_port'] ?? 993);
    $ssl = (int)($account['imap_ssl'] ?? 1) === 1 ? '/ssl' : '';
    $mailbox = '{' . $host . ':' . $port . '/imap' . $ssl . '}' . $folder;
    $inbox = @imap_open($mailbox, $account['email'], $account['password'], 0, 1, []);
    if ($inbox === false) return [];
    $nums = imap_search($inbox, 'ALL') ?: [];
    rsort($nums);
    $msgs = [];
    foreach (array_slice($nums, 0, $limit) as $num) {
        $overviewList = imap_fetch_overview($inbox, (string)$num, 0);
        $o = $overviewList && isset($overviewList[0]) ? $overviewList[0] : null;
        if (!$o) continue;
        $msgs[] = [
            'num' => $num,
            'subject' => $o->subject ?? '(no subject)',
            'from' => $o->from ?? '',
            'date' => $o->date ?? '',
            'seen' => !empty($o->seen)
        ];
    }
    imap_close($inbox);
    return $msgs;
}

function imapGetMessage(array $account, string $folder, int $num): array {
    if (!function_exists('imap_open')) return [];
    $host = $account['imap_host'];
    $port = (int)($account['imap_port'] ?? 993);
    $ssl = (int)($account['imap_ssl'] ?? 1) === 1 ? '/ssl' : '';
    $mailbox = '{' . $host . ':' . $port . '/imap' . $ssl . '}' . $folder;
    $inbox = @imap_open($mailbox, $account['email'], $account['password'], 0, 1, []);
    if ($inbox === false) return [];
    $header = imap_headerinfo($inbox, $num);
    $body = imap_body($inbox, $num);
    imap_close($inbox);
    return [
        'subject' => isset($header->subject) ? (string)$header->subject : '(no subject)',
        'from' => isset($header->fromaddress) ? (string)$header->fromaddress : '',
        'date' => isset($header->date) ? (string)$header->date : '',
        'body' => $body ?: ''
    ];
}

function imapDeleteMessage(array $account, string $folder, int $num): bool {
    if (!function_exists('imap_open')) return false;
    $host = $account['imap_host'];
    $port = (int)($account['imap_port'] ?? 993);
    $ssl = (int)($account['imap_ssl'] ?? 1) === 1 ? '/ssl' : '';
    $mailbox = '{' . $host . ':' . $port . '/imap' . $ssl . '}' . $folder;
    $inbox = @imap_open($mailbox, $account['email'], $account['password']);
    if ($inbox === false) return false;
    imap_delete($inbox, (string)$num);
    imap_expunge($inbox);
    imap_close($inbox);
    return true;
}

function smtpLastError(): string {
    return $GLOBALS['SMTP_LAST_ERROR'] ?? '';
}

function smtpSetError(string $message): void {
    $GLOBALS['SMTP_LAST_ERROR'] = $message;
}

function smtpSendSimple(array $account, string $to, string $subject, string $bodyText): bool {
    $GLOBALS['SMTP_LAST_ERROR'] = '';

    $host = trim((string)($account['smtp_host'] ?? ''));
    $port = (int)($account['smtp_port'] ?? 587);
    $useTls = (int)($account['smtp_tls'] ?? 1) === 1; // STARTTLS on 587
    $username = (string)($account['email'] ?? '');
    $password = (string)($account['password'] ?? '');

    $from = $username;
    $fromName = (string)($account['label'] ?? $from);
    $heloHost = gethostname() ?: 'localhost';

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        smtpSetError('Invalid recipient email address');
        return false;
    }
    if ($host === '' || $username === '' || $password === '') {
        smtpSetError('SMTP configuration is incomplete');
        return false;
    }

    $timeout = 25;
    $cryptoContext = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ]
    ]);

    $transport = ($port === 465) ? 'ssl' : 'tcp'; // 465 = implicit TLS
    $remote = $transport . '://' . $host . ':' . $port;
    $errno = 0; $errstr = '';
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $cryptoContext);
    if (!$fp) {
        smtpSetError('Connect failed: ' . ($errstr ?: ('errno ' . $errno)));
        return false;
    }
    stream_set_timeout($fp, $timeout);

    $readResp = function() use ($fp): array {
        $data = '';
        $code = 0;
        while (!feof($fp)) {
            $line = fgets($fp, 2048);
            if ($line === false) break;
            $data .= $line;
            if (strlen($line) >= 3 && ctype_digit($line[0] . $line[1] . $line[2])) {
                $code = (int)substr($line, 0, 3);
                if (isset($line[3]) && $line[3] === ' ') break; // end of multi-line
            } else {
                // non-standard line; continue until a code+space line appears or EOF
            }
        }
        return ['code' => $code, 'text' => $data];
    };
    $writeCmd = function(string $cmd) use ($fp): void { fwrite($fp, $cmd . "\r\n"); };

    // Greet
    $greet = $readResp();
    if ($greet['code'] !== 220) { fclose($fp); smtpSetError('Server greeting failed: ' . trim($greet['text'])); return false; }

    // EHLO
    $writeCmd('EHLO ' . $heloHost);
    $ehlo = $readResp();
    if ($ehlo['code'] !== 250) { fclose($fp); smtpSetError('EHLO failed: ' . trim($ehlo['text'])); return false; }

    $ehloText = strtoupper($ehlo['text']);

    // STARTTLS for port 587 if requested and advertised
    if ($port === 587 && $useTls) {
        if (strpos($ehloText, 'STARTTLS') === false) {
            fclose($fp); smtpSetError('STARTTLS not supported by server'); return false;
        }
        $writeCmd('STARTTLS');
        $resp = $readResp();
        if ($resp['code'] !== 220) { fclose($fp); smtpSetError('STARTTLS failed: ' . trim($resp['text'])); return false; }
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
            fclose($fp); smtpSetError('TLS negotiation failed'); return false;
        }
        // Re-issue EHLO after TLS
        $writeCmd('EHLO ' . $heloHost);
        $ehlo = $readResp();
        if ($ehlo['code'] !== 250) { fclose($fp); smtpSetError('EHLO after STARTTLS failed: ' . trim($ehlo['text'])); return false; }
        $ehloText = strtoupper($ehlo['text']);
    }

    // AUTH selection
    $authLine = '';
    foreach (explode("\n", $ehlo['text']) as $line) {
        if (stripos($line, 'AUTH') !== false) { $authLine = strtoupper($line); break; }
    }
    $authUsed = '';
    if (strpos($authLine, 'LOGIN') !== false) {
        $authUsed = 'LOGIN';
        $writeCmd('AUTH LOGIN');
        $step1 = $readResp(); if ($step1['code'] !== 334) { fclose($fp); smtpSetError('AUTH LOGIN not accepted: ' . trim($step1['text'])); return false; }
        $writeCmd(base64_encode($username));
        $step2 = $readResp(); if ($step2['code'] !== 334) { fclose($fp); smtpSetError('Username rejected: ' . trim($step2['text'])); return false; }
        $writeCmd(base64_encode($password));
        $authOk = $readResp(); if ($authOk['code'] !== 235) { fclose($fp); smtpSetError('Authentication failed: ' . trim($authOk['text'])); return false; }
    } elseif (strpos($authLine, 'PLAIN') !== false) {
        $authUsed = 'PLAIN';
        $writeCmd('AUTH PLAIN');
        $step = $readResp(); if ($step['code'] !== 334) { fclose($fp); smtpSetError('AUTH PLAIN not accepted: ' . trim($step['text'])); return false; }
        $payload = base64_encode("\0" . $username . "\0" . $password);
        $writeCmd($payload);
        $authOk = $readResp(); if ($authOk['code'] !== 235) { fclose($fp); smtpSetError('Authentication failed: ' . trim($authOk['text'])); return false; }
    } else {
        fclose($fp); smtpSetError('Server does not advertise a supported AUTH method (LOGIN/PLAIN)'); return false;
    }

    // MAIL FROM / RCPT TO
    $writeCmd('MAIL FROM: <' . $from . '>');
    $mf = $readResp(); if ($mf['code'] !== 250) { fclose($fp); smtpSetError('MAIL FROM failed: ' . trim($mf['text'])); return false; }

    $writeCmd('RCPT TO: <' . $to . '>');
    $rt = $readResp(); if (!in_array($rt['code'], [250, 251], true)) { fclose($fp); smtpSetError('RCPT TO failed: ' . trim($rt['text'])); return false; }

    // DATA
    $writeCmd('DATA');
    $dr = $readResp(); if ($dr['code'] !== 354) { fclose($fp); smtpSetError('DATA not accepted: ' . trim($dr['text'])); return false; }

    // Headers
    $date = date('r');
    $domain = ($pos = strpos($from, '@')) !== false ? substr($from, $pos + 1) : 'localhost';
    try { $rand = bin2hex(random_bytes(8)); } catch (Throwable $e) { $rand = dechex(mt_rand()); }
    $messageId = sprintf('<%s.%d@%s>', $rand, time(), $domain);

    $headers = [];
    $headers[] = 'From: ' . sprintf('"%s" <%s>', addcslashes($fromName, '"'), $from);
    $headers[] = 'To: <' . $to . '>';
    $headers[] = 'Subject: ' . $subject;
    $headers[] = 'Date: ' . $date;
    $headers[] = 'Message-ID: ' . $messageId;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';

    // Normalize newlines to CRLF and dot-stuff body
    $body = str_replace(["\r\n", "\r"], "\n", (string)$bodyText);
    $body = preg_replace("/\n/", "\r\n", $body);
    $body = preg_replace("/\r\n\./", "\r\n..", $body);

    $data = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
    $writeCmd($data);
    $dr2 = $readResp(); if ($dr2['code'] !== 250) { fclose($fp); smtpSetError('Message rejected: ' . trim($dr2['text'])); return false; }

    $writeCmd('QUIT');
    fclose($fp);
    return true;
}
// CRUD helpers for email accounts
function listEmailAccounts(): array {
    try {
        $db = getDatabaseConnection();
        $rows = [];
        $result = $db->query("SELECT * FROM email_accounts ORDER BY id DESC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $db->close();
        return $rows;
    } catch (Throwable $e) {
        return [];
    }
}

function getEmailAccountById(int $id): ?array {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT * FROM email_accounts WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        $db->close();
        return $row ?: null;
    } catch (Throwable $e) { return null; }
}

function createEmailAccount(array $data): bool {
    try {
        // Validate input data
        $label = trim($data['label'] ?? '');
        $imap_host = trim($data['imap_host'] ?? '');
        $smtp_host = trim($data['smtp_host'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        
        // Basic validation
        if (empty($label) || empty($imap_host) || empty($smtp_host) || empty($email) || empty($password)) {
            return false;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        $imap_port = (int)($data['imap_port'] ?? 993);
        $smtp_port = (int)($data['smtp_port'] ?? 587);
        
        // Validate port ranges
        if ($imap_port < 1 || $imap_port > 65535 || $smtp_port < 1 || $smtp_port > 65535) {
            return false;
        }
        
        $imap_ssl = isset($data['imap_ssl']) ? (int)$data['imap_ssl'] : 1;
        $smtp_tls = isset($data['smtp_tls']) ? (int)$data['smtp_tls'] : 1;
        
        $db = getDatabaseConnection();
        $stmt = $db->prepare("INSERT INTO email_accounts (label, imap_host, smtp_host, email, password, imap_port, smtp_port, imap_ssl, smtp_tls) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssiiii', $label, $imap_host, $smtp_host, $email, $password, $imap_port, $smtp_port, $imap_ssl, $smtp_tls);
        $ok = $stmt->execute();
        $stmt->close();
        $db->close();
        return $ok === true;
    } catch (Throwable $e) { return false; }
}

function updateEmailAccount(int $id, array $data): bool {
    try {
        // Validate input data
        $label = trim($data['label'] ?? '');
        $imap_host = trim($data['imap_host'] ?? '');
        $smtp_host = trim($data['smtp_host'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        
        // Basic validation
        if (empty($label) || empty($imap_host) || empty($smtp_host) || empty($email) || empty($password)) {
            return false;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        $imap_port = (int)($data['imap_port'] ?? 993);
        $smtp_port = (int)($data['smtp_port'] ?? 587);
        
        // Validate port ranges
        if ($imap_port < 1 || $imap_port > 65535 || $smtp_port < 1 || $smtp_port > 65535) {
            return false;
        }
        
        $imap_ssl = isset($data['imap_ssl']) ? (int)$data['imap_ssl'] : 1;
        $smtp_tls = isset($data['smtp_tls']) ? (int)$data['smtp_tls'] : 1;
        
        $db = getDatabaseConnection();
        $stmt = $db->prepare("UPDATE email_accounts SET label=?, imap_host=?, smtp_host=?, email=?, password=?, imap_port=?, smtp_port=?, imap_ssl=?, smtp_tls=? WHERE id=?");
        $stmt->bind_param('sssssiiiii', $label, $imap_host, $smtp_host, $email, $password, $imap_port, $smtp_port, $imap_ssl, $smtp_tls, $id);
        $ok = $stmt->execute();
        $stmt->close();
        $db->close();
        return $ok === true;
    } catch (Throwable $e) { return false; }
}

function deleteEmailAccount(int $id): bool {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("DELETE FROM email_accounts WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        $db->close();
        return $ok === true;
    } catch (Throwable $e) { return false; }
}

function testImapConnection(array $account): string {
    if (!function_exists('imap_open')) {
        return 'IMAP extension is not installed';
    }
    // Clear previous errors/alerts and set short timeouts
    imap_errors();
    imap_alerts();
    if (function_exists('imap_timeout')) {
        @imap_timeout(1, 8); // OPENTIMEOUT
        @imap_timeout(2, 8); // READTIMEOUT
        @imap_timeout(3, 8); // WRITETIMEOUT
    }

    $host = $account['imap_host'] ?? '';
    $port = (int)($account['imap_port'] ?? 993);
    $ssl = (int)($account['imap_ssl'] ?? 1) === 1 ? '/ssl' : '';
    $mailbox = '{' . $host . ':' . $port . '/imap' . $ssl . '}INBOX';
    $email = $account['email'] ?? '';
    $pass = $account['password'] ?? '';

    $inbox = @imap_open($mailbox, $email, $pass, 0, 1, []);
    if ($inbox === false) {
        $errors = imap_errors() ?: [];
        $alerts = imap_alerts() ?: [];
        $msg = 'Connection failed';
        if (!empty($errors)) {
            $msg .= ': ' . implode(' | ', array_unique($errors));
        } elseif (!empty($alerts)) {
            $msg .= ': ' . implode(' | ', array_unique($alerts));
        }
        return $msg;
    }
    imap_close($inbox);
    return 'OK';
}

function getEmailCountToday(): int {
    try {
        $db = getDatabaseConnection();
        $today = (new DateTime('today'))->format('Y-m-d');
        $stmt = $db->prepare("SELECT COUNT(*) FROM emails WHERE DATE(created_at) = ?");
        $stmt->bind_param('s', $today);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        $db->close();
        return (int)$count;
    } catch (Throwable $e) {
        return 0;
    }
}

function getPendingFollowups(): int {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE status = 'Pending'");
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        $db->close();
        return (int)$count;
    } catch (Throwable $e) {
        return 7; // Mock data fallback
    }
}

function getAgentSummary(): string {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM agents WHERE status = 'active'");
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        $db->close();
        return $count . " Active";
    } catch (Throwable $e) {
        return "3 Active"; // Mock data fallback
    }
}

function getSLACountdown(): string {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT MIN(TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(created_at, INTERVAL 24 HOUR))) as minutes_left
            FROM emails 
            WHERE status = 'New' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
        $stmt->bind_result($minutes);
        $stmt->fetch();
        $stmt->close();
        $db->close();
        
        if ($minutes <= 0) return "Overdue";
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours . "h " . $mins . "m";
    } catch (Throwable $e) {
        return "2h 15m"; // Mock data fallback
    }
}

function getSentEmailsCount(): int {
    try {
        $db = getDatabaseConnection();
        $today = (new DateTime('today'))->format('Y-m-d');
        $stmt = $db->prepare("SELECT COUNT(*) FROM emails WHERE status = 'Completed' AND DATE(created_at) = ?");
        $stmt->bind_param('s', $today);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        $db->close();
        return (int)$count;
    } catch (Throwable $e) {
        return 12; // Mock data fallback
    }
}

function getPendingEmailsCount(): int {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM emails WHERE status IN ('New', 'In Progress')");
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        $db->close();
        return (int)$count;
    } catch (Throwable $e) {
        return 8; // Mock data fallback
    }
}

function getAISuggestionsCount(): int {
    try {
        $db = getDatabaseConnection();
        $today = (new DateTime('today'))->format('Y-m-d');
        $stmt = $db->prepare("SELECT COUNT(*) FROM activity_log WHERE action = 'ai_suggestion' AND DATE(created_at) = ?");
        $stmt->bind_param('s', $today);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        $db->close();
        return (int)$count;
    } catch (Throwable $e) {
        return 5; // Mock data fallback
    }
}

function getAllEmailAccounts(): array {
    try {
        $db = getDatabaseConnection();
        $result = $db->query("SELECT * FROM email_accounts ORDER BY label ASC");
        $accounts = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $accounts[] = $row;
            }
        }
        $db->close();
        return $accounts;
    } catch (Throwable $e) {
        return [];
    }
}

function getRecentEmails(int $limit = 10): array {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT * FROM emails ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $emails = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $emails[] = $row;
            }
        }
        $stmt->close();
        $db->close();
        return $emails;
    } catch (Throwable $e) {
        return [];
    }
}

function logEmailActivity(int $accountId, string $action, string $recipient, string $subject): void {
    try {
        $db = getDatabaseConnection();
        $details = json_encode([
            'recipient' => $recipient,
            'subject' => $subject,
            'account_id' => $accountId
        ]);
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $db->prepare("
            INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address, created_at) 
            VALUES (1, ?, 'email', ?, ?, ?, NOW())
        ");
        $stmt->bind_param('siss', $action, $accountId, $details, $ip);
        $stmt->execute();
        $stmt->close();
        $db->close();
    } catch (Throwable $e) {
        error_log("Error logging email activity: " . $e->getMessage());
    }
}

function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
}

?>

