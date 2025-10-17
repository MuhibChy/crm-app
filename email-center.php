<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');
    
    switch ($action) {
        case 'send_email':
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                $error = 'Security validation failed';
                break;
            }
            
            $to = sanitizeInput($_POST['to'] ?? '');
            $subject = sanitizeInput($_POST['subject'] ?? '');
            $body = sanitizeInput($_POST['body'] ?? '');
            $account_id = validateInt($_POST['account_id'] ?? 0);
            
            if (!validateEmail($to)) {
                $error = 'Invalid recipient email address';
                break;
            }
            
            if (empty($subject) || empty($body)) {
                $error = 'Subject and message body are required';
                break;
            }
            
            $account = getEmailAccountById($account_id);
            if (!$account) {
                $error = 'Invalid email account selected';
                break;
            }
            
            $result = smtpSendSimple($account, $to, $subject, $body);
            if ($result) {
                $message = 'Email sent successfully!';
                // Log the sent email
                logEmailActivity($account_id, 'sent', $to, $subject);
            } else {
                $error = 'Failed to send email: ' . smtpLastError();
            }
            break;
            
        case 'test_connection':
            $account_id = validateInt($_POST['test_account_id'] ?? 0);
            $account = getEmailAccountById($account_id);
            
            if (!$account) {
                $error = 'Invalid email account';
                break;
            }
            
            $imap_test = testImapConnection($account);
            $smtp_test = testSmtpConnection($account);
            
            if ($imap_test && $smtp_test) {
                $message = 'Connection test successful! Both IMAP and SMTP are working.';
            } else {
                $error = 'Connection test failed. ';
                if (!$imap_test) $error .= 'IMAP connection failed. ';
                if (!$smtp_test) $error .= 'SMTP connection failed.';
            }
            break;
    }
}

// Get email accounts
$email_accounts = getAllEmailAccounts();
$recent_emails = getRecentEmails(10);
?>

