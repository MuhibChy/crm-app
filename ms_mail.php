<?php require_once __DIR__ . '/templates/header.php'; ?>
<?php require_once __DIR__ . '/functions.php'; ?>
<?php $accounts = listGraphAccounts(); ?>
<?php
$selectedAccountId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selectedFolder = isset($_GET['folder']) ? (string)$_GET['folder'] : 'Inbox';
$pageTop = isset($_GET['top']) ? max(1, (int)$_GET['top']) : 10;
?>

<div class="p-6 glass-surface rounded">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Outlook (Microsoft Graph)</h1>
    <a href="ms_login.php" class="glass-card px-3 py-2 rounded text-sm">Connect Account</a>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <?php foreach ($accounts as $acc): ?>
      <?php $ok = refreshGraphTokenIfNeeded($acc); ?>
      <div class="glass-card p-4 rounded">
        <div class="font-semibold text-sm"><?php echo htmlspecialchars($acc['display_name'] ?: $acc['email']); ?></div>
        <div class="text-xs text-gray-300 mb-3"><?php echo htmlspecialchars($acc['email']); ?></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="md:col-span-1 text-sm">
            <div class="font-semibold mb-2">Folders</div>
            <div class="space-y-1 max-h-72 overflow-y-auto">
              <?php $folders = $ok ? graphApi($acc['access_token'], '/me/mailFolders?$top=50') : ['value'=>[]]; ?>
              <?php if (isset($folders['value'])): foreach ($folders['value'] as $f): ?>
                <?php $fid = $f['id']; ?>
                <a class="block glass-surface px-2 py-1 rounded hover:opacity-90" href="ms_mail.php?id=<?php echo (int)$acc['id']; ?>&folder=<?php echo rawurlencode($fid); ?>&top=<?php echo $pageTop; ?>"><?php echo htmlspecialchars($f['displayName']); ?></a>
              <?php endforeach; else: ?>
                <div class="text-xs text-gray-300">No folders</div>
              <?php endif; ?>
            </div>
          </div>
          <div class="md:col-span-2 text-sm">
            <div class="font-semibold mb-2">Messages</div>
            <div class="space-y-2 max-h-72 overflow-y-auto">
              <?php
                $folderParam = ($selectedAccountId === (int)$acc['id']) ? ($selectedFolder ?: 'Inbox') : 'Inbox';
                $endpoint = $folderParam === 'Inbox' ? '/me/mailFolders/Inbox/messages?$top=' . $pageTop : '/me/mailFolders/' . rawurlencode($folderParam) . '/messages?$top=' . $pageTop;
                $msgs = $ok ? graphApi($acc['access_token'], $endpoint) : ['value'=>[]];
              ?>
              <?php if (isset($msgs['value'])): foreach ($msgs['value'] as $msg): ?>
                <div class="glass-surface p-2 rounded">
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="text-xs text-gray-300"><?php echo htmlspecialchars($msg['from']['emailAddress']['name'] ?? ''); ?> · <?php echo htmlspecialchars($msg['from']['emailAddress']['address'] ?? ''); ?></div>
                      <div class="font-semibold text-sm"><?php echo htmlspecialchars($msg['subject'] ?? '(no subject)'); ?></div>
                    </div>
                    <div class="text-xs">
                      <a class="glass-card px-2 py-1 rounded" href="ms_message.php?id=<?php echo (int)$acc['id']; ?>&msg=<?php echo rawurlencode($msg['id']); ?>">Open</a>
                    </div>
                  </div>
                </div>
              <?php endforeach; else: ?>
                <div class="text-xs text-gray-300">Unable to load messages.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <form method="post" action="ms_send.php" class="space-y-2 mt-3">
          <input type="hidden" name="id" value="<?php echo (int)$acc['id']; ?>" />
          <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <input class="glass-input rounded px-2 py-2 text-sm" type="email" name="to" placeholder="To" required />
            <input class="glass-input rounded px-2 py-2 text-sm" type="text" name="subject" placeholder="Subject" required />
          </div>
          <textarea class="glass-input rounded px-2 py-2 text-sm" name="body" placeholder="Message" rows="4" required></textarea>
          <button class="glass-card px-3 py-2 rounded text-sm" type="submit">Send</button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (count($accounts) === 0): ?>
      <div class="glass-card p-4 rounded text-sm text-gray-300">No Outlook accounts connected yet. Click "Connect Account" to sign in.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>