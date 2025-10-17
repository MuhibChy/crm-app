<?php require_once __DIR__ . '/templates/header.php'; ?>
<?php require_once __DIR__ . '/functions.php'; ?>
<?php $accounts = function_exists('listEmailAccounts') ? listEmailAccounts() : []; ?>

<div class="p-6 glass-surface rounded">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Outlook Access</h1>
    <a href="<?php echo ($_SERVER['PHP_SELF']); ?>" class="text-sm text-indigo-300 hover:text-white">Refresh</a>
  </div>

  <div class="glass-card p-4 rounded mt-4 text-sm">
    <p class="text-gray-300">Quick actions to use your locally configured Outlook. For full in-browser CRUD of Outlook mailboxes, integrate Microsoft Graph (OAuth) — see guidance below.</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
    <?php foreach ($accounts as $acc): ?>
      <div class="glass-card p-4 rounded">
        <div class="font-semibold"><?php echo htmlspecialchars($acc['label'] ?? ($acc['email'] ?? 'Account')); ?></div>
        <div class="text-xs text-gray-300 mb-3"><?php echo htmlspecialchars($acc['email'] ?? ''); ?></div>
        <div class="flex flex-wrap items-center gap-2 text-sm">
          <a class="glass-card px-3 py-2 rounded" href="mailto:?subject=New%20Message&body=">Compose (default client)</a>
          <a class="glass-card px-3 py-2 rounded" href="https://outlook.office.com/mail/" target="_blank" rel="noopener">Open Outlook Web</a>
          <a class="glass-card px-3 py-2 rounded" href="outlook:" title="Open Outlook desktop app">Open Outlook app</a>
          <a class="glass-card px-3 py-2 rounded" href="outlook://Inbox" title="Open Inbox in Outlook">Inbox (app)</a>
          <a class="glass-card px-3 py-2 rounded" href="outlook://Drafts" title="Open Drafts in Outlook">Drafts (app)</a>
          <a class="glass-card px-3 py-2 rounded" href="outlook://Sent%20Items" title="Open Sent Items in Outlook">Sent (app)</a>
          <button class="glass-card px-3 py-2 rounded" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($acc['email'] ?? '', ENT_QUOTES); ?>');">Copy Address</button>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (count($accounts) === 0): ?>
      <div class="glass-card p-4 rounded text-sm text-gray-300">No accounts found. Add some in Email Accounts first.</div>
    <?php endif; ?>
  </div>

  <div class="glass-card p-4 rounded mt-6 text-sm">
    <h2 class="text-lg font-semibold mb-2">Full Outlook CRUD (recommended approach)</h2>
    <ol class="list-decimal ml-5 space-y-1 text-gray-300">
      <li>Register an Azure AD app with permissions: Mail.Read, Mail.ReadWrite, Mail.Send (delegated).</li>
      <li>Implement OAuth 2.0 authorization code flow to sign in each mailbox user.</li>
      <li>Use Microsoft Graph endpoints to list/create/update/delete messages.</li>
      <li>Optionally, subscribe to webhooks for new mail notifications.</li>
    </ol>
    <p class="mt-2">If you want, I can add a basic Microsoft Graph OAuth setup here.</p>
  </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>


