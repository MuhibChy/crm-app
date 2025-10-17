<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Invalid request method'], 405);
    exit;
}

try {
    $stats = [
        'success' => true,
        'today' => getEmailCountToday(),
        'sent' => getSentEmailsCount(),
        'pending' => getPendingEmailsCount(),
        'ai_helps' => getAISuggestionsCount(),
        'accounts' => count(getAllEmailAccounts()),
        'sla_status' => getSLACountdown(),
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    // Additional detailed stats
    $stats['detailed'] = [
        'emails_by_status' => getEmailsByStatus(),
        'emails_by_priority' => getEmailsByPriority(),
        'top_senders' => getTopSenders(5),
        'response_times' => getAverageResponseTimes()
    ];
    
    jsonResponse($stats);
    
} catch (Exception $e) {
    error_log("Error getting email stats: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Failed to load statistics'], 500);
}

function getEmailsByStatus(): array {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT status, COUNT(*) as count 
            FROM emails 
            WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY status
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats[$row['status']] = (int)$row['count'];
            }
        }
        
        $stmt->close();
        $db->close();
        return $stats;
    } catch (Throwable $e) {
        return [];
    }
}

function getEmailsByPriority(): array {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT priority, COUNT(*) as count 
            FROM emails 
            WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY priority
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats[$row['priority']] = (int)$row['count'];
            }
        }
        
        $stmt->close();
        $db->close();
        return $stats;
    } catch (Throwable $e) {
        return [];
    }
}

function getTopSenders(int $limit = 5): array {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT sender, COUNT(*) as count 
            FROM emails 
            WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY sender 
            ORDER BY count DESC 
            LIMIT ?
        ");
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $senders = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $senders[] = [
                    'sender' => $row['sender'],
                    'count' => (int)$row['count']
                ];
            }
        }
        
        $stmt->close();
        $db->close();
        return $senders;
    } catch (Throwable $e) {
        return [];
    }
}

function getAverageResponseTimes(): array {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT 
                AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours,
                MIN(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as min_hours,
                MAX(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as max_hours
            FROM emails 
            WHERE status = 'Completed' 
            AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND updated_at > created_at
        ");
        $stmt->execute();
        $stmt->bind_result($avg, $min, $max);
        $stmt->fetch();
        $stmt->close();
        $db->close();
        
        return [
            'average_hours' => round($avg ?? 0, 2),
            'fastest_hours' => round($min ?? 0, 2),
            'slowest_hours' => round($max ?? 0, 2)
        ];
    } catch (Throwable $e) {
        return [
            'average_hours' => 0,
            'fastest_hours' => 0,
            'slowest_hours' => 0
        ];
    }
}
?>
