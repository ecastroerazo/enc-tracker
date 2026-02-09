<?php
/**
 * Main Theme Functions File
 * 
 * This file loads all modular function files from the inc/ directory
 * to keep the codebase organized and maintainable.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Include all modular function files
 */
$modules = [
    'enqueue.php',           // Scripts and styles enqueuing
    'enc-database.php',      // ENC Tracker database setup
    'enc-helpers.php',       // ENC Tracker helper functions
    'enc-permissions.php',   // ENC Tracker user permissions
    'enc-dashboard.php',     // ENC Tracker dashboard functionality
    'enc-incomes.php',       // ENC Tracker incomes module
    'enc-invoices.php',      // ENC Tracker invoices module
    'enc-withdrawals.php',   // ENC Tracker withdrawals module
    'enc-shortcodes.php',    // ENC Tracker shortcodes
];

foreach ($modules as $module) {
    $file_path = get_template_directory() . '/inc/' . $module;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}