<?php require_once __DIR__ . '/templates/header.php'; ?>
<?php require_once __DIR__ . '/functions.php'; ?>
<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msgId = isset($_GET['msg']) ? (string)$_GET['msg'] : '';
if ($id <= 0 || $msgId === '') { echo '<div class="p-6">Invalid request</div>'; require_once __DIR__ . '/templates/footer.php'; exit; }
$acc = getGraphAccountById($id);
if (!$acc || !refreshGraphTokenIfNeeded($acc)) { echo '<div class="p-6">Account not ready</div>'; require_once __DIR__ . '/templates/footer.php'; exit; }

$message = graphApi($acc['access_token'], '/me/messages/' . rawurlencode($msgId));
?>

<div class="p-6 glass-surface rounded">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Message</h1>
    <a href="ms_mail.php?id=<?php echo (int)$acc['id']; ?>" class="text-sm text-indigo-300 hover:text-white">Back</a>
  </div>

  <div class="glass-card p-4 rounded mt-4 text-sm">
    <div class="mb-2"><span class="text-gray-300">From:</span> <?php echo htmlspecialchars($message['from']['emailAddress']['name'] ?? ''); ?> &lt;<?php echo htmlspecialchars($message['from']['emailAddress']['address'] ?? ''); ?>&gt;</div>
    <div class="mb-2"><span class="text-gray-300">Subject:</span> <?php echo htmlspecialchars($message['subject'] ?? '(no subject)'); ?></div>
    <div class="mb-2"><span class="text-gray-300">Received:</span> <?php echo htmlspecialchars($message['receivedDateTime'] ?? ''); ?></div>
    <div class="mt-3 p-3 glass-surface rounded">
      <?php
        $contentType = $message['body']['contentType'] ?? 'Text';
        $content = $message['body']['content'] ?? '';
        if (strcasecmp($contentType, 'html') === 0) echo $content; else echo nl2br(htmlspecialchars($content));
      ?>
    </div>
  </div>

  <div class="flex items-center gap-2 mt-4 text-sm">
    <a class="glass-card px-3 py-2 rounded" href="ms_action.php?action=delete&id=<?php echo (int)$acc['id']; ?>&msg=<?php echo rawurlencode($msgId); }?>" onclick="return confirm('Delete this message?');">Delete</a>
    <button class="glass-card px-3 py-2 rounded" onclick="document.getElementById('reply').scrollIntoView({behavior:'smooth'});">Reply</button>
    <button class="glass-card px-3 py-2 rounded" onclick="document.getElementById('forward').scrollIntoView({behavior:'smooth'});">Forward</button>
  </div>

  <div id="reply" class="glass-card p-4 rounded mt-4">
    <div class="font-semibold mb-2">Reply</div>
    <form method="post" action="ms_action.php">
      <input type="hidden" name="action" value="reply" />
      <input type="hidden" name="id" value="<?php echo (int)$acc['id']; ?>" />
      <input type="hidden" name="msg" value="<?php echo htmlspecialchars($msgId); ?>" />
      <textarea class="glass-input rounded px-2 py-2 text-sm w-full" name="body" rows="5" placeholder="Your reply..." required></textarea>
      <button class="glass-card px-3 py-2 rounded text-sm mt-2" type="submit">Send Reply</button>
    </form>
  </div>

  <div id="forward" class="glass-card p-4 rounded mt-4">
    <div class="font-semibold mb-2">Forward</div>
    <form method="post" action="ms_action.php">
      <input type="hidden" name="action" value="forward" />
      <input type="hidden" name="id" value="<?php echo (int)$acc['id']; ?>" />
      <input type="hidden" name="msg" value="<?php echo htmlspecialchars($msgId); ?>" />
      <input class="glass-input rounded px-2 py-2 text-sm w-full" type="email" name="to" placeholder="Recipient" required />
      <textarea class="glass-input rounded px-2 py-2 text-sm w-full mt-2" name="body" rows="5" placeholder="Add a note (optional)"></textarea>
      <button class="glass-card px-3 py-2 rounded text-sm mt-2" type="submit">Send Forward</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>


