<?php
// Autoload Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Load .env
$env_file = __DIR__ . '/../.env';
if (!file_exists($env_file)) {
    die('❌ .env file not found. Copy .env.example → .env and configure it.');
}

$env = parse_ini_file($env_file);
foreach ($env as $key => $value) {
    define($key, $value);
}

// Stripe Configuration
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// JSON DB Handler
require_once __DIR__ . '/db.php';

// Error Handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', DATA_PATH . '/log/php_errors.log');

// CORS Headers
header('Access-Control-Allow-Origin: ' . APP_URL);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize global DB instance
global $DB;
$DB = new JsonDB(DATA_PATH);
?>
