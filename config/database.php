<?php
/**
 * Database Configuration
 * Learnrail Web App - Direct DB access
 */

// Database credentials - same as API since on same server
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'rivoteli_learnrail');
define('DB_USER', getenv('DB_USER') ?: 'rivoteli_learnrai');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');