<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header Section -->
    <div class="glass-surface">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-slate-100 mb-2">📧 Email Center</h1>
                <p class="text-slate-300">Send, receive, and manage emails from multiple domains</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="refreshEmails()" class="glass-card px-4 py-2 rounded-lg hover:scale-105 transition-all duration-200 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Refresh</span>
                </button>
                <button onclick="syncAllAccounts()" class="glass-card px-4 py-2 rounded-lg hover:scale-105 transition-all duration-200 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Sync All</span>
                </button>
            </div>
        </div>

        <!-- Status Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success mb-4">
                <strong>Success!</strong> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error mb-4">
                <strong>Error!</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Compose Email -->
        <div class="lg:col-span-2">
            <div class="glass-surface">
                <h2 class="text-xl font-semibold text-slate-100 mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                    </svg>
                    Compose Email
                </h2>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="send_email">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">From Account</label>
                            <select name="account_id" required class="glass-input w-full rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select email account...</option>
                                <?php foreach ($email_accounts as $account): ?>
                                    <option value="<?= $account['id'] ?>">
                                        <?= htmlspecialchars($account['label'] ?? $account['email']) ?> (<?= htmlspecialchars($account['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">To</label>
                            <input type="email" name="to" required class="glass-input w-full rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="recipient@domain.com">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Subject</label>
                        <input type="text" name="subject" required class="glass-input w-full rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Email subject">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Message</label>
                        <textarea name="body" required rows="8" class="glass-input w-full rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Type your message here..."></textarea>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path>
                            </svg>
                            <span>Send Email</span>
                        </button>
                        
                        <button type="button" onclick="draftEmail()" class="glass-card px-6 py-3 rounded-lg font-medium hover:scale-105 transition-all duration-200">
                            💾 Save Draft
                        </button>
                        
                        <button type="button" onclick="getAISuggestion()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 flex items-center space-x-2">
                            <span>🤖</span>
                            <span>AI Assist</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Right Column: Account Status & Quick Actions -->
        <div class="space-y-6">
            
            <!-- Email Accounts Status -->
            <div class="glass-surface">
                <h3 class="text-lg font-semibold text-slate-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                    </svg>
                    Email Accounts
                </h3>
                
                <div class="space-y-3">
                    <?php if (empty($email_accounts)): ?>
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 text-slate-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 4a3 3 0 00-3 3v6a3 3 0 003 3h10a3 3 0 003-3V7a3 3 0 00-3-3H5zm-1 9v-1h5v2H5a1 1 0 01-1-1zm7 1h4a1 1 0 001-1v-1h-5v2zm0-4h5V8h-5v2zM9 8H4v2h5V8z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-slate-400 mb-4">No email accounts configured</p>
                            <a href="email_accounts.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                                Add Account
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($email_accounts as $account): ?>
                            <div class="glass-card p-4 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <h4 class="font-medium text-slate-100"><?= htmlspecialchars($account['label'] ?? 'Email Account') ?></h4>
                                        <p class="text-sm text-slate-400"><?= htmlspecialchars($account['email']) ?></p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="w-2 h-2 bg-green-400 rounded-full" title="Connected"></span>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="test_connection">
                                            <input type="hidden" name="test_account_id" value="<?= $account['id'] ?>">
                                            <button type="submit" class="text-xs text-slate-400 hover:text-slate-200" title="Test Connection">
                                                🔍
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="text-xs text-slate-400">
                                    IMAP: <?= htmlspecialchars($account['imap_host']) ?>:<?= htmlspecialchars($account['imap_port']) ?> • 
                                    SMTP: <?= htmlspecialchars($account['smtp_host']) ?>:<?= htmlspecialchars($account['smtp_port']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4 pt-4 border-t border-slate-600">
                    <a href="email_accounts.php" class="block text-center glass-card py-2 rounded-lg hover:scale-105 transition-all duration-200">
                        ⚙️ Manage Accounts
                    </a>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="glass-surface">
                <h3 class="text-lg font-semibold text-slate-100 mb-4">📊 Quick Stats</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-400" id="emails-today"><?= getEmailCountToday() ?></div>
                        <div class="text-xs text-slate-400">Today</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400" id="emails-sent"><?= getSentEmailsCount() ?></div>
                        <div class="text-xs text-slate-400">Sent</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-400" id="pending-emails"><?= getPendingEmailsCount() ?></div>
                        <div class="text-xs text-slate-400">Pending</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-400" id="ai-suggestions"><?= getAISuggestionsCount() ?></div>
                        <div class="text-xs text-slate-400">AI Helps</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Emails Section -->
    <div class="glass-surface">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-slate-100">📬 Recent Emails</h2>
            <div class="flex space-x-2">
                <button onclick="fetchEmails()" class="glass-card px-3 py-1 rounded text-sm hover:scale-105 transition-all duration-200">
                    📥 Fetch New
                </button>
                <a href="custom_email.php" class="glass-card px-3 py-1 rounded text-sm hover:scale-105 transition-all duration-200">
                    📋 View All
                </a>
            </div>
        </div>
        
        <div class="overflow-hidden rounded-lg">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-800 bg-opacity-50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">From</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-600">
                    <?php if (empty($recent_emails)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                No emails found. Configure email accounts to start receiving emails.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_emails as $email): ?>
                            <tr class="hover:bg-slate-800 hover:bg-opacity-30 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 bg-blue-400 rounded-full mr-3"></div>
                                        <div>
                                            <div class="text-sm font-medium text-slate-200"><?= htmlspecialchars($email['sender']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-slate-200"><?= htmlspecialchars(substr($email['subject'] ?? 'No Subject', 0, 50)) ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        <?= $email['status'] === 'New' ? 'bg-blue-100 text-blue-800' : 
                                           ($email['status'] === 'In Progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') ?>">
                                        <?= htmlspecialchars($email['status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-400">
                                    <?= date('M j, H:i', strtotime($email['created_at'])) ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <button onclick="replyToEmail(<?= $email['id'] ?>)" class="text-blue-400 hover:text-blue-300 text-xs">Reply</button>
                                        <button onclick="getAIResponse(<?= $email['id'] ?>)" class="text-purple-400 hover:text-purple-300 text-xs">🤖 AI</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Email Center JavaScript Functions
function refreshEmails() {
    location.reload();
}

function syncAllAccounts() {
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '🔄 Syncing...';
    button.disabled = true;
    
    // Simulate sync process
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
        location.reload();
    }, 2000);
}

function fetchEmails() {
    // Fetch new emails from all accounts
    fetch('email_sync.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=fetch_all'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to fetch emails: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching emails');
    });
}

function draftEmail() {
    // Save current form as draft
    const form = document.querySelector('form');
    const formData = new FormData(form);
    formData.set('action', 'save_draft');
    
    fetch('email_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Draft saved successfully!');
        } else {
            alert('Failed to save draft: ' + data.error);
        }
    });
}

function getAISuggestion() {
    const subject = document.querySelector('input[name="subject"]').value;
    const body = document.querySelector('textarea[name="body"]').value;
    
    if (!subject && !body) {
        alert('Please enter a subject or message to get AI suggestions');
        return;
    }
    
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '🤖 Thinking...';
    button.disabled = true;
    
    fetch('ai_suggest.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
    })
    .then(response => response.json())
    .then(data => {
        button.innerHTML = originalText;
        button.disabled = false;
        
        if (data.success) {
            // Show AI suggestion in a modal or replace content
            if (confirm('AI Suggestion:\n\n' + data.suggestion + '\n\nWould you like to use this suggestion?')) {
                if (data.suggested_subject) {
                    document.querySelector('input[name="subject"]').value = data.suggested_subject;
                }
                if (data.suggested_body) {
                    document.querySelector('textarea[name="body"]').value = data.suggested_body;
                }
            }
        } else {
            alert('AI suggestion failed: ' + data.error);
        }
    })
    .catch(error => {
        button.innerHTML = originalText;
        button.disabled = false;
        console.error('Error:', error);
        alert('An error occurred while getting AI suggestion');
    });
}

function replyToEmail(emailId) {
    // Load email content for reply
    window.location.href = `custom_email_message.php?id=${emailId}&action=reply`;
}

function getAIResponse(emailId) {
    // Get AI-generated response for specific email
    fetch('ai_response.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `email_id=${emailId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('AI Response:\n\n' + data.response);
        } else {
            alert('AI response failed: ' + data.error);
        }
    });
}

// Auto-refresh stats every 30 seconds
setInterval(() => {
    fetch('email_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('emails-today').textContent = data.today;
                document.getElementById('emails-sent').textContent = data.sent;
                document.getElementById('pending-emails').textContent = data.pending;
                document.getElementById('ai-suggestions').textContent = data.ai_helps;
            }
        })
        .catch(error => console.log('Stats update failed:', error));
}, 30000);
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
