<?php
// Basic HTML header template with TailwindCSS and Alpine.js
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM App</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="<?php echo ($basePath === '' ? '' : $basePath) . '/assets/app.css'; ?>" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="<?php echo ($basePath === '' ? '' : $basePath) . '/assets/app.js'; ?>"></script>
</head>
<body class="text-gray-100">
    <nav class="glass-nav">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16 items-center">
                <div class="text-lg font-semibold">CRM App</div>
                <div class="space-x-4 text-sm">
                    <a href="<?php echo ($basePath === '' ? '' : $basePath) . '/admin.php'; ?>" class="text-indigo-300 hover:text-white">Admin</a>
                    <a href="<?php echo ($basePath === '' ? '' : $basePath) . '/dashboard.php'; ?>" class="text-indigo-300 hover:text-white">Dashboard</a>
                    <a href="<?php echo ($basePath === '' ? '' : $basePath) . '/email_accounts.php'; ?>" class="text-indigo-300 hover:text-white">Email Accounts</a>
                    <a href="<?php echo ($basePath === '' ? '' : $basePath) . '/custom_email.php'; ?>" class="text-indigo-300 hover:text-white">Custom Email</a>
                </div>
            </div>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto mt-6 content-container">

