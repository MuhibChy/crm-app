<?php require_once __DIR__ . '/config.php'; ?>
<?php require_once __DIR__ . '/functions.php'; ?>
<?php
$code = $_GET['code'] ?? '';
if ($code === '') {
  echo 'Missing authorization code';
  exit;
}

$tokenUrl = 'https://login.microsoftonline.com/' . rawurlencode(GRAPH_TENANT) . '/oauth2/v2.0/token';
$body = http_build_query([
  'client_id' => GRAPH_CLIENT_ID,
  'client_secret' => GRAPH_CLIENT_SECRET,
  'redirect_uri' => GRAPH_REDIRECT_URI,
  'grant_type' => 'authorization_code',
  'code' => $code
]);

$opts = [
  'http' => [
    'method' => 'POST',
    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
    'content' => $body,
    'timeout' => 20,
  ]
];
$raw = @file_get_contents($tokenUrl, false, stream_context_create($opts));
if ($raw === false) { echo 'Token request failed'; exit; }
$tokens = json_decode($raw, true);
if (!isset($tokens['access_token'])) { echo 'Token response invalid'; exit; }

// Get profile to identify account
$ctx = stream_context_create([
  'http' => [
    'method' => 'GET',
    'header' => 'Authorization: Bearer ' . $tokens['access_token'] . "\r\n",
    'timeout' => 15,
  ]
]);
$meRaw = @file_get_contents('https://graph.microsoft.com/v1.0/me', false, $ctx);
$profile = $meRaw ? json_decode($meRaw, true) : [];

saveGraphTokens($profile, $tokens);

header('Location: ms_mail.php');
exit;
?>


