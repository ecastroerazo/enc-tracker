<?php
/**
 * ENC Tracker User Permissions
 */

// Setup user capabilities
function enc_setup_capabilities() {
    // Add capabilities to admin
    $admin = get_role('administrator');
    if ($admin) {
        $admin->add_cap('enc_add_income');
        $admin->add_cap('enc_manage_withdrawals');
        $admin->add_cap('enc_view_reports');
        $admin->add_cap('enc_manage_invoices');
        $admin->add_cap('enc_manage_companies');
    }
    
    // Add limited capabilities to employees (only income submission)
    $employee = get_role('editor'); // Using 'editor' role as employee role
    if ($employee) {
        $employee->add_cap('enc_add_income');
    }
}

//add_action('init', 'enc_setup_capabilities');