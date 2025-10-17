<?php
require_once __DIR__ . '/config.php';

function fetchSheetData(): array {
    $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode(GOOGLE_SHEET_ID) . '/values/Sheet1?key=' . urlencode(GOOGLE_API_KEY);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
        ]
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    return isset($data['values']) && is_array($data['values']) ? $data['values'] : [];
}
?>
