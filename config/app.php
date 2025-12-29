<?php
/**
 * Application Configuration
 * Learnrail Web App
 */

// Error reporting - TEMPORARY: Display errors to debug 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);  // TEMPORARY: Enable to see errors
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/error.log');

// Timezone
date_default_timezone_set('UTC');

// Application settings
define('APP_NAME', 'Learnrail');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production');
define('APP_DEBUG', false);
define('APP_URL', 'https://app.learnrail.org');

// API Configuration
define('API_BASE_URL', 'https://api.learnrail.org/api');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('SRC_PATH', ROOT_PATH . '/src');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Session Configuration
define('SESSION_NAME', 'learnrail_session');
define('SESSION_LIFETIME', 86400 * 7); // 7 days

// Cookie Configuration
define('COOKIE_DOMAIN', '.learnrail.org');
define('COOKIE_SECURE', true);
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Lax');

// CSRF Token
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper functions
function csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_field(): string {
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

function verify_csrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function back(): void {
    $referer = $_SERVER['HTTP_REFERER'] ?? '/';
    redirect($referer);
}

function old(string $key, string $default = ''): string {
    return htmlspecialchars($_SESSION['old_input'][$key] ?? $default, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, $value = null) {
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
    } else {
        $flash = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
}

function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function asset(string $path): string {
    return '/' . ltrim($path, '/');
}

function url(string $path = ''): string {
    return APP_URL . '/' . ltrim($path, '/');
}

function format_date(string $date, string $format = 'M d, Y'): string {
    return date($format, strtotime($date));
}

function format_currency(float $amount, string $currency = 'NGN'): string {
    $symbols = ['NGN' => '₦', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];
    $symbol = $symbols[$currency] ?? $currency . ' ';
    return $symbol . number_format($amount, 2);
}

function format_duration(int $minutes): string {
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . 'h ' . ($mins > 0 ? $mins . 'm' : '');
}

function time_ago(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';

    return date('M d, Y', $time);
}
