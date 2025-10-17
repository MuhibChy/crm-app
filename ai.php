<?php
require_once __DIR__ . '/config.php';

function getAISuggestion(string $emailContent): string {
    if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === 'your-openai-api-key' || OPENAI_API_KEY === '' || empty(OPENAI_API_KEY)) {
        return 'AI is not configured. Set OPENAI_API_KEY in your .env file';
    }
    
    // Rate limiting for AI requests
    require_once __DIR__ . '/security.php';
    if (!checkRateLimit('ai_request', 20, 3600)) {
        return 'AI rate limit exceeded. Please try again later.';
    }
    
    // Validate and sanitize input
    $emailContent = trim($emailContent);
    if (empty($emailContent)) {
        return 'No email content provided for AI analysis.';
    }
    
    if (strlen($emailContent) > 4000) {
        $emailContent = substr($emailContent, 0, 4000) . '...';
    }

    $messages = [
        [
            'role' => 'system',
            'content' => 'You are a helpful CRM assistant. Provide concise next-action suggestions.'
        ],
        [
            'role' => 'user',
            'content' => "Suggest the next action for this email content:\n" . $emailContent
        ]
    ];

    $payload = json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => $messages,
        'temperature' => 0.2,
        'max_tokens' => 180,
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . OPENAI_API_KEY,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $result = curl_exec($ch);
    if ($result === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return 'AI request failed: ' . $err;
    }

    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($result, true);

    if ($code >= 200 && $code < 300 && isset($data['choices'][0]['message']['content'])) {
        return trim($data['choices'][0]['message']['content']);
    }

    if (isset($data['error']['message'])) {
        return 'AI error: ' . $data['error']['message'];
    }

    return 'AI returned an unexpected response.';
}
?>
