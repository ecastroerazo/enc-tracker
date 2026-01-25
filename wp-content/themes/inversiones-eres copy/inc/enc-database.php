<?php
/**
 * ENC Tracker Database Setup and Helper Functions
 */

// Setup database tables on theme activation
function enc_tracker_setup() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $stores_table = $wpdb->prefix . 'enc_stores';
    $income_table = $wpdb->prefix . 'enc_incomes';
    $withdrawals_table = $wpdb->prefix . 'enc_withdrawals';

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

    dbDelta($sql_stores);
    dbDelta($sql_incomes);
    dbDelta($sql_withdrawals);

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

}
add_action('after_switch_theme', 'enc_tracker_setup');