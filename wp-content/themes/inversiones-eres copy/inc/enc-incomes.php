<?php
/**
 * ENC Tracker Incomes Module
 */

if (!function_exists('enc_render_report_filters')) {
    function enc_render_report_filters($args = []) {
        $defaults = [
            'tab' => '',
            'stores' => [],
            'selected_store' => 0,
            'date_from' => '',
            'date_to' => '',
            'button_label' => 'Filter',
            'clear_url' => '',
            'show_clear' => true,
            'export_url' => '',
            'export_label' => 'Export CSV',
        ];
        $args = wp_parse_args($args, $defaults);

        ob_start();
        ?>
        <form method="get" class="mb-6 p-4 bg-gray-50 rounded-lg">
            <input type="hidden" name="tab" value="<?php echo esc_attr($args['tab']); ?>" />
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Store</label>
                    <select name="store_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Stores</option>
                        <?php foreach ($args['stores'] as $store): ?>
                            <option value="<?php echo esc_attr($store->id); ?>" <?php selected($store->id, $args['selected_store']); ?>>
                                <?php echo esc_html($store->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($args['date_from']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($args['date_to']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-200"><?php echo esc_html($args['button_label']); ?></button>
                    <?php if ($args['show_clear'] && !empty($args['clear_url'])): ?>
                        <a href="<?php echo esc_url($args['clear_url']); ?>" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-100 transition duration-200">Clear</a>
                    <?php endif; ?>
                    <?php if (!empty($args['export_url'])): ?>
                        <a href="<?php echo esc_url($args['export_url']); ?>" class="inline-flex items-center justify-center px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-900 transition duration-200">
                            <?php echo esc_html($args['export_label']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }
}

add_action('template_redirect', 'enc_handle_income_export');
function enc_handle_income_export() {
    if (!isset($_GET['enc_export']) || $_GET['enc_export'] !== 'incomes') {
        return;
    }

    if (!current_user_can('enc_view_reports') && !current_user_can('manage_options')) {
        wp_die('You do not have permission to export income reports.');
    }

    global $wpdb;
    $store_id = intval($_GET['store_id'] ?? 0);
    $date_from = enc_parse_date($_GET['date_from'] ?? '', current_time('Y-m-01'));
    $date_to = enc_parse_date($_GET['date_to'] ?? '', current_time('Y-m-d'));

    $where = "WHERE 1=1";
    $params = [];
    if ($store_id > 0) {
        $where .= " AND i.store_id = %d";
        $params[] = $store_id;
    }
    $where .= " AND i.entry_date BETWEEN %s AND %s";
    $params[] = $date_from;
    $params[] = $date_to;

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT i.entry_date, s.name as store_name, i.amount, i.notes FROM {$wpdb->prefix}enc_incomes i 
         JOIN {$wpdb->prefix}enc_stores s ON i.store_id = s.id 
         $where ORDER BY i.entry_date DESC",
        $params
    ));

    $rows = array_map(function ($row) {
        return [
            $row->entry_date,
            $row->store_name,
            $row->amount,
            $row->notes,
        ];
    }, $results);

    enc_stream_csv('enc-incomes-' . date('Ymd') . '.csv', ['Date', 'Store', 'Amount', 'Notes'], $rows);
}

// Incomes view
function enc_incomes_view() {
    // Check if we're in edit mode
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        return enc_income_edit_shortcode(['id' => intval($_GET['id'])]);
    }

    global $wpdb;
    
    // Success/error messages
    $messages = '';
    if (isset($_GET['deleted'])) {
        $messages .= '<div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-md">Income deleted successfully!</div>';
    } elseif (isset($_GET['updated'])) {
        $messages .= '<div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-md">Income updated successfully!</div>';
    } elseif (isset($_GET['success'])) {
        $messages .= '<div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-md">Income recorded successfully!</div>';
    } elseif (isset($_GET['error'])) {
        $error_code = intval($_GET['error']);
        $error_messages = [
            1 => 'Please select a valid store and enter a valid amount.',
            2 => 'Failed to save income. Please try again.'
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-md">' . esc_html($message) . '</div>';
    }

    $store_id = intval($_GET['store_id'] ?? 0);
    $date_from = enc_parse_date($_GET['date_from'] ?? '', current_time('Y-m-01'));
    $date_to = enc_parse_date($_GET['date_to'] ?? '', current_time('Y-m-d'));
    $stores = enc_get_stores();

    $where = "WHERE 1=1";
    $params = [];
    if ($store_id > 0) {
        $where .= " AND i.store_id = %d";
        $params[] = $store_id;
    }
    $where .= " AND i.entry_date BETWEEN %s AND %s";
    $params[] = $date_from;
    $params[] = $date_to;

        $base_income_query = "SELECT i.*, s.name as store_name FROM {$wpdb->prefix}enc_incomes i 
            JOIN {$wpdb->prefix}enc_stores s ON i.store_id = s.id 
            $where ORDER BY i.entry_date DESC, i.id DESC";

    $incomes = $wpdb->get_results($wpdb->prepare($base_income_query . ' LIMIT 50', $params));
    $running_totals = [];
    $running_sum = 0;
    $ascending_incomes = $incomes;
    usort($ascending_incomes, function ($a, $b) {
        if ($a->entry_date === $b->entry_date) {
            return $a->id <=> $b->id;
        }
        return strcmp($a->entry_date, $b->entry_date);
    });
    foreach ($ascending_incomes as $income_item) {
        $running_sum += (float) $income_item->amount;
        $running_totals[$income_item->id] = $running_sum;
    }

    $filter_clear_url = remove_query_arg(
        ['store_id', 'date_from', 'date_to', 'paged', 'enc_export'],
        add_query_arg('tab', 'incomes', get_permalink())
    );

    $export_url = '';
    if (current_user_can('enc_view_reports') || current_user_can('manage_options')) {
        $export_url = add_query_arg(
            array_filter([
                'tab' => 'incomes',
                'store_id' => $store_id ?: null,
                'date_from' => $date_from ?: null,
                'date_to' => $date_to ?: null,
                'enc_export' => 'incomes',
            ]),
            get_permalink()
        );
    }

    ob_start();
    ?>
    <?php echo $messages; ?>
    <h2 class="text-2xl font-bold mb-4">Income Reports</h2>
    
    <?php
    echo enc_render_report_filters([
        'tab' => 'incomes',
        'stores' => $stores,
        'selected_store' => $store_id,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'clear_url' => $filter_clear_url,
        'export_url' => $export_url,
    ]);
    ?>

    <!-- Results -->
    <?php if (empty($incomes)): ?>
        <p class="text-center py-8 text-gray-500">No income records found for the selected criteria.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse bg-white rounded-lg shadow-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 border-b">Date</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 border-b">Store</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-700 border-b">Amount</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-700 border-b">Cumulative Total</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 border-b">Notes</th>
                        <?php if (current_user_can('manage_options')): ?>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-700 border-b">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incomes as $income): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 border-b"><?php echo esc_html($income->entry_date); ?></td>
                            <td class="px-4 py-3 border-b"><?php echo esc_html($income->store_name); ?></td>
                            <td class="px-4 py-3 text-right border-b">$<?php echo enc_money($income->amount); ?></td>
                            <td class="px-4 py-3 text-right border-b">$<?php echo enc_money($running_totals[$income->id] ?? $income->amount); ?></td>
                            <td class="px-4 py-3 border-b"><?php echo esc_html($income->notes); ?></td>
                            <?php if (current_user_can('manage_options')): ?>
                                <?php
                                $edit_url = add_query_arg(['tab' => 'incomes', 'action' => 'edit', 'id' => $income->id], get_permalink());
                                $delete_url = add_query_arg(['tab' => 'incomes', 'action' => 'delete', 'id' => $income->id, 'nonce' => wp_create_nonce('delete_income_'.$income->id)], get_permalink());
                                ?>
                                <td class="px-4 py-3 text-center border-b">
                                    <a href="<?php echo esc_url($edit_url); ?>" class="text-blue-600 hover:text-blue-800 mr-3">Edit</a>
                                    <a href="<?php echo esc_url($delete_url); ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this income record?')">Delete</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="mt-4 text-sm text-gray-600">
            Total: $<?php echo enc_money(array_sum(array_column($incomes, 'amount'))); ?>
        </p>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}

// Income form handling
add_action('template_redirect', 'enc_handle_income_forms');
function enc_handle_income_forms() {
    // Income update submission
    if (isset($_POST['enc_income_update'])) {
        if (!is_user_logged_in() || !current_user_can('manage_options')) return;
        if (!wp_verify_nonce($_POST['enc_income_nonce'], 'enc_income_update')) wp_die('Security check failed.');

        global $wpdb;
        $income_id = intval($_POST['income_id'] ?? 0);
        $store_id = intval($_POST['store_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $date = sanitize_text_field($_POST['entry_date'] ?? current_time('Y-m-d'));
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $valid_store = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}enc_stores WHERE id=%d AND is_active=1", $store_id));
        $current_url = strtok((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '?');

        if (!$valid_store || $amount <= 0 || $income_id <= 0) {
            wp_redirect($current_url . '?tab=incomes&error=1');
            exit;
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'enc_incomes',
            [
                'store_id' => $store_id,
                'amount' => $amount,
                'entry_date' => $date,
                'notes' => $notes
            ],
            ['id' => $income_id],
            ['%d', '%f', '%s', '%s'],
            ['%d']
        );

        if ($result !== false) {
            wp_redirect($current_url . '?tab=incomes&updated=1');
        } else {
            wp_redirect($current_url . '?tab=incomes&error=2');
        }
        exit;
    }
    
    // Income form submission
    if (isset($_POST['enc_income_submit'])) {
        if (!is_user_logged_in() || !current_user_can('enc_add_income')) return;
        if (!wp_verify_nonce($_POST['enc_income_nonce'], 'enc_income_submit')) wp_die('Security check failed.');

        global $wpdb;
        $store_id = intval($_POST['store_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $date = sanitize_text_field($_POST['entry_date'] ?? current_time('Y-m-d'));
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $valid_store = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}enc_stores WHERE id=%d AND is_active=1", $store_id));
        $current_url = strtok((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '?');

        if (!$valid_store || $amount <= 0) {
            wp_redirect($current_url . '?tab=incomes&error=1');
            exit;
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'enc_incomes',
            [
                'store_id' => $store_id,
                'amount' => $amount,
                'entry_date' => $date,
                'notes' => $notes,
                'created_by' => get_current_user_id()
            ],
            ['%d', '%f', '%s', '%s', '%d']
        );

        if ($result) {
            wp_redirect($current_url . '?tab=incomes&success=1');
        } else {
            wp_redirect($current_url . '?tab=incomes&error=2');
        }
        exit;
    }

    // Delete income
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && $_GET['tab'] === 'incomes') {
        if (!current_user_can('manage_options')) return;
        
        $income_id = intval($_GET['id']);
        $nonce = sanitize_text_field($_GET['nonce'] ?? '');
        
        if (!wp_verify_nonce($nonce, 'delete_income_' . $income_id)) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'enc_incomes', ['id' => $income_id], ['%d']);
        
        wp_redirect(add_query_arg(['tab' => 'incomes', 'deleted' => '1'], remove_query_arg(['action', 'id', 'nonce'])));
        exit;
    }
}

// Income edit shortcode [enc_income_edit]
add_shortcode('enc_income_edit', 'enc_income_edit_shortcode');
function enc_income_edit_shortcode($atts) {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return '<p>Access denied.</p>';
    }

    $atts = shortcode_atts(['id' => 0], $atts);
    $income_id = intval($atts['id']) ?: intval($_GET['id'] ?? 0);
    
    if (!$income_id) {
        return '<p>Invalid income ID.</p>';
    }

    global $wpdb;
    $income = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}enc_incomes WHERE id = %d", 
        $income_id
    ));

    if (!$income) {
        return '<p>Income record not found.</p>';
    }

    $stores = enc_get_stores();
    if (empty($stores)) {
        return '<p>No stores available. Please contact administrator.</p>';
    }

    // Success/error messages
    $messages = '';
    if (isset($_GET['updated'])) {
        $messages .= '<div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-md">Income updated successfully!</div>';
    } elseif (isset($_GET['error'])) {
        $error_code = intval($_GET['error']);
        $error_messages = [
            1 => 'Please select a valid store and enter a valid amount.',
            2 => 'Failed to update income. Please try again.'
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-md">' . esc_html($message) . '</div>';
    }

    $back_url = remove_query_arg(['action', 'id']);

    ob_start();
    ?>
    <?php echo $messages; ?>
    <div class="mb-4">
        <a href="<?php echo esc_url($back_url); ?>" class="text-blue-600 hover:text-blue-800">← Back to Income Reports</a>
    </div>
    
    <h2 class="text-2xl font-bold mb-4">Edit Income Record</h2>
    
    <form method="post" class="max-w-lg p-6 border border-gray-300 rounded-lg bg-white shadow-sm">
        <?php wp_nonce_field('enc_income_update', 'enc_income_nonce'); ?>
        <input type="hidden" name="income_id" value="<?php echo esc_attr($income->id); ?>">
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Store *</label>
            <select name="store_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select Store</option>
                <?php foreach ($stores as $store): ?>
                    <option value="<?php echo esc_attr($store->id); ?>" <?php selected($store->id, $income->store_id); ?>>
                        <?php echo esc_html($store->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
            <input type="date" name="entry_date" value="<?php echo esc_attr($income->entry_date); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Amount *</label>
            <input type="number" name="amount" step="0.01" min="0.01" value="<?php echo esc_attr($income->amount); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"><?php echo esc_textarea($income->notes); ?></textarea>
        </div>
        
        <div class="mt-6 flex gap-3">
            <button type="submit" name="enc_income_update" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">Update Income</button>
            <a href="<?php echo esc_url($back_url); ?>" class="flex-1 text-center bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded-md transition duration-200">Cancel</a>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

// Income submission shortcode [enc_income_form]
add_shortcode('enc_income_form', 'enc_income_form_shortcode');
function enc_income_form_shortcode() {
    if (!is_user_logged_in() || !current_user_can('enc_add_income')) {
        return '<p>Access denied.</p>';
    }

    $stores = enc_get_stores();
    if (empty($stores)) {
        return '<p>No stores available. Please contact administrator.</p>';
    }

    // Success/error messages
    $messages = '';
    if (isset($_GET['success'])) {
        $messages .= '<div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-md">Income recorded successfully!</div>';
    } elseif (isset($_GET['error'])) {
        $error_code = intval($_GET['error']);
        $error_messages = [
            1 => 'Please select a valid store and enter a valid amount.',
            2 => 'Failed to save income. Please try again.'
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-md">' . esc_html($message) . '</div>';
    }

    ob_start();
    ?>
    <?php echo $messages; ?>
    <form method="post" class="max-w-lg p-6 border border-gray-300 rounded-lg bg-white shadow-sm">
        <?php wp_nonce_field('enc_income_submit', 'enc_income_nonce'); ?>
        
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
            <input type="date" name="entry_date" value="<?php echo current_time('Y-m-d'); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Amount *</label>
            <input type="number" name="amount" step="0.01" min="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
        </div>
        
        <div class="mt-6">
            <button type="submit" name="enc_income_submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">Submit Income</button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}