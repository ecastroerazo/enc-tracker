<?php
/**
 * ENC Tracker Helper Functions
 */

if (!function_exists('enc_money')) {
    function enc_money($n) {
        return number_format((float) $n, 2, '.', ',');
    }
}

if (!function_exists('enc_parse_date')) {
    function enc_parse_date($value, $fallback) {
        $value = sanitize_text_field($value ?? '');
        return (!$value || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) ? $fallback : $value;
    }
}

if (!function_exists('enc_get_stores')) {
    function enc_get_stores() {
        global $wpdb;
        return $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}enc_stores WHERE is_active = 1 ORDER BY name ASC");
    }
}

if (!function_exists('enc_get_companies')) {
    function enc_get_companies($args = []) {
        global $wpdb;

        $defaults = [
            'include_inactive' => false,
            'order' => 'ASC',
        ];
        $args = wp_parse_args($args, $defaults);

        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        $where = $args['include_inactive'] ? '1=1' : 'is_active = 1';

        $sql = "SELECT id, name, contact_person, phone, is_active FROM {$wpdb->prefix}enc_companies WHERE $where ORDER BY name $order";

        return $wpdb->get_results($sql);
    }
}

if (!function_exists('enc_get_invoice_statuses')) {
    function enc_get_invoice_statuses() {
        $statuses = [
            'pending'  => __('Pending', 'enc'),
            'paid'     => __('Paid', 'enc'),
            'overdue'  => __('Overdue', 'enc'),
            'cancelled'=> __('Cancelled', 'enc'),
        ];

        return apply_filters('enc_invoice_statuses', $statuses);
    }
}

if (!function_exists('enc_stream_csv')) {
    function enc_stream_csv($filename, array $headers, array $rows) {
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}
