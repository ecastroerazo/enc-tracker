<?php
/**
 * ENC Tracker Database Setup and Helper Functions
 */

if (!defined('ENC_TRACKER_DB_VERSION')) {
    define('ENC_TRACKER_DB_VERSION', '20260208');
}

// Setup database tables on theme activation
function enc_tracker_setup() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $stores_table = $wpdb->prefix . 'enc_stores';
    $income_table = $wpdb->prefix . 'enc_incomes';
    $withdrawals_table = $wpdb->prefix . 'enc_withdrawals';
    $companies_table = $wpdb->prefix . 'enc_companies';
    $invoices_table = $wpdb->prefix . 'enc_invoices';

    // Create tables
    $sql_stores = "CREATE TABLE $stores_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        slug VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug_unique (slug)
    ) $charset_collate;";

    $sql_incomes = "CREATE TABLE $income_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        store_id BIGINT UNSIGNED NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        entry_date DATE NOT NULL,
        notes TEXT NULL,
        created_by BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY store_date (store_id, entry_date)
    ) $charset_collate;";

    $sql_withdrawals = "CREATE TABLE $withdrawals_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        store_id BIGINT UNSIGNED NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        withdrawal_date DATE NOT NULL,
        category VARCHAR(120) NULL,
        notes TEXT NULL,
        created_by BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY store_date (store_id, withdrawal_date)
    ) $charset_collate;";

    $sql_companies = "CREATE TABLE $companies_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255) NULL,
        phone VARCHAR(50) NULL,
        notes TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_by BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY active_name (is_active, name)
    ) $charset_collate;";

    $sql_invoices = "CREATE TABLE $invoices_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        store_id BIGINT UNSIGNED NOT NULL,
        company_id BIGINT UNSIGNED NULL,
        invoice_number VARCHAR(100) NOT NULL,
        client_name VARCHAR(255) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        invoice_date DATE NOT NULL,
        due_date DATE NOT NULL,
        status ENUM('pending', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'pending',
        description TEXT NULL,
        notes TEXT NULL,
        created_by BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY store_invoice_number (store_id, invoice_number),
        KEY store_id (store_id),
        KEY store_status (store_id, status),
        KEY company_id (company_id),
        KEY due_date (due_date)
    ) $charset_collate;";



    dbDelta($sql_stores);
    dbDelta($sql_incomes);
    dbDelta($sql_withdrawals);
    dbDelta($sql_companies);
    dbDelta($sql_invoices);

    // Add default stores
    $stores = [
        ['slug' => 'eres', 'name' => 'ERES Internacional'],
        ['slug' => 'ce', 'name' => 'CE Fashion'],
        ['slug' => 'nova', 'name' => 'NOVA Fashion'],
    ];

    foreach ($stores as $store) {
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $stores_table WHERE slug = %s", $store['slug']));
        if (!$exists) {
            $wpdb->insert($stores_table, $store + ['is_active' => 1], ['%s','%s','%d']);
        }
    }

    update_option('enc_tracker_db_version', ENC_TRACKER_DB_VERSION);
}

function enc_tracker_maybe_upgrade() {
    $current_version = get_option('enc_tracker_db_version');
    if ($current_version !== ENC_TRACKER_DB_VERSION) {
        enc_tracker_setup();
    }
}

add_action('after_switch_theme', 'enc_tracker_setup');
add_action('init', 'enc_tracker_maybe_upgrade');
add_action('admin_init', 'enc_tracker_maybe_upgrade');