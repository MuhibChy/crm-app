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

<div class="max-w-6xl mx-auto">
  <div class="glass-surface p-8 rounded-2xl">
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-slate-100 mb-2">📧 Email Account Management</h1>
      <p class="text-slate-300">Configure your email accounts to send and receive emails from multiple domains</p>
    </div>
    
    <?php if ($flash): ?>
    <div class="max-w-2xl mx-auto mb-6">
      <div class="glass-card p-4 rounded-lg text-center <?php echo strpos($flash, 'Failed') !== false ? 'border-l-4 border-red-500' : 'border-l-4 border-green-500'; ?>">
        <?php echo htmlspecialchars($flash); ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
    <div class="glass-card p-6 rounded-xl">
      <div class="text-center mb-6">
        <h2 class="text-xl font-semibold text-slate-100 mb-2">
          <?php echo $editing ? '✏️ Edit Email Account' : '➕ Add New Email Account'; ?>
        </h2>
        <p class="text-sm text-slate-400">Configure IMAP and SMTP settings for your email provider</p>
      </div>
      
      <form method="post" class="space-y-5">
        <?php echo csrfTokenField(); ?>
        <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>" />
        <?php if ($editing): ?><input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>" /><?php endif; ?>
        
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-2">Account Label</label>
          <input class="glass-input w-full rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                 type="text" name="label" 
                 value="<?php echo htmlspecialchars($editing['label'] ?? ''); ?>" 
                 placeholder="e.g., Main Business Email, Support Team" 
                 required />
          <p class="text-xs text-slate-400 mt-1">A friendly name to identify this account</p>
        </div>
        <!-- IMAP Settings -->
        <div class="bg-slate-800 bg-opacity-30 p-4 rounded-lg border border-slate-600">
          <h3 class="text-sm font-semibold text-slate-200 mb-3 flex items-center">
            <span class="mr-2">📥</span> IMAP Settings (Receiving Emails)
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">IMAP Host</label>
              <input class="glass-input w-full rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                     type="text" name="imap_host" 
                     value="<?php echo htmlspecialchars($editing['imap_host'] ?? ''); ?>" 
                     placeholder="imap.gmail.com" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">IMAP Port</label>
              <input class="glass-input w-full rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                     type="number" name="imap_port" 
                     value="<?php echo htmlspecialchars($editing['imap_port'] ?? '993'); ?>" 
                     placeholder="993" />
            </div>
          </div>
        </div>

        <!-- SMTP Settings -->
        <div class="bg-slate-800 bg-opacity-30 p-4 rounded-lg border border-slate-600">
          <h3 class="text-sm font-semibold text-slate-200 mb-3 flex items-center">
            <span class="mr-2">📤</span> SMTP Settings (Sending Emails)
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">SMTP Host</label>
              <input class="glass-input w-full rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                     type="text" name="smtp_host" 
                     value="<?php echo htmlspecialchars($editing['smtp_host'] ?? ''); ?>" 
                     placeholder="smtp.gmail.com" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">SMTP Port</label>
              <input class="glass-input w-full rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                     type="number" name="smtp_port" 
                     value="<?php echo htmlspecialchars($editing['smtp_port'] ?? '587'); ?>" 
                     placeholder="587" />
            </div>
          </div>
        </div>

        <!-- Credentials -->
        <div class="bg-slate-800 bg-opacity-30 p-4 rounded-lg border border-slate-600">
          <h3 class="text-sm font-semibold text-slate-200 mb-3 flex items-center">
            <span class="mr-2">🔐</span> Account Credentials
          </h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
              <input class="glass-input w-full rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                     type="email" name="email" 
                     value="<?php echo htmlspecialchars($editing['email'] ?? ''); ?>" 
                     placeholder="your-email@domain.com" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">Password / App Password</label>
              <input class="glass-input w-full rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                     type="password" name="password" 
                     value="<?php echo htmlspecialchars($editing['password'] ?? ''); ?>" 
                     placeholder="Enter your password or app password" required />
              <p class="text-xs text-slate-400 mt-1">For Gmail, use an App Password instead of your regular password</p>
            </div>
          </div>
        </div>

        <!-- Security Options -->
        <div class="bg-slate-800 bg-opacity-30 p-4 rounded-lg border border-slate-600">
          <h3 class="text-sm font-semibold text-slate-200 mb-3 flex items-center">
            <span class="mr-2">🔒</span> Security Options
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="flex items-center space-x-3 p-3 bg-slate-700 bg-opacity-50 rounded-lg cursor-pointer hover:bg-opacity-70 transition-colors">
              <input type="checkbox" name="imap_ssl" value="1" 
                     <?php echo (isset($editing['imap_ssl']) ? ((int)$editing['imap_ssl']===1?'checked':'') : 'checked'); ?> 
                     class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500" />
              <span class="text-sm text-slate-300">Enable IMAP SSL/TLS</span>
            </label>
            <label class="flex items-center space-x-3 p-3 bg-slate-700 bg-opacity-50 rounded-lg cursor-pointer hover:bg-opacity-70 transition-colors">
              <input type="checkbox" name="smtp_tls" value="1" 
                     <?php echo (isset($editing['smtp_tls']) ? ((int)$editing['smtp_tls']===1?'checked':'') : 'checked'); ?> 
                     class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500" />
              <span class="text-sm text-slate-300">Enable SMTP TLS</span>
            </label>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-center space-x-4 pt-4">
          <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center space-x-2" type="submit">
            <span><?php echo $editing ? '✏️' : '➕'; ?></span>
            <span><?php echo $editing ? 'Update Account' : 'Add Account'; ?></span>
          </button>
          <?php if ($editing): ?>
            <a class="glass-card px-6 py-3 rounded-lg font-medium hover:scale-105 transition-transform duration-200" href="email_accounts.php">
              Cancel
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="glass-card p-6 rounded-xl">
      <div class="text-center mb-6">
        <h2 class="text-xl font-semibold text-slate-100 mb-2">📋 Configured Accounts</h2>
        <p class="text-sm text-slate-400">Manage your existing email accounts</p>
      </div>
      
      <div class="space-y-4">
        <?php foreach ($accounts as $acc): ?>
          <div class="bg-slate-800 bg-opacity-40 p-4 rounded-lg border border-slate-600 hover:border-slate-500 transition-colors">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center space-x-2 mb-2">
                  <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                  <h3 class="font-semibold text-slate-100"><?php echo htmlspecialchars($acc['label']); ?></h3>
                </div>
                <p class="text-sm text-slate-300 mb-1"><?php echo htmlspecialchars($acc['email']); ?></p>
                <div class="text-xs text-slate-400 space-y-1">
                  <div>📥 IMAP: <?php echo htmlspecialchars($acc['imap_host']); ?>:<?php echo htmlspecialchars($acc['imap_port']); ?></div>
                  <div>📤 SMTP: <?php echo htmlspecialchars($acc['smtp_host']); ?>:<?php echo htmlspecialchars($acc['smtp_port']); ?></div>
                </div>
              </div>
              <div class="flex flex-col space-y-2 ml-4">
                <a class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors text-center" 
                   href="email_accounts.php?action=edit&id=<?php echo (int)$acc['id']; ?>">
                  ✏️ Edit
                </a>
                <form method="post" onsubmit="return confirm('Are you sure you want to delete this account?');" class="inline">
                  <?php echo csrfTokenField(); ?>
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="id" value="<?php echo (int)$acc['id']; ?>" />
                  <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors w-full" type="submit">
                    🗑️ Delete
                  </button>
                </form>
                <form method="post" class="inline">
                  <?php echo csrfTokenField(); ?>
                  <input type="hidden" name="action" value="test" />
                  <input type="hidden" name="id" value="<?php echo (int)$acc['id']; ?>" />
                  <button class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors w-full" type="submit">
                    🔍 Test
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        
        <?php if (count($accounts) === 0): ?>
          <div class="text-center py-8">
            <div class="text-6xl mb-4">📧</div>
            <p class="text-slate-400 mb-4">No email accounts configured yet</p>
            <p class="text-sm text-slate-500">Add your first email account to start sending and receiving emails</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Quick Setup Guide -->
  <div class="mt-8 glass-card p-6 rounded-xl">
    <div class="text-center mb-6">
      <h2 class="text-xl font-semibold text-slate-100 mb-2">🚀 Quick Setup Guide</h2>
      <p class="text-sm text-slate-400">Common email provider settings</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Gmail -->
      <div class="bg-slate-800 bg-opacity-40 p-4 rounded-lg border border-slate-600">
        <div class="flex items-center space-x-2 mb-3">
          <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center text-white font-bold text-sm">G</div>
          <h3 class="font-semibold text-slate-100">Gmail</h3>
        </div>
        <div class="text-xs text-slate-300 space-y-1">
          <div><strong>IMAP:</strong> imap.gmail.com:993 (SSL)</div>
          <div><strong>SMTP:</strong> smtp.gmail.com:587 (TLS)</div>
          <div><strong>Note:</strong> Use App Password, not regular password</div>
        </div>
      </div>
      
      <!-- Outlook -->
      <div class="bg-slate-800 bg-opacity-40 p-4 rounded-lg border border-slate-600">
        <div class="flex items-center space-x-2 mb-3">
          <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold text-sm">O</div>
          <h3 class="font-semibold text-slate-100">Outlook</h3>
        </div>
        <div class="text-xs text-slate-300 space-y-1">
          <div><strong>IMAP:</strong> outlook.office365.com:993 (SSL)</div>
          <div><strong>SMTP:</strong> smtp-mail.outlook.com:587 (TLS)</div>
          <div><strong>Note:</strong> Use your Microsoft account credentials</div>
        </div>
      </div>
      
      <!-- Yahoo -->
      <div class="bg-slate-800 bg-opacity-40 p-4 rounded-lg border border-slate-600">
        <div class="flex items-center space-x-2 mb-3">
          <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center text-white font-bold text-sm">Y</div>
          <h3 class="font-semibold text-slate-100">Yahoo</h3>
        </div>
        <div class="text-xs text-slate-300 space-y-1">
          <div><strong>IMAP:</strong> imap.mail.yahoo.com:993 (SSL)</div>
          <div><strong>SMTP:</strong> smtp.mail.yahoo.com:587 (TLS)</div>
          <div><strong>Note:</strong> Enable "Less secure app access"</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Troubleshooting -->
  <div class="mt-8 glass-card p-6 rounded-xl">
    <div class="text-center mb-6">
      <h2 class="text-xl font-semibold text-slate-100 mb-2">🔧 Troubleshooting</h2>
      <p class="text-sm text-slate-400">Common issues and solutions</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <h3 class="font-semibold text-slate-200 mb-3 flex items-center">
          <span class="mr-2">🔐</span> Authentication Issues
        </h3>
        <ul class="text-sm text-slate-300 space-y-2">
          <li class="flex items-start space-x-2">
            <span class="text-blue-400 mt-1">•</span>
            <span><strong>Gmail:</strong> Enable 2-Step Verification and create an App Password</span>
          </li>
          <li class="flex items-start space-x-2">
            <span class="text-blue-400 mt-1">•</span>
            <span><strong>Outlook:</strong> Use your Microsoft account password or App Password</span>
          </li>
          <li class="flex items-start space-x-2">
            <span class="text-blue-400 mt-1">•</span>
            <span>Enable IMAP in your email provider's settings</span>
          </li>
        </ul>
      </div>
      
      <div>
        <h3 class="font-semibold text-slate-200 mb-3 flex items-center">
          <span class="mr-2">⚙️</span> Connection Issues
        </h3>
        <ul class="text-sm text-slate-300 space-y-2">
          <li class="flex items-start space-x-2">
            <span class="text-green-400 mt-1">•</span>
            <span>Verify host names and port numbers are correct</span>
          </li>
          <li class="flex items-start space-x-2">
            <span class="text-green-400 mt-1">•</span>
            <span>Ensure SSL/TLS options match your provider's requirements</span>
          </li>
          <li class="flex items-start space-x-2">
            <span class="text-green-400 mt-1">•</span>
            <span>Check firewall settings on your server</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>


