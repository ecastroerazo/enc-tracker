<?php
/**
 * ENC Tracker Withdrawals Module
 */

add_action('template_redirect', 'enc_handle_withdrawal_export');
function enc_handle_withdrawal_export() {
    if (!isset($_GET['enc_export']) || $_GET['enc_export'] !== 'withdrawals') {
        return;
    }

    if (!current_user_can('enc_manage_withdrawals') && !current_user_can('manage_options')) {
        wp_die('You do not have permission to export withdrawal reports.');
    }

    global $wpdb;
    $store_id = intval($_GET['store_id'] ?? 0);
    $date_from = enc_parse_date($_GET['date_from'] ?? '', current_time('Y-m-01'));
    $date_to = enc_parse_date($_GET['date_to'] ?? '', current_time('Y-m-d'));

    $where = "WHERE 1=1";
    $params = [];
    if ($store_id > 0) {
        $where .= " AND w.store_id = %d";
        $params[] = $store_id;
    }
    $where .= " AND w.withdrawal_date BETWEEN %s AND %s";
    $params[] = $date_from;
    $params[] = $date_to;

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT w.withdrawal_date, s.name as store_name, w.amount, w.category, w.notes FROM {$wpdb->prefix}enc_withdrawals w 
         JOIN {$wpdb->prefix}enc_stores s ON w.store_id = s.id 
         $where ORDER BY w.withdrawal_date DESC, w.id DESC",
        $params
    ));

    $rows = array_map(function ($row) {
        return [
            $row->withdrawal_date,
            $row->store_name,
            $row->amount,
            $row->category,
            $row->notes,
        ];
    }, $results);

    enc_stream_csv('enc-withdrawals-' . date('Ymd') . '.csv', ['Date', 'Store', 'Amount', 'Category', 'Notes'], $rows);
}

