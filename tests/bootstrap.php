<?php
/**
 * PHPUnit Bootstrap File
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Tests;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Load Composer autoloader.
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Load plugin.
require_once __DIR__ . '/../wp-api-codeia.php';

// Define test constants.
define('WP_API_CODEIA_TESTING', true);

// Initialize plugin (will be done in each test setup if needed).

echo "WP API Codeia Test Suite Loaded\n";
