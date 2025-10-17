<?php require_once __DIR__ . '/templates/header.php'; ?>
<?php require_once __DIR__ . '/config.php'; ?>
<div class="p-6 glass-surface rounded">
  <h2 class="text-xl font-bold mb-4">Admin Panel</h2>
  <form class="space-y-3">
    <div>
      <label class="text-sm">Email Domain</label>
      <input type="text" class="glass-input p-2 w-full rounded" placeholder="e.g. quote@broadinsurance.co.uk" />
    </div>
    <div>
      <label class="text-sm">Password</label>
      <input type="password" class="glass-input p-2 w-full rounded" />
    </div>
    <button class="glass-card px-4 py-2 rounded">Save</button>
  </form>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