// Withdrawals view
function enc_withdrawals_view() {
    // Check if we're in edit mode
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        return enc_withdrawal_edit_shortcode(['id' => intval($_GET['id'])]);
    }

    global $wpdb;
    
    if (!current_user_can('enc_manage_withdrawals') && !current_user_can('manage_options')) {
        return '<p>Access denied.</p>';
    }
    
    $out = '';
    
    // Success/error messages
    if (isset($_GET['deleted'])) {
        $out .= '<div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-md">Withdrawal deleted successfully!</div>';
    } elseif (isset($_GET['updated'])) {
        $out .= '<div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-md">Withdrawal updated successfully!</div>';
    } elseif (isset($_GET['success'])) {
        $out .= '<div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-md">Withdrawal recorded successfully!</div>';
    } elseif (isset($_GET['error'])) {
        $error_code = intval($_GET['error']);
        $error_messages = [
            1 => 'Please select a valid store and enter a valid amount.',
            2 => 'Failed to save withdrawal. Please try again.'
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $out .= '<div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-md">' . esc_html($message) . '</div>';
    }

    $store_id = intval($_GET['store_id'] ?? 0);
    $date_from = enc_parse_date($_GET['date_from'] ?? '', current_time('Y-m-01'));
    $date_to = enc_parse_date($_GET['date_to'] ?? '', current_time('Y-m-d'));
    $stores = enc_get_stores();

    $where = "WHERE 1=1";
    $params = [];
    if ($store_id > 0) {
        $where .= " AND w.store_id = %d";
        $params[] = $store_id;
    }
    $where .= " AND w.withdrawal_date BETWEEN %s AND %s";
    $params[] = $date_from;
    $params[] = $date_to;

    // Get withdrawals
        $base_withdrawal_query = "SELECT w.*, s.name as store_name FROM {$wpdb->prefix}enc_withdrawals w 
            JOIN {$wpdb->prefix}enc_stores s ON w.store_id = s.id 
            $where ORDER BY w.withdrawal_date DESC, w.id DESC";

    $withdrawals = $wpdb->get_results($wpdb->prepare($base_withdrawal_query . ' LIMIT 50', $params));
    
    $filter_clear_url = remove_query_arg(
        ['store_id', 'date_from', 'date_to', 'paged', 'enc_export'],
        add_query_arg('tab', 'withdrawals', get_permalink())
    );
    
    $export_url = '';
    if (current_user_can('enc_manage_withdrawals') || current_user_can('manage_options')) {
        $export_url = add_query_arg(
            array_filter([
                'tab' => 'withdrawals',
                'store_id' => $store_id ?: null,
                'date_from' => $date_from ?: null,
                'date_to' => $date_to ?: null,
                'enc_export' => 'withdrawals',
            ]),
            get_permalink()
        );
    }
    
    $out .= '<h2 class="text-2xl font-bold mb-4">Withdrawal Reports</h2>';
    
    $out .= enc_render_report_filters([
        'tab' => 'withdrawals',
        'stores' => $stores,
        'selected_store' => $store_id,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'clear_url' => $filter_clear_url,
        'export_url' => $export_url,
    ]);
    
    // Results
    
    if (empty($withdrawals)) {
        $out .= '<p class="text-center py-8 text-gray-500">No withdrawal records found for the selected criteria.</p>';
    } else {
        $out .= '<div class="overflow-x-auto"><table class="w-full border-collapse bg-white rounded-lg shadow-sm">';
        $out .= '<thead class="bg-gray-50"><tr>';
        $out .= '<th class="px-4 py-3 text-left text-sm font-medium text-gray-700 border-b">Date</th>';
        $out .= '<th class="px-4 py-3 text-left text-sm font-medium text-gray-700 border-b">Store</th>';
        $out .= '<th class="px-4 py-3 text-right text-sm font-medium text-gray-700 border-b">Amount</th>';
        $out .= '<th class="px-4 py-3 text-left text-sm font-medium text-gray-700 border-b">Category</th>';
        $out .= '<th class="px-4 py-3 text-left text-sm font-medium text-gray-700 border-b">Notes</th>';
        if (current_user_can('manage_options')) {
            $out .= '<th class="px-4 py-3 text-center text-sm font-medium text-gray-700 border-b">Actions</th>';
        }
        $out .= '</tr></thead><tbody>';
        
        foreach ($withdrawals as $withdrawal) {
            $out .= '<tr class="hover:bg-gray-50">';
            $out .= '<td class="px-4 py-3 border-b">'.esc_html($withdrawal->withdrawal_date).'</td>';
            $out .= '<td class="px-4 py-3 border-b">'.esc_html($withdrawal->store_name).'</td>';
            $out .= '<td class="px-4 py-3 text-right border-b">$'.enc_money($withdrawal->amount).'</td>';
            $out .= '<td class="px-4 py-3 border-b">'.esc_html($withdrawal->category).'</td>';
            $out .= '<td class="px-4 py-3 border-b">'.esc_html($withdrawal->notes).'</td>';
            if (current_user_can('manage_options')) {
                $edit_url = add_query_arg(['tab' => 'withdrawals', 'action' => 'edit', 'id' => $withdrawal->id], get_permalink());
                $delete_url = add_query_arg(['tab' => 'withdrawals', 'action' => 'delete', 'id' => $withdrawal->id, 'nonce' => wp_create_nonce('delete_withdrawal_'.$withdrawal->id)], get_permalink());
                $out .= '<td class="px-4 py-3 text-center border-b">';
                $out .= '<a href="'.esc_url($edit_url).'" class="text-blue-600 hover:text-blue-800 mr-3">Edit</a>';
                $out .= '<a href="'.esc_url($delete_url).'" class="text-red-600 hover:text-red-800" onclick="return confirm(\'Are you sure you want to delete this withdrawal record?\')">Delete</a>';
                $out .= '</td>';
            }
            $out .= '</tr>';
        }
        
        $out .= '</tbody></table></div>';
        $out .= '<p class="mt-4 text-sm text-gray-600">';
        $out .= 'Total: $' . enc_money(array_sum(array_column($withdrawals, 'amount')));
        $out .= '</p>';
    }

    if (current_user_can('enc_manage_withdrawals') || current_user_can('manage_options')) {
        $out .= '<div class="mt-10">';
        $out .= '<h3 class="text-xl font-semibold mb-4">Submit Withdrawal</h3>';
        $out .= enc_withdrawal_form_shortcode();
        $out .= '</div>';
    }
    
    return $out;
}

