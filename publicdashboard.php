<?php require_once __DIR__ . '/templates/header.php'; ?>
<?php require_once __DIR__ . '/functions.php'; ?>
<div class="p-6 glass-surface rounded">
  <h1 class="text-2xl font-bold">CRM Dashboard</h1>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
    <div class="glass-card p-4 rounded">Total Emails Today: <?= getEmailCountToday(); ?></div>
    <div class="glass-card p-4 rounded">Pending Follow-ups: <?= getPendingFollowups(); ?></div>
    <div class="glass-card p-4 rounded">Agent Activity: <?= getAgentSummary(); ?></div>
    <div class="glass-card p-4 rounded">SLA Countdown: <?= getSLACountdown(); ?></div>
  </div>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
