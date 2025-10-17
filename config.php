<?php
// Load environment variables
require_once __DIR__ . '/env_loader.php';
loadEnv();

// Database Configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'crm_db'));

// Email Configuration
define('IMAP_HOST', env('IMAP_HOST', ''));
define('SMTP_HOST', env('SMTP_HOST', ''));
define('EMAIL_USER', env('EMAIL_USER', ''));
define('EMAIL_PASS', env('EMAIL_PASS', ''));

// OpenAI Configuration
define('OPENAI_API_KEY', env('OPENAI_API_KEY', ''));

// Google Sheets Configuration
define('GOOGLE_SHEET_ID', env('GOOGLE_SHEET_ID', ''));
define('GOOGLE_API_KEY', env('GOOGLE_API_KEY', ''));

// Microsoft Graph Configuration (OAuth - delegated)
define('GRAPH_CLIENT_ID', env('GRAPH_CLIENT_ID', ''));
define('GRAPH_CLIENT_SECRET', env('GRAPH_CLIENT_SECRET', ''));
define('GRAPH_TENANT', env('GRAPH_TENANT', 'common'));
define('GRAPH_REDIRECT_URI', env('GRAPH_REDIRECT_URI', env('APP_URL', 'http://localhost') . '/ms_callback.php'));

// Application Configuration
define('APP_URL', env('APP_URL', 'http://localhost/crm-app'));
define('APP_ENV', env('APP_ENV', 'development'));
?>




