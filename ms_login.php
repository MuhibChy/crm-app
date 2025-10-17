<?php require_once __DIR__ . '/config.php'; ?>
<?php
$authUrl = 'https://login.microsoftonline.com/' . rawurlencode(GRAPH_TENANT) . '/oauth2/v2.0/authorize';
$params = [
  'client_id' => GRAPH_CLIENT_ID,
  'response_type' => 'code',
  'redirect_uri' => GRAPH_REDIRECT_URI,
  'response_mode' => 'query',
  'scope' => 'offline_access Mail.Read Mail.ReadWrite Mail.Send User.Read',
  'state' => bin2hex(random_bytes(8))
];
header('Location: ' . $authUrl . '?' . http_build_query($params));
exit;
?>