// Withdrawal form handling
add_action('template_redirect', 'enc_handle_withdrawal_forms');
function enc_handle_withdrawal_forms() {
    // Withdrawal update submission
    if (isset($_POST['enc_withdrawal_update'])) {
        if (!current_user_can('manage_options')) return;
        if (!wp_verify_nonce($_POST['enc_withdrawal_nonce'], 'enc_withdrawal_update')) wp_die('Security check failed.');

        global $wpdb;
        $withdrawal_id = intval($_POST['withdrawal_id'] ?? 0);
        $store_id = intval($_POST['store_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $date = sanitize_text_field($_POST['withdrawal_date'] ?? current_time('Y-m-d'));
        $category = sanitize_text_field($_POST['category'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $valid_store = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}enc_stores WHERE id=%d AND is_active=1", $store_id));
        $current_url = strtok((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '?');

        if (!$valid_store || $amount <= 0 || $withdrawal_id <= 0) {
            wp_redirect($current_url . '?tab=withdrawals&error=1');
            exit;
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'enc_withdrawals',
            [
                'store_id' => $store_id,
                'amount' => $amount,
                'withdrawal_date' => $date,
                'category' => $category,
                'notes' => $notes
            ],
            ['id' => $withdrawal_id],
            ['%d', '%f', '%s', '%s', '%s'],
            ['%d']
        );

        if ($result !== false) {
            wp_redirect($current_url . '?tab=withdrawals&updated=1');
        } else {
            wp_redirect($current_url . '?tab=withdrawals&error=2');
        }
        exit;
    }
    
    // Withdrawal form submission
    if (isset($_POST['enc_withdrawal_submit'])) {
        if (!current_user_can('enc_manage_withdrawals') && !current_user_can('manage_options')) return;
        if (!wp_verify_nonce($_POST['enc_withdrawal_nonce'], 'enc_withdrawal_submit')) wp_die('Security check failed.');

        global $wpdb;
        $store_id = intval($_POST['store_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $date = sanitize_text_field($_POST['withdrawal_date'] ?? current_time('Y-m-d'));
        $category = sanitize_text_field($_POST['category'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $valid_store = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}enc_stores WHERE id=%d AND is_active=1", $store_id));
        $current_url = strtok((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '?');

        if (!$valid_store || $amount <= 0) {
            wp_redirect($current_url . '?tab=withdrawals&error=1');
            exit;
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'enc_withdrawals',
            [
                'store_id' => $store_id,
                'amount' => $amount,
                'withdrawal_date' => $date,
                'category' => $category,
                'notes' => $notes,
                'created_by' => get_current_user_id()
            ],
            ['%d', '%f', '%s', '%s', '%s', '%d']
        );

        if ($result) {
            wp_redirect($current_url . '?tab=withdrawals&success=1');
        } else {
            wp_redirect($current_url . '?tab=withdrawals&error=2');
        }
        exit;
    }

    // Delete withdrawal
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && $_GET['tab'] === 'withdrawals') {
        if (!current_user_can('manage_options')) return;
        
        $withdrawal_id = intval($_GET['id']);
        $nonce = sanitize_text_field($_GET['nonce'] ?? '');
        
        if (!wp_verify_nonce($nonce, 'delete_withdrawal_' . $withdrawal_id)) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'enc_withdrawals', ['id' => $withdrawal_id], ['%d']);
        
        wp_redirect(add_query_arg(['tab' => 'withdrawals', 'deleted' => '1'], remove_query_arg(['action', 'id', 'nonce'])));
        exit;
    }
}

// Withdrawal edit shortcode [enc_withdrawal_edit]
add_shortcode('enc_withdrawal_edit', 'enc_withdrawal_edit_shortcode');
function enc_withdrawal_edit_shortcode($atts) {
    if (!current_user_can('manage_options')) {
        return '<p>Access denied.</p>';
    }

    $atts = shortcode_atts(['id' => 0], $atts);
    $withdrawal_id = intval($atts['id']) ?: intval($_GET['id'] ?? 0);
    
    if (!$withdrawal_id) {
        return '<p>Invalid withdrawal ID.</p>';
    }

    global $wpdb;
    $withdrawal = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}enc_withdrawals WHERE id = %d", 
        $withdrawal_id
    ));

    if (!$withdrawal) {
        return '<p>Withdrawal record not found.</p>';
    }

    $stores = enc_get_stores();
    if (empty($stores)) {
        return '<p>No stores available. Please contact administrator.</p>';
    }

    // Success/error messages
    $messages = '';
    if (isset($_GET['updated'])) {
        $messages .= '<div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-md">Withdrawal updated successfully!</div>';
    } elseif (isset($_GET['error'])) {
        $error_code = intval($_GET['error']);
        $error_messages = [
            1 => 'Please select a valid store and enter a valid amount.',
            2 => 'Failed to update withdrawal. Please try again.'
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-md">' . esc_html($message) . '</div>';
    }

    $back_url = remove_query_arg(['action', 'id']);

    ob_start();
    ?>
    <?php echo $messages; ?>
    <div class="mb-4">
        <a href="<?php echo esc_url($back_url); ?>" class="text-blue-600 hover:text-blue-800">← Back to Withdrawal Reports</a>
    </div>
    
    <h2 class="text-2xl font-bold mb-4">Edit Withdrawal Record</h2>
    
    <form method="post" class="max-w-lg p-6 border border-gray-300 rounded-lg bg-white shadow-sm">
        <?php wp_nonce_field('enc_withdrawal_update', 'enc_withdrawal_nonce'); ?>
        <input type="hidden" name="withdrawal_id" value="<?php echo esc_attr($withdrawal->id); ?>">
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Store *</label>
            <select name="store_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select Store</option>
                <?php foreach ($stores as $store): ?>
                    <option value="<?php echo esc_attr($store->id); ?>" <?php selected($store->id, $withdrawal->store_id); ?>>
                        <?php echo esc_html($store->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
            <input type="date" name="withdrawal_date" value="<?php echo esc_attr($withdrawal->withdrawal_date); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Amount *</label>
            <input type="number" name="amount" step="0.01" min="0.01" value="<?php echo esc_attr($withdrawal->amount); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <input type="text" name="category" value="<?php echo esc_attr($withdrawal->category); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"><?php echo esc_textarea($withdrawal->notes); ?></textarea>
        </div>
        
        <div class="mt-6 flex gap-3">
            <button type="submit" name="enc_withdrawal_update" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">Update Withdrawal</button>
            <a href="<?php echo esc_url($back_url); ?>" class="flex-1 text-center bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded-md transition duration-200">Cancel</a>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

// Withdrawal form shortcode [enc_withdrawal_form]  
add_shortcode('enc_withdrawal_form', 'enc_withdrawal_form_shortcode');
function enc_withdrawal_form_shortcode() {
    if (!current_user_can('enc_manage_withdrawals') && !current_user_can('manage_options')) {
        return '<p>Access denied.</p>';
    }

    $stores = enc_get_stores();
    if (empty($stores)) {
        return '<p>No stores available. Please contact administrator.</p>';
    }

    // Success/error messages
    $messages = '';
    if (isset($_GET['success'])) {
        $messages .= '<div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-md">Withdrawal recorded successfully!</div>';
    } elseif (isset($_GET['error'])) {
        $error_code = intval($_GET['error']);
        $error_messages = [
            1 => 'Please select a valid store and enter a valid amount.',
            2 => 'Failed to save withdrawal. Please try again.'
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-md">' . esc_html($message) . '</div>';
    }

    ob_start();
    ?>
    <?php echo $messages; ?>
    <form method="post" class="max-w-lg p-6 border border-gray-300 rounded-lg bg-white shadow-sm">
        <?php wp_nonce_field('enc_withdrawal_submit', 'enc_withdrawal_nonce'); ?>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Store *</label>
            <select name="store_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select Store</option>
                <?php foreach ($stores as $store): ?>
                    <option value="<?php echo esc_attr($store->id); ?>"><?php echo esc_html($store->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
            <input type="date" name="withdrawal_date" value="<?php echo current_time('Y-m-d'); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Amount *</label>
            <input type="number" name="amount" step="0.01" min="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <input type="text" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
        </div>
        
        <div class="mt-6">
            <button type="submit" name="enc_withdrawal_submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">Submit Withdrawal</button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}