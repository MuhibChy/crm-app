<?php
// Basic HTML header template with TailwindCSS and Alpine.js
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM App - Professional Email Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.0/dist/tailwind.min.css" rel="stylesheet">
    <link href="<?php echo ($basePath === '' ? '' : $basePath) . '/assets/app.css'; ?>" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <script defer src="<?php echo ($basePath === '' ? '' : $basePath) . '/assets/app.js'; ?>"></script>
    <meta name="description" content="Professional CRM application with AI-powered email management">
    <meta name="keywords" content="CRM, email management, AI, customer relationship management">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📧</text></svg>">
    <style>
        /* Fallback styles in case CDN fails */
        .max-w-7xl { max-width: 80rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .items-center { align-items: center; }
        .h-16 { height: 4rem; }
        .text-lg { font-size: 1.125rem; }
        .font-semibold { font-weight: 600; }
        .space-x-4 > * + * { margin-left: 1rem; }
        .text-sm { font-size: 0.875rem; }
        .mt-6 { margin-top: 1.5rem; }
        .p-6 { padding: 1.5rem; }
        .rounded { border-radius: 0.375rem; }
        .text-2xl { font-size: 1.5rem; }
        .font-bold { font-weight: 700; }
        .grid { display: grid; }
        .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .gap-4 { gap: 1rem; }
        .mt-4 { margin-top: 1rem; }
        .p-4 { padding: 1rem; }
        @media (min-width: 640px) {
            .sm\\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .lg\\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
    </style>
</head>
<body class="text-gray-100">
    <nav class="glass-nav">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">📧</span>
                    </div>
                    <div class="text-lg font-semibold text-slate-100">CRM Pro</div>
                </div>
                <div class="hidden md:flex space-x-6 text-sm">
                    <a href="<?php echo ($basePath === '' ? '' : $basePath) . '/dashboard.php'; ?>" class="text-slate-300 hover:text-white transition-colors flex items-center space-x-1">
                        <span>📊</span><span>Dashboard</span>
                    </a>
                    <a href="<?php echo ($basePath === '' ? '' : $basePath) . '/email-center.php'; ?>" class="text-slate-300 hover:text-white transition-colors flex items-center space-x-1">
                        <span>📧</span><span>Email Center</span>
                    </a>
                    <a href="<?php echo ($basePath === '' ? '' : $basePath) . '/email_accounts.php'; ?>" class="text-slate-300 hover:text-white transition-colors flex items-center space-x-1">
                        <span>⚙️</span><span>Accounts</span>
                    </a>
                    <a href="<?php echo ($basePath === '' ? '' : $basePath) . '/custom_email.php'; ?>" class="text-slate-300 hover:text-white transition-colors flex items-center space-x-1">
                        <span>📬</span><span>Inbox</span>
                    </a>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button class="text-slate-300 hover:text-white p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto mt-6 content-container">

