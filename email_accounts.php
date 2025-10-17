<?php require_once __DIR__ . '/templates/header.php'; ?>
<?php require_once __DIR__ . '/functions.php'; ?>
<?php require_once __DIR__ . '/security.php'; ?>
<?php
$action = sanitizeInput($_POST['action'] ?? $_GET['action'] ?? '');
$id = validateInt($_GET['id'] ?? $_POST['id'] ?? 0) ?? 0;
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token for POST requests
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $flash = 'Security token validation failed. Please try again.';
        logSecurityEvent('csrf_validation_failed', ['action' => $action]);
    } else {
    if ($action === 'create') {
        $ok = createEmailAccount($_POST);
        $flash = $ok ? 'Account created.' : 'Failed to create account.';
    } elseif ($action === 'update' && $id > 0) {
        $ok = updateEmailAccount($id, $_POST);
        $flash = $ok ? 'Account updated.' : 'Failed to update account.';
    } elseif ($action === 'delete' && $id > 0) {
        $ok = deleteEmailAccount($id);
        $flash = $ok ? 'Account deleted.' : 'Failed to delete account.';
    } elseif ($action === 'test' && $id > 0) {
        $acc = getEmailAccountById($id);
        $flash = $acc ? testImapConnection($acc) : 'Account not found';
    }
    }
}

$editing = ($action === 'edit' && $id > 0) ? getEmailAccountById($id) : null;
$accounts = listEmailAccounts();
?>

<div class="p-6 glass-surface rounded">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Email Accounts</h1>
    <a href="<?php echo ($_SERVER['PHP_SELF']); ?>" class="text-sm text-indigo-300 hover:text-white">Refresh</a>
  </div>
  <?php if ($flash): ?>
  <div class="glass-card p-3 rounded mt-3 text-sm"><?php echo htmlspecialchars($flash); ?></div>
  <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <div class="glass-card p-4 rounded">
      <h2 class="text-lg font-semibold mb-3"><?php echo $editing ? 'Edit Account' : 'Add Account'; ?></h2>
      <form method="post" class="space-y-3">
        <?php echo csrfTokenField(); ?>
        <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>" />
        <?php if ($editing): ?><input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>" /><?php endif; ?>
        <div>
          <label class="text-sm">Label</label>
          <input class="glass-input w-full rounded px-3 py-2" type="text" name="label" value="<?php echo htmlspecialchars($editing['label'] ?? ''); ?>" required />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-sm">IMAP Host</label>
            <input class="glass-input w-full rounded px-3 py-2" type="text" name="imap_host" value="<?php echo htmlspecialchars($editing['imap_host'] ?? ''); ?>" required />
          </div>
          <div>
            <label class="text-sm">IMAP Port</label>
            <input class="glass-input w-full rounded px-3 py-2" type="number" name="imap_port" value="<?php echo htmlspecialchars($editing['imap_port'] ?? '993'); ?>" />
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-sm">SMTP Host</label>
            <input class="glass-input w-full rounded px-3 py-2" type="text" name="smtp_host" value="<?php echo htmlspecialchars($editing['smtp_host'] ?? ''); ?>" required />
          </div>
          <div>
            <label class="text-sm">SMTP Port</label>
            <input class="glass-input w-full rounded px-3 py-2" type="number" name="smtp_port" value="<?php echo htmlspecialchars($editing['smtp_port'] ?? '587'); ?>" />
          </div>
        </div>
        <div>
          <label class="text-sm">Email Address</label>
          <input class="glass-input w-full rounded px-3 py-2" type="email" name="email" value="<?php echo htmlspecialchars($editing['email'] ?? ''); ?>" required />
        </div>
        <div>
          <label class="text-sm">Password</label>
          <input class="glass-input w-full rounded px-3 py-2" type="password" name="password" value="<?php echo htmlspecialchars($editing['password'] ?? ''); ?>" required />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <label class="text-sm flex items-center space-x-2"><input type="checkbox" name="imap_ssl" value="1" <?php echo (isset($editing['imap_ssl']) ? ((int)$editing['imap_ssl']===1?'checked':'') : 'checked'); ?> /> <span>IMAP SSL</span></label>
          <label class="text-sm flex items-center space-x-2"><input type="checkbox" name="smtp_tls" value="1" <?php echo (isset($editing['smtp_tls']) ? ((int)$editing['smtp_tls']===1?'checked':'') : 'checked'); ?> /> <span>SMTP TLS</span></label>
        </div>
        <div class="flex items-center space-x-2">
          <button class="glass-card px-4 py-2 rounded" type="submit"><?php echo $editing ? 'Update' : 'Add'; ?></button>
          <?php if ($editing): ?>
            <a class="text-sm text-indigo-300 hover:text-white" href="email_accounts.php">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="glass-card p-4 rounded">
      <h2 class="text-lg font-semibold mb-3">Accounts</h2>
      <div class="space-y-2">
        <?php foreach ($accounts as $acc): ?>
          <div class="flex items-center justify-between p-3 rounded glass-surface">
            <div>
              <div class="font-semibold text-sm"><?php echo htmlspecialchars($acc['label']); ?></div>
              <div class="text-xs text-gray-300"><?php echo htmlspecialchars($acc['email']); ?> · IMAP: <?php echo htmlspecialchars($acc['imap_host']); ?> · SMTP: <?php echo htmlspecialchars($acc['smtp_host']); ?></div>
            </div>
            <div class="flex items-center space-x-2 text-sm">
              <a class="glass-card px-2 py-1 rounded" href="email_accounts.php?action=edit&id=<?php echo (int)$acc['id']; ?>">Edit</a>
              <form method="post" onsubmit="return confirm('Delete this account?');">
                <?php echo csrfTokenField(); ?>
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="id" value="<?php echo (int)$acc['id']; ?>" />
                <button class="glass-card px-2 py-1 rounded" type="submit">Delete</button>
              </form>
              <form method="post">
                <?php echo csrfTokenField(); ?>
                <input type="hidden" name="action" value="test" />
                <input type="hidden" name="id" value="<?php echo (int)$acc['id']; ?>" />
                <button class="glass-card px-2 py-1 rounded" type="submit">Test IMAP</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (count($accounts) === 0): ?>
          <div class="text-sm text-gray-300">No accounts yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="glass-card p-4 rounded mt-6 text-sm">
    <h3 class="font-semibold mb-2">Login issues? Read this</h3>
    <ul class="list-disc ml-5 space-y-1 text-gray-300">
      <li>For Google/Gmail or Google Workspace, enable 2‑Step Verification and create an App Password. Use that app password here.</li>
      <li>IMAP must be enabled in the mailbox settings (e.g., Gmail Settings → Forwarding and POP/IMAP).</li>
      <li>Too many login failures can trigger a temporary lock. Wait a few minutes before retrying.</li>
      <li>Use the correct IMAP host/port and SSL option (e.g., imap.gmail.com:993 with SSL).</li>
      <li>For Microsoft 365 or other providers with modern auth, use an app password or configure OAuth2 (not covered here).</li>
    </ul>
  </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>


