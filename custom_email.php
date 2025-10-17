<?php require_once __DIR__ . '/templates/header.php'; ?>
<?php require_once __DIR__ . '/functions.php'; ?>
<?php
$accounts = listEmailAccounts();
$aid = isset($_GET['aid']) ? (int)$_GET['aid'] : (count($accounts) ? (int)$accounts[0]['id'] : 0);
$folder = isset($_GET['folder']) ? (string)$_GET['folder'] : 'INBOX';
$flash_msg = $_GET['msg'] ?? '';
$flash_err = $_GET['error'] ?? '';
?>

<div class="p-6 glass-surface rounded">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Custom Email</h1>
    <a href="email_accounts.php" class="text-sm text-indigo-300 hover:text-white">Manage Accounts</a>
  </div>
  <?php if ($flash_msg): ?>
    <div class="glass-card p-3 rounded mt-3 text-sm text-green-300"><?php echo htmlspecialchars($flash_msg); ?></div>
  <?php endif; ?>
  <?php if ($flash_err): ?>
    <div class="glass-card p-3 rounded mt-3 text-sm text-red-300"><?php echo htmlspecialchars($flash_err); ?></div>
  <?php endif; ?>

  <?php if ($aid === 0): ?>
    <div class="glass-card p-4 rounded mt-4 text-sm text-gray-300">Add an account first in Email Accounts.</div>
  <?php else: ?>
    <?php $acc = getEmailAccountById($aid); ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
      <div class="glass-card p-4 rounded text-sm">
        <div class="font-semibold mb-2">Accounts</div>
        <div class="space-y-1 max-h-64 overflow-y-auto">
          <?php foreach ($accounts as $a): ?>
            <a class="block glass-surface px-2 py-1 rounded hover:opacity-90" href="custom_email.php?aid=<?php echo (int)$a['id']; ?>&folder=<?php echo rawurlencode($folder); ?>"><?php echo htmlspecialchars($a['label']); ?></a>
          <?php endforeach; ?>
        </div>
        <div class="font-semibold mt-4 mb-2">Folders</div>
        <div class="space-y-1 max-h-64 overflow-y-auto">
          <?php foreach (['INBOX','Drafts','Sent','Trash','Spam'] as $f): ?>
            <a class="block glass-surface px-2 py-1 rounded hover:opacity-90" href="custom_email.php?aid=<?php echo (int)$aid; ?>&folder=<?php echo rawurlencode($f); ?>"><?php echo htmlspecialchars($f); ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="md:col-span-2 glass-card p-4 rounded text-sm">
        <div class="flex items-center justify-between">
          <div class="font-semibold">Messages · <?php echo htmlspecialchars($folder); ?></div>
          <a class="glass-card px-2 py-1 rounded" href="custom_email.php?aid=<?php echo (int)$aid; ?>&folder=<?php echo rawurlencode($folder); ?>">Refresh</a>
        </div>
        <div class="space-y-2 max-h-96 overflow-y-auto mt-2">
          <?php $msgs = imapListMessages($acc, $folder, 25); ?>
          <?php foreach ($msgs as $m): ?>
            <div class="glass-surface p-2 rounded flex items-center justify-between">
              <div>
                <div class="text-xs text-gray-300"><?php echo htmlspecialchars($m['from']); ?> · <?php echo htmlspecialchars($m['date']); ?></div>
                <div class="font-semibold text-sm"><?php echo htmlspecialchars($m['subject']); ?></div>
              </div>
              <div class="text-xs flex items-center gap-2">
                <a class="glass-card px-2 py-1 rounded" href="custom_email_message.php?aid=<?php echo (int)$aid; ?>&folder=<?php echo rawurlencode($folder); ?>&num=<?php echo (int)$m['num']; ?>">Open</a>
                <a class="glass-card px-2 py-1 rounded" href="custom_email_action.php?action=delete&aid=<?php echo (int)$aid; ?>&folder=<?php echo rawurlencode($folder); ?>&num=<?php echo (int)$m['num']; ?>" onclick="return confirm('Delete this email?');">Delete</a>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (count($msgs) === 0): ?>
            <div class="text-xs text-gray-300">No messages.</div>
          <?php endif; ?>
        </div>

        <div class="mt-4">
          <div class="font-semibold mb-2">Compose</div>
          <form method="post" action="custom_email_action.php" class="space-y-2">
            <input type="hidden" name="action" value="send" />
            <input type="hidden" name="aid" value="<?php echo (int)$aid; ?>" />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <input class="glass-input rounded px-2 py-2 text-sm" type="email" name="to" placeholder="To" required />
              <input class="glass-input rounded px-2 py-2 text-sm" type="text" name="subject" placeholder="Subject" required />
            </div>
            <textarea class="glass-input rounded px-2 py-2 text-sm w-full" name="body" placeholder="Message" rows="5" required></textarea>
            <button class="glass-card px-3 py-2 rounded text-sm" type="submit">Send</button>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>


