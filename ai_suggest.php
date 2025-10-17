<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/ai.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Invalid request method'], 405);
    exit;
}

// Rate limiting for AI requests
if (!checkRateLimit('ai_suggest_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10, 3600)) {
    jsonResponse(['success' => false, 'error' => 'Rate limit exceeded. Please try again later.'], 429);
    exit;
}

$subject = sanitizeInput($_POST['subject'] ?? '');
$body = sanitizeInput($_POST['body'] ?? '');

if (empty($subject) && empty($body)) {
    jsonResponse(['success' => false, 'error' => 'Subject or body required for AI suggestion'], 400);
    exit;
}

try {
    $content = trim($subject . "\n\n" . $body);
    $suggestion = getAISuggestion($content);
    
    // Log AI usage
    logAIUsage('email_suggestion', $content);
    
    // Parse suggestion for structured response
    $response = parseAISuggestion($suggestion);
    
    jsonResponse([
        'success' => true,
        'suggestion' => $suggestion,
        'suggested_subject' => $response['subject'] ?? null,
        'suggested_body' => $response['body'] ?? null,
        'tone' => $response['tone'] ?? 'professional',
        'confidence' => $response['confidence'] ?? 0.8
    ]);
    
} catch (Exception $e) {
    error_log("AI suggestion error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'AI service temporarily unavailable'], 503);
}

function parseAISuggestion(string $suggestion): array {
    $response = [];
    
    // Try to extract structured suggestions
    if (preg_match('/Subject:\s*(.+)/i', $suggestion, $matches)) {
        $response['subject'] = trim($matches[1]);
    }
    
    if (preg_match('/Body:\s*(.+)/is', $suggestion, $matches)) {
        $response['body'] = trim($matches[1]);
    }
    
    // Determine tone
    $lowerSuggestion = strtolower($suggestion);
    if (strpos($lowerSuggestion, 'formal') !== false) {
        $response['tone'] = 'formal';
    } elseif (strpos($lowerSuggestion, 'casual') !== false) {
        $response['tone'] = 'casual';
    } else {
        $response['tone'] = 'professional';
    }
    
    // Mock confidence based on content length and keywords
    $confidence = 0.7;
    if (strlen($suggestion) > 100) $confidence += 0.1;
    if (strpos($lowerSuggestion, 'recommend') !== false) $confidence += 0.1;
    if (strpos($lowerSuggestion, 'suggest') !== false) $confidence += 0.1;
    
    $response['confidence'] = min(0.95, $confidence);
    
    return $response;
}

function logAIUsage(string $type, string $content): void {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            INSERT INTO activity_log (user_id, action, entity_type, details, ip_address, created_at) 
            VALUES (1, 'ai_suggestion', ?, ?, ?, NOW())
        ");
        
        $details = json_encode([
            'type' => $type,
            'content_length' => strlen($content),
            'timestamp' => time()
        ]);
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->bind_param('sss', $type, $details, $ip);
        $stmt->execute();
        $stmt->close();
        $db->close();
    } catch (Throwable $e) {
        error_log("Error logging AI usage: " . $e->getMessage());
    }
}
?>
