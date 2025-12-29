<?php
/**
 * Learnrail Web Application
 * Front Controller
 */

// Start output buffering to prevent any early output breaking HTML
ob_start();

// Load configuration
require_once __DIR__ . '/../config/app.php';

// Autoloader for src classes
spl_autoload_register(function ($class) {
    $prefix = '';
    $baseDir = SRC_PATH . '/';

    $relativeClass = $class;
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize authentication
$auth = new Core\Auth();

// Initialize API client with auth token
$api = new Core\ApiClient($auth->getToken());

// Make globally available
$GLOBALS['auth'] = $auth;
$GLOBALS['api'] = $api;

// Load and dispatch routes
$router = new Core\Router();
require_once ROOT_PATH . '/config/routes.php';
$router->dispatch();
