<?php
/**
 * ENC Tracker Withdrawals Module
 */

add_action('template_redirect', 'enc_handle_withdrawal_export');
function enc_handle_withdrawal_export()
{
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
function enc_withdrawals_view()
{
    // Check if we're in edit mode
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        return enc_withdrawal_edit_shortcode(['id' => intval($_GET['id'])]);
    }

    global $wpdb;

    if (!current_user_can('enc_manage_withdrawals') && !current_user_can('manage_options')) {
        return '<p>Access denied.</p>';
    }

    $base_withdrawals_url = add_query_arg('tab', 'withdrawals', get_permalink());
    $log_form_url = add_query_arg('view', 'log', $base_withdrawals_url);
    $show_form_view = isset($_GET['view']) && $_GET['view'] === 'log';

    $messages = '';
    if (isset($_GET['deleted'])) {
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Withdrawal deleted successfully!</div>';
    } elseif (isset($_GET['updated'])) {
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Withdrawal updated successfully!</div>';
    } elseif (isset($_GET['success'])) {
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Withdrawal recorded successfully!</div>';
    } elseif (isset($_GET['error'])) {
        $error_code = intval($_GET['error']);
        $error_messages = [
            1 => 'Please select a valid store and enter a valid amount.',
            2 => 'Failed to save withdrawal. Please try again.'
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">' . esc_html($message) . '</div>';
    }

    if ($show_form_view) {
        ob_start();
        ?>
        <div class="mx-auto max-w-3xl space-y-5">
            <?php if (!empty($messages)): ?>
                <div class="space-y-3"><?php echo $messages; ?></div>
            <?php endif; ?>

            <a href="<?php echo esc_url($base_withdrawals_url); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900">
                <span aria-hidden="true">←</span>
                <span><?php esc_html_e('Back to withdrawal reports', 'enc'); ?></span>
            </a>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400"><?php esc_html_e('Withdrawals', 'enc'); ?></p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900"><?php esc_html_e('Record new withdrawal', 'enc'); ?></h2>
                    <p class="mt-1 text-sm text-slate-600"><?php esc_html_e('Capture payouts, expenses, or adjustments with consistent categorization and notes.', 'enc'); ?></p>
                </div>

                <div>
                    <?php echo enc_withdrawal_form_shortcode([
                        'variant' => 'minimal',
                        'show_messages' => 'false',
                    ]); ?>
                </div>
            </section>
        </div>
        <?php
        return ob_get_clean();
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
    $record_count = count($withdrawals);
    $total_amount = array_sum(array_map(function ($withdrawal) {
        return (float) $withdrawal->amount;
    }, $withdrawals));
    $average_amount = $record_count > 0 ? $total_amount / $record_count : 0;
    $period_start_label = date_i18n('M j, Y', strtotime($date_from));
    $period_end_label = date_i18n('M j, Y', strtotime($date_to));

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

    ob_start();
    ?>
    <div
        class="space-y-6">
        <?php if (!empty($messages)): ?>
            <div
                class="space-y-3"><?php echo $messages; ?>
            </div>
        <?php endif; ?>

        <section class="py-2">
            <div class="flex flex-col gap-8 md:flex-row md:items-end md:justify-between">

                <div class="space-y-5">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Overview</p>
                        <h2 class="text-3xl font-bold tracking-tight text-slate-900">Withdrawal Reports</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Showing activity from
                            <span class="font-medium text-slate-700"><?php echo esc_html($period_start_label); ?></span>
                            to
                            <span class="font-medium text-slate-700"><?php echo esc_html($period_end_label); ?></span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-[12px] font-medium text-slate-600 shadow-sm">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                            </span>
                            <?php echo esc_html($record_count); ?>
                            entries
                        </div>

                        <a href="<?php echo esc_url($log_form_url); ?>" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition-all hover:bg-slate-800 hover:shadow-lg active:scale-95">
                            <span>Log Withdrawal</span>
                            <svg class="h-4 w-4 opacity-70" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14m-7-7l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="w-full md:w-72">
                    <div class="relative overflow-hidden rounded-2xl border border-rose-100 bg-white p-6 shadow-sm">
                        <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-rose-50 opacity-50"></div>

                        <div class="relative">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-rose-500">Total Outflow</p>
                            <div class="mt-1 flex items-baseline gap-1">
                                <span class="text-3xl font-bold text-slate-900">$<?php echo enc_money($total_amount); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <?php
        echo enc_render_report_filters([
            'tab' => 'withdrawals',
            'stores' => $stores,
            'selected_store' => $store_id,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'clear_url' => $filter_clear_url,
            'export_url' => $export_url,
        ]);
        ?>

        <?php if (empty($withdrawals)): ?>
            <div class="rounded-3xl border border-slate-100 bg-white px-6 py-12 text-center shadow-sm">
                <p class="text-base font-semibold text-slate-800">No withdrawal records found</p>
                <p class="mt-2 text-sm text-slate-500">Adjust your filters or record a withdrawal to see it listed here.</p>
            </div>
        <?php else: ?>
            <div class="rounded-3xl border border-slate-100 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Date</th>
                                <th class="px-4 py-3 text-left font-semibold">Store</th>
                                <th class="px-4 py-3 text-right font-semibold">Amount</th>
                                <th class="px-4 py-3 text-left font-semibold">Category</th>
                                <th class="px-4 py-3 text-left font-semibold">Notes</th>
                                <?php if (current_user_can('manage_options')): ?>
                                    <th class="px-4 py-3 text-center font-semibold">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100">
                            <?php foreach ($withdrawals as $withdrawal): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-4 font-medium text-slate-900"><?php echo esc_html(date_i18n('M j, Y', strtotime($withdrawal->withdrawal_date))); ?></td>
                                    <td class="px-4 py-4 text-slate-700"><?php echo esc_html($withdrawal->store_name); ?></td>
                                    <td class="px-4 py-4 text-right font-semibold text-rose-600">-$<?php echo enc_money($withdrawal->amount); ?></td>
                                    <td class="px-4 py-4 text-slate-600"><?php echo esc_html($withdrawal->category); ?></td>
                                    <td class="px-4 py-4 text-slate-500"><?php echo esc_html($withdrawal->notes); ?></td>
                                    <?php if (current_user_can('manage_options')): ?>
                                        <?php
                                        $edit_url = add_query_arg(['tab' => 'withdrawals', 'action' => 'edit', 'id' => $withdrawal->id], get_permalink());
                                        $delete_url = add_query_arg([
                                            'tab' => 'withdrawals',
                                            'action' => 'delete',
                                            'id' => $withdrawal->id,
                                            'nonce' => wp_create_nonce('delete_withdrawal_' . $withdrawal->id)
                                        ], get_permalink());
                                        ?>
                                        <td class="px-4 py-4 text-center">
                                            <div class="inline-flex items-center gap-2">
                                                <a href="<?php echo esc_url($edit_url); ?>" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">Edit</a>
                                                <a href="<?php echo esc_url($delete_url); ?>" class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-100" onclick="return confirm('Are you sure you want to delete this withdrawal record?')">Delete</a>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-6 py-4 text-sm text-slate-600">
                    <span class="font-semibold text-slate-900">$<?php echo enc_money($total_amount); ?></span>
                    total withdrawals in the current view.
                </div>
            </div>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}

// Withdrawal form handling
add_action('template_redirect', 'enc_handle_withdrawal_forms');
function enc_handle_withdrawal_forms()
{
    // Withdrawal update submission
    if (isset($_POST['enc_withdrawal_update'])) {
        if (!current_user_can('manage_options'))
            return;
        if (!wp_verify_nonce($_POST['enc_withdrawal_nonce'], 'enc_withdrawal_update'))
            wp_die('Security check failed.');

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
        if (!current_user_can('enc_manage_withdrawals') && !current_user_can('manage_options'))
            return;
        if (!wp_verify_nonce($_POST['enc_withdrawal_nonce'], 'enc_withdrawal_submit'))
            wp_die('Security check failed.');

        global $wpdb;
        $store_id = intval($_POST['store_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $date = sanitize_text_field($_POST['withdrawal_date'] ?? current_time('Y-m-d'));
        $category = sanitize_text_field($_POST['category'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $valid_store = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}enc_stores WHERE id=%d AND is_active=1", $store_id));
        $current_url = strtok((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '?');

        if (!$valid_store || $amount <= 0) {
            wp_redirect($current_url . '?tab=withdrawals&view=log&error=1');
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
            wp_redirect($current_url . '?tab=withdrawals&view=log&error=2');
        }
        exit;
    }

    // Delete withdrawal
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && $_GET['tab'] === 'withdrawals') {
        if (!current_user_can('manage_options'))
            return;

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
function enc_withdrawal_edit_shortcode($atts)
{
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
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Withdrawal updated successfully!</div>';
    } elseif (isset($_GET['error'])) {
        $error_code = intval($_GET['error']);
        $error_messages = [
            1 => 'Please select a valid store and enter a valid amount.',
            2 => 'Failed to update withdrawal. Please try again.'
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">' . esc_html($message) . '</div>';
    }

    $back_url = remove_query_arg(['action', 'id']);

    ob_start();
    ?>
    <div
        class="mx-auto max-w-2xl space-y-5">
        <?php if (!empty($messages)): ?>
            <div
                class="space-y-3"><?php echo $messages; ?>
            </div>
        <?php endif; ?>

        <a href="<?php echo esc_url($back_url); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900">
            <span aria-hidden="true">←</span>
            <span><?php esc_html_e('Back to withdrawal reports', 'enc'); ?></span>
        </a>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400"><?php esc_html_e('Withdrawal', 'enc'); ?></p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900"><?php esc_html_e('Edit withdrawal record', 'enc'); ?></h2>
                <p class="mt-1 text-sm text-slate-600"><?php esc_html_e('Adjust store, date, amount, category, or notes. Changes are logged for audit.', 'enc'); ?></p>
            </div>

            <form
                method="post" class="space-y-5">
                <?php wp_nonce_field('enc_withdrawal_update', 'enc_withdrawal_nonce'); ?>
                <input type="hidden" name="withdrawal_id" value="<?php echo esc_attr($withdrawal->id); ?>">

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Store *</label>
                    <select name="store_id" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value=""><?php esc_html_e('Select Store', 'enc'); ?></option>
                        <?php foreach ($stores as $store): ?>
                            <option
                                value="<?php echo esc_attr($store->id); ?>" <?php selected($store->id, $withdrawal->store_id); ?>><?php echo esc_html($store->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date *</label>
                    <div class="relative">
                        <input type="date" name="withdrawal_date" value="<?php echo esc_attr($withdrawal->withdrawal_date); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white pl-11 pr-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="h-4 w-4" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="4"></rect>
                                <path d="M16 2v4"></path>
                                <path d="M8 2v4"></path>
                                <path d="M3 10h18"></path>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Amount *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" value="<?php echo esc_attr($withdrawal->amount); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-right text-sm font-semibold text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Category</label>
                    <input type="text" name="category" value="<?php echo esc_attr($withdrawal->category); ?>" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                    <textarea name="notes" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 resize-none"><?php echo esc_textarea($withdrawal->notes); ?></textarea>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" name="enc_withdrawal_update" class="inline-flex h-11 flex-1 items-center justify-center rounded-2xl bg-rose-600 px-4 text-sm font-semibold text-white transition hover:bg-rose-500"><?php esc_html_e('Update withdrawal', 'enc'); ?></button>
                    <a href="<?php echo esc_url($back_url); ?>" class="inline-flex h-11 flex-1 items-center justify-center rounded-2xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><?php esc_html_e('Cancel', 'enc'); ?></a>
                </div>
            </form>
        </section>
    </div>
    <?php
    return ob_get_clean();
}

// Withdrawal form shortcode [enc_withdrawal_form]  
add_shortcode('enc_withdrawal_form', 'enc_withdrawal_form_shortcode');
function enc_withdrawal_form_shortcode($atts = [])
{
    $atts = shortcode_atts([
        'variant' => 'card',
        'show_messages' => 'true',
    ], $atts, 'enc_withdrawal_form');

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
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Withdrawal recorded successfully!</div>';
    } elseif (isset($_GET['error'])) {
        $error_code = intval($_GET['error']);
        $error_messages = [
            1 => 'Please select a valid store and enter a valid amount.',
            2 => 'Failed to save withdrawal. Please try again.'
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">' . esc_html($message) . '</div>';
    }

    $variant = $atts['variant'] === 'minimal' ? 'minimal' : 'card';
    $show_messages = !in_array(strtolower((string) $atts['show_messages']), ['0', 'false', 'no'], true);
    $form_classes = 'space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm';
    if ($variant === 'minimal') {
        $form_classes = 'space-y-4';
    }

    ob_start();
    ?>
    <div
        class="space-y-4">
        <?php if ($show_messages && !empty($messages)): ?>
            <div
                class="space-y-3"><?php echo $messages; ?>
            </div>
        <?php endif; ?>

        <form
            method="post" class="<?php echo esc_attr($form_classes); ?>">
            <?php wp_nonce_field('enc_withdrawal_submit', 'enc_withdrawal_nonce'); ?>

            <div class="space-y-2">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Store *</label>
                <select name="store_id" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    <option value=""><?php esc_html_e('Select Store', 'enc'); ?></option>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?php echo esc_attr($store->id); ?>"><?php echo esc_html($store->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date *</label>
                <div class="relative">
                    <input type="date" name="withdrawal_date" value="<?php echo current_time('Y-m-d'); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white pl-11 pr-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="h-4 w-4" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="4"></rect>
                            <path d="M16 2v4"></path>
                            <path d="M8 2v4"></path>
                            <path d="M3 10h18"></path>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Amount *</label>
                <input type="number" name="amount" step="0.01" min="0.01" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-right text-sm font-semibold text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            </div>

            <div class="space-y-2">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Category</label>
                <input type="text" name="category" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            </div>

            <div class="space-y-2">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                <textarea name="notes" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 resize-none"></textarea>
            </div>

            <button
                type="submit" name="enc_withdrawal_submit" class="inline-flex h-11 w-full items-center justify-center rounded-2xl bg-rose-600 px-4 text-sm font-semibold text-white transition hover:bg-rose-500"><?php esc_html_e('Submit withdrawal', 'enc'); ?>
            </button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}