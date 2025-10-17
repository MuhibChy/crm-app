<?php require_once __DIR__ . '/functions.php'; ?>
<?php
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $method === 'POST' ? ($_POST['action'] ?? '') : ($_GET['action'] ?? '');
$id = $method === 'POST' ? (int)($_POST['id'] ?? 0) : (int)($_GET['id'] ?? 0);
$msg = $method === 'POST' ? ($_POST['msg'] ?? '') : ($_GET['msg'] ?? '');

if ($id <= 0) { header('Location: ms_mail.php'); exit; }
$acc = getGraphAccountById($id);
if (!$acc || !refreshGraphTokenIfNeeded($acc)) { header('Location: ms_mail.php'); exit; }

switch ($action) {
  case 'delete':
    if ($msg === '') break;
    graphApi($acc['access_token'], '/me/messages/' . rawurlencode($msg), [ 'method' => 'DELETE' ]);
    header('Location: ms_mail.php?id=' . $id);
    exit;
  case 'reply':
    if ($method !== 'POST' || $msg === '') break;
    $body = $_POST['body'] ?? '';
    $payload = [ 'comment' => $body ];
    graphApi($acc['access_token'], '/me/messages/' . rawurlencode($msg) . '/reply', [ 'method' => 'POST', 'body' => $payload ]);
    header('Location: ms_message.php?id=' . $id . '&msg=' . rawurlencode($msg));
    exit;
  case 'forward':
    if ($method !== 'POST' || $msg === '') break;
    $to = $_POST['to'] ?? '';
    $body = $_POST['body'] ?? '';
    $payload = [
      'comment' => $body,
      'toRecipients' => [[ 'emailAddress' => [ 'address' => $to ] ]]
    ];
    graphApi($acc['access_token'], '/me/messages/' . rawurlencode($msg) . '/forward', [ 'method' => 'POST', 'body' => $payload ]);
    header('Location: ms_message.php?id=' . $id . '&msg=' . rawurlencode($msg));
    exit;
}

header('Location: ms_mail.php');
exit;
?>


