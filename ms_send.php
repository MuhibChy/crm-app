<?php require_once __DIR__ . '/functions.php'; ?>
<?php
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$to = $_POST['to'] ?? '';
$subject = $_POST['subject'] ?? '';
$body = $_POST['body'] ?? '';

if ($id <= 0 || $to === '') { header('Location: ms_mail.php'); exit; }
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { header('Location: ms_mail.php'); exit; }

$acc = getGraphAccountById($id);
if (!$acc) { header('Location: ms_mail.php'); exit; }
if (!refreshGraphTokenIfNeeded($acc)) { header('Location: ms_mail.php'); exit; }

$payload = [
  'message' => [
    'subject' => $subject,
    'body' => [ 'contentType' => 'Text', 'content' => $body ],
    'toRecipients' => [[ 'emailAddress' => [ 'address' => $to ] ]]
  ],
  'saveToSentItems' => true
];

$res = graphApi($acc['access_token'], '/me/sendMail', [ 'method' => 'POST', 'body' => $payload ]);

header('Location: ms_mail.php');
exit;
?>


