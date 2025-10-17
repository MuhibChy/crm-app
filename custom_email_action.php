<?php 
require_once __DIR__ . '/functions.php'; 
require_once __DIR__ . '/security.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = sanitizeInput($method === 'POST' ? ($_POST['action'] ?? '') : ($_GET['action'] ?? ''));
$aid = validateInt($method === 'POST' ? ($_POST['aid'] ?? 0) : ($_GET['aid'] ?? 0)) ?? 0;
$folder = sanitizeInput($method === 'POST' ? ($_POST['folder'] ?? 'INBOX') : ($_GET['folder'] ?? 'INBOX'));
$num = validateInt($method === 'POST' ? ($_POST['num'] ?? 0) : ($_GET['num'] ?? 0)) ?? 0;

if ($aid <= 0) { header('Location: custom_email.php'); exit; }
$acc = getEmailAccountById($aid);
if (!$acc) { header('Location: custom_email.php'); exit; }

switch ($action) {
  case 'delete':
    if ($num <= 0) break;
    imapDeleteMessage($acc, $folder, $num);
    header('Location: custom_email.php?aid=' . $aid . '&folder=' . urlencode($folder));
    exit;
  case 'send':
    if ($method !== 'POST') break;
    
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
      logSecurityEvent('csrf_validation_failed', ['action' => 'send_email', 'aid' => $aid]);
      header('Location: custom_email.php?aid=' . $aid . '&error=' . urlencode('Security validation failed'));
      exit;
    }
    
    // Rate limiting for email sending
    if (!checkRateLimit('email_send_' . $aid, 10, 3600)) {
      logSecurityEvent('rate_limit_exceeded', ['action' => 'send_email', 'aid' => $aid]);
      header('Location: custom_email.php?aid=' . $aid . '&error=' . urlencode('Rate limit exceeded. Please try again later.'));
      exit;
    }
    
    $to = sanitizeInput($_POST['to'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $body = sanitizeInput($_POST['body'] ?? '');
    
    if (!validateEmail($to)) {
      header('Location: custom_email.php?aid=' . $aid . '&error=' . urlencode('Invalid recipient email'));
      exit;
    }
    $ok = smtpSendSimple($acc, $to, $subject, $body);
    if ($ok) {
      header('Location: custom_email.php?aid=' . $aid . '&msg=' . urlencode('Email sent successfully'));
    } else {
      $err = smtpLastError();
      header('Location: custom_email.php?aid=' . $aid . '&error=' . urlencode($err !== '' ? $err : 'Send failed'));
    }
    exit;
}

header('Location: custom_email.php');
exit;
?>


