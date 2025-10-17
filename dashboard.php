<?php require_once __DIR__ . '/templates/header.php'; ?>
<?php require_once __DIR__ . '/functions.php'; ?>

<div class="p-6 glass-surface rounded animate-fadeInUp">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold text-gray-100">CRM Dashboard</h1>
    <div class="text-sm text-gray-300">
      <?= date('F j, Y - g:i A'); ?>
    </div>
  </div>
  
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
    <div class="glass-card p-6 rounded-lg hover:scale-105 transition-transform duration-200">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-300 mb-1">Total Emails Today</p>
          <p class="text-2xl font-bold text-blue-400"><?= getEmailCountToday(); ?></p>
        </div>
        <div class="text-blue-400 opacity-60">
          <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
          </svg>
        </div>
      </div>
    </div>
    
    <div class="glass-card p-6 rounded-lg hover:scale-105 transition-transform duration-200">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-300 mb-1">Pending Follow-ups</p>
          <p class="text-2xl font-bold text-yellow-400"><?= getPendingFollowups(); ?></p>
        </div>
        <div class="text-yellow-400 opacity-60">
          <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
          </svg>
        </div>
      </div>
    </div>
    
    <div class="glass-card p-6 rounded-lg hover:scale-105 transition-transform duration-200">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-300 mb-1">Agent Activity</p>
          <p class="text-2xl font-bold text-green-400"><?= getAgentSummary(); ?></p>
        </div>
        <div class="text-green-400 opacity-60">
          <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
      </div>
    </div>
    
    <div class="glass-card p-6 rounded-lg hover:scale-105 transition-transform duration-200">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-300 mb-1">SLA Countdown</p>
          <p class="text-2xl font-bold text-red-400"><?= getSLACountdown(); ?></p>
        </div>
        <div class="text-red-400 opacity-60">
          <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"></path>
          </svg>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Actions Section -->
  <div class="mt-8">
    <h2 class="text-xl font-semibold text-gray-100 mb-4">Quick Actions</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <a href="email_accounts.php" class="glass-card p-4 rounded-lg hover:scale-105 transition-transform duration-200 block">
        <div class="flex items-center space-x-3">
          <div class="text-indigo-400">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
              <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
              <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-100">Manage Email Accounts</p>
            <p class="text-sm text-gray-300">Configure IMAP/SMTP settings</p>
          </div>
        </div>
      </a>
      
      <a href="custom_email.php" class="glass-card p-4 rounded-lg hover:scale-105 transition-transform duration-200 block">
        <div class="flex items-center space-x-3">
          <div class="text-green-400">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-100">Send Custom Email</p>
            <p class="text-sm text-gray-300">Compose and send emails</p>
          </div>
        </div>
      </a>
      
      <a href="admin.php" class="glass-card p-4 rounded-lg hover:scale-105 transition-transform duration-200 block">
        <div class="flex items-center space-x-3">
          <div class="text-purple-400">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-100">Admin Panel</p>
            <p class="text-sm text-gray-300">System configuration</p>
          </div>
        </div>
      </a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
