<?php require_once __DIR__ . '/templates/header.php'; ?>
<?php require_once __DIR__ . '/functions.php'; ?>
<?php
$aid = isset($_GET['aid']) ? (int)$_GET['aid'] : 0;
$folder = isset($_GET['folder']) ? (string)$_GET['folder'] : 'INBOX';
$num = isset($_GET['num']) ? (int)$_GET['num'] : 0;
if ($aid <= 0 || $num <= 0) { echo '<div class="p-6">Invalid request</div>'; require_once __DIR__ . '/templates/footer.php'; exit; }
$acc = getEmailAccountById($aid);
$msg = imapGetMessage($acc, $folder, $num);
?>

<div class="p-6 glass-surface rounded">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Message</h1>
    <a href="custom_email.php?aid=<?php echo (int)$aid; ?>&folder=<?php echo rawurlencode($folder); ?>" class="text-sm text-indigo-300 hover:text-white">Back</a>
  </div>

  <div class="glass-card p-4 rounded mt-4 text-sm">
    <div class="mb-2"><span class="text-gray-300">From:</span> <?php echo htmlspecialchars($msg['from'] ?? ''); ?></div>
    <div class="mb-2"><span class="text-gray-300">Subject:</span> <?php echo htmlspecialchars($msg['subject'] ?? '(no subject)'); ?></div>
    <div class="mb-2"><span class="text-gray-300">Date:</span> <?php echo htmlspecialchars($msg['date'] ?? ''); ?></div>
    <div class="mt-3 p-3 glass-surface rounded">
      <?php echo nl2br(htmlspecialchars($msg['body'] ?? '')); ?>
    </div>
  </div>

  <div class="flex items-center gap-2 mt-4 text-sm">
    <a class="glass-card px-3 py-2 rounded" href="custom_email_action.php?action=delete&aid=<?php echo (int)$aid; ?>&folder=<?php echo rawurlencode($folder); ?>&num=<?php echo (int)$num; ?>" onclick="return confirm('Delete this message?');">Delete</a>
    <button class="glass-card px-3 py-2 rounded" onclick="document.getElementById('reply').scrollIntoView({behavior:'smooth'});">Reply</button>
  </div>

  <div id="reply" class="glass-card p-4 rounded mt-4">
    <div class="font-semibold mb-2">Reply</div>
    <form method="post" action="custom_email_action.php">
      <input type="hidden" name="action" value="send" />
      <input type="hidden" name="aid" value="<?php echo (int)$aid; ?>" />
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <input class="glass-input rounded px-2 py-2 text-sm" type="email" name="to" value="<?php echo htmlspecialchars($msg['from'] ?? ''); ?>" placeholder="To" required />
        <input class="glass-input rounded px-2 py-2 text-sm" type="text" name="subject" value="Re: <?php echo htmlspecialchars($msg['subject'] ?? ''); ?>" placeholder="Subject" required />
      </div>
      <textarea class="glass-input rounded px-2 py-2 text-sm w-full" name="body" placeholder="Message" rows="5" required></textarea>
      <button class="glass-card px-3 py-2 rounded text-sm mt-2" type="submit">Send</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>


