<?php
require_once __DIR__ . '/ai.php';

header('Content-Type: application/json');

$query = isset($_POST['query']) ? (string)$_POST['query'] : '';
if ($query === '') {
    echo json_encode(['response' => 'Please provide a message.']);
    exit;
}

$response = getAISuggestion($query);
echo json_encode(['response' => $response]);
?>
