<?php
/**
 * ENC Tracker Incomes Module
 */

if (!function_exists('enc_render_report_filters')) {
    function enc_render_report_filters($args = [])
    {
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
            'actions_align' => 'start',
        ];
        $args = wp_parse_args($args, $defaults);

        ob_start();
        ?>
        <form method="get" class="mb-8 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <input type="hidden" name="tab" value="<?php echo esc_attr($args['tab']); ?>"/>

            <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-12">

                <div class="md:col-span-3">
                    <label class="mb-1.5 block px-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Store</label>
                    <div class="relative">
                        <select name="store_id" class="h-11 w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-4 pr-10 text-sm font-medium text-slate-700 transition-all focus:border-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-slate-900/5">
                            <option value="">All Stores</option>
                            <?php foreach ($args['stores'] as $store): ?>
                                <option
                                    value="<?php echo esc_attr($store->id); ?>" <?php selected($store->id, $args['selected_store']); ?>><?php echo esc_html($store->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-4">
                    <label class="mb-1.5 block px-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Date Range</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="date_from" value="<?php echo esc_attr($args['date_from']); ?>" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-700 transition-all focus:border-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-slate-900/5">
                        <input type="date" name="date_to" value="<?php echo esc_attr($args['date_to']); ?>" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-700 transition-all focus:border-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-slate-900/5">
                    </div>
                </div>

                <div class="flex items-center gap-2 md:col-span-5">
                    <button
                        type="submit" class="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-slate-900 px-4 text-sm font-bold text-white transition hover:bg-slate-800 active:scale-[0.98]"><?php echo esc_html($args['button_label']); ?>
                    </button>

                    <?php if ($args['show_clear'] && !empty($args['clear_url'])): ?>
                        <a href="<?php echo esc_url($args['clear_url']); ?>" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-700" title="Clear Filters">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($args['export_url'])): ?>
                        <a href="<?php echo esc_url($args['export_url']); ?>" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-900/10 bg-slate-100 px-4 text-sm font-bold text-slate-900 transition hover:bg-slate-200">
                            <svg class="h-4 w-4" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5l5 5 5-5m-5 5V3"></path>
                            </svg>
                            <span class="hidden lg:inline"><?php echo esc_html($args['export_label']); ?></span>
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
function enc_handle_income_export()
{
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
function enc_incomes_view()
{
    // Check if we're in edit mode
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        return enc_income_edit_shortcode(['id' => intval($_GET['id'])]);
    }

    global $wpdb;

    // Success/error messages
    $messages = '';
    if (isset($_GET['deleted'])) {
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Income deleted successfully!</div>';
    } elseif (isset($_GET['updated'])) {
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Income updated successfully!</div>';
    } elseif (isset($_GET['success'])) {
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Income recorded successfully!</div>';
    } elseif (isset($_GET['error'])) {
        $error_code = intval($_GET['error']);
        $error_messages = [
            1 => 'Please select a valid store and enter a valid amount.',
            2 => 'Failed to save income. Please try again.'
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">' . esc_html($message) . '</div>';
    }

    $store_id = intval($_GET['store_id'] ?? 0);
    $date_from = enc_parse_date($_GET['date_from'] ?? '', current_time('Y-m-01'));
    $date_to = enc_parse_date($_GET['date_to'] ?? '', current_time('Y-m-d'));
    $stores = enc_get_stores();

    $base_incomes_url = add_query_arg('tab', 'incomes', get_permalink());
    $log_form_url = add_query_arg('view', 'log', $base_incomes_url);
    $show_form_view = isset($_GET['view']) && $_GET['view'] === 'log';
    $can_add_income = current_user_can('enc_add_income');

    if ($show_form_view && $can_add_income) {
        ob_start();
        ?>
        <div class="mx-auto max-w-3xl space-y-5">
            <?php if (!empty($messages)): ?>
                <div class="space-y-3"><?php echo $messages; ?></div>
            <?php endif; ?>

            <a href="<?php echo esc_url($base_incomes_url); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900">
                <span aria-hidden="true">←</span>
                <span><?php esc_html_e('Back to income reports', 'enc'); ?></span>
            </a>

            <?php echo enc_income_form_shortcode(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

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

    $record_count = count($incomes);
    $total_amount = array_sum(array_map(function ($income) {
        return (float) $income->amount;
    }, $incomes));

    $period_start_label = date_i18n('M j, Y', strtotime($date_from));
    $period_end_label = date_i18n('M j, Y', strtotime($date_to));

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
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Financial Summary</p>
                        <h2 class="text-3xl font-bold tracking-tight text-slate-900">Income Reports</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Showing entries from
                            <span class="font-medium text-slate-700"><?php echo esc_html($period_start_label); ?></span>
                            to
                            <span class="font-medium text-slate-700"><?php echo esc_html($period_end_label); ?></span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-[12px] font-medium text-slate-600 shadow-sm">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <?php echo esc_html($record_count); ?>
                            recent entries
                        </div>
                        <?php if ($can_add_income): ?>
                            <a href="<?php echo esc_url($log_form_url); ?>" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition-all hover:bg-slate-800 hover:shadow-lg active:scale-95">
                                <span><?php esc_html_e('Record Income', 'enc'); ?></span>
                                <svg class="h-4 w-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14m-7-7l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="w-full md:w-80">
                    <div class="relative overflow-hidden rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm">
                        <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-emerald-50 opacity-60"></div>

                        <div class="relative">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-600">Total Recorded Revenue</p>
                            <div class="mt-1 flex items-baseline gap-1">
                                <span class="text-3xl font-black text-slate-900 tracking-tight">
                                    $
                                    <?php echo enc_money($total_amount); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

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

        <?php if (empty($incomes)): ?>
            <div class="rounded-3xl border border-slate-100 bg-white px-6 py-12 text-center shadow-sm">
                <p class="text-base font-semibold text-slate-800">No income records found</p>
                <p class="mt-2 text-sm text-slate-500">Adjust your filters or record a new income entry to see it listed here.</p>
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
                                <th class="px-4 py-3 text-right font-semibold">Running Total</th>
                                <th class="px-4 py-3 text-left font-semibold">Notes</th>
                                <?php if (current_user_can('manage_options')): ?>
                                    <th class="px-4 py-3 text-center font-semibold">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100">
                            <?php foreach ($incomes as $income): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-4 font-medium text-slate-900"><?php echo esc_html(date_i18n('M j, Y', strtotime($income->entry_date))); ?></td>
                                    <td class="px-4 py-4 text-slate-700"><?php echo esc_html($income->store_name); ?></td>
                                    <td class="px-4 py-4 text-right font-semibold text-slate-900">$<?php echo enc_money($income->amount); ?></td>
                                    <td class="px-4 py-4 text-right text-slate-700">$<?php echo enc_money($running_totals[$income->id] ?? $income->amount); ?></td>
                                    <td class="px-4 py-4 text-slate-600"><?php echo esc_html($income->notes); ?></td>
                                    <?php if (current_user_can('manage_options')): ?>
                                        <?php
                                        $edit_url = add_query_arg(['tab' => 'incomes', 'action' => 'edit', 'id' => $income->id], get_permalink());
                                        $delete_url = add_query_arg([
                                            'tab' => 'incomes',
                                            'action' => 'delete',
                                            'id' => $income->id,
                                            'nonce' => wp_create_nonce('delete_income_' . $income->id)
                                        ], get_permalink());
                                        ?>
                                        <td class="px-4 py-4 text-center">
                                            <div class="inline-flex items-center gap-2">
                                                <a href="<?php echo esc_url($edit_url); ?>" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">Edit</a>
                                                <a href="<?php echo esc_url($delete_url); ?>" class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-100" onclick="return confirm('Are you sure you want to delete this income record?')">Delete</a>
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
                    recorded across the current view.
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Income form handling
add_action('template_redirect', 'enc_handle_income_forms');
function enc_handle_income_forms()
{
    // Income update submission
    if (isset($_POST['enc_income_update'])) {
        if (!is_user_logged_in() || !current_user_can('manage_options'))
            return;
        if (!wp_verify_nonce($_POST['enc_income_nonce'], 'enc_income_update'))
            wp_die('Security check failed.');

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
        if (!is_user_logged_in() || !current_user_can('enc_add_income'))
            return;
        if (!wp_verify_nonce($_POST['enc_income_nonce'], 'enc_income_submit'))
            wp_die('Security check failed.');

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
        if (!current_user_can('manage_options'))
            return;

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
function enc_income_edit_shortcode($atts)
{
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
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Income updated successfully!</div>';
    } elseif (isset($_GET['error'])) {
        $error_code = intval($_GET['error']);
        $error_messages = [
            1 => 'Please select a valid store and enter a valid amount.',
            2 => 'Failed to update income. Please try again.'
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
            <span><?php esc_html_e('Back to income reports', 'enc'); ?></span>
        </a>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400"><?php esc_html_e('Income', 'enc'); ?></p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900"><?php esc_html_e('Edit income record', 'enc'); ?></h2>
                <p class="mt-1 text-sm text-slate-600"><?php esc_html_e('Update the store, date, amount, and optional notes. All changes are logged.', 'enc'); ?></p>
            </div>

            <form
                method="post" class="space-y-5">
                <?php wp_nonce_field('enc_income_update', 'enc_income_nonce'); ?>
                <input type="hidden" name="income_id" value="<?php echo esc_attr($income->id); ?>">

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Store *</label>
                    <select name="store_id" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value=""><?php esc_html_e('Select Store', 'enc'); ?></option>
                        <?php foreach ($stores as $store): ?>
                            <option
                                value="<?php echo esc_attr($store->id); ?>" <?php selected($store->id, $income->store_id); ?>><?php echo esc_html($store->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date *</label>
                    <div class="relative">
                        <input type="date" name="entry_date" value="<?php echo esc_attr($income->entry_date); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white pl-11 pr-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
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
                    <input type="number" name="amount" step="0.01" min="0.01" value="<?php echo esc_attr($income->amount); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-right text-sm font-semibold text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                    <textarea name="notes" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 resize-none"><?php echo esc_textarea($income->notes); ?></textarea>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" name="enc_income_update" class="inline-flex h-11 flex-1 items-center justify-center rounded-2xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"><?php esc_html_e('Update income', 'enc'); ?></button>
                    <a href="<?php echo esc_url($back_url); ?>" class="inline-flex h-11 flex-1 items-center justify-center rounded-2xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><?php esc_html_e('Cancel', 'enc'); ?></a>
                </div>
            </form>
        </section>
    </div>
    <?php
    return ob_get_clean();
}

// Income submission shortcode [enc_income_form]
add_shortcode('enc_income_form', 'enc_income_form_shortcode');
function enc_income_form_shortcode()
{
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
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Income recorded successfully!</div>';
    } elseif (isset($_GET['error'])) {
        $error_code = intval($_GET['error']);
        $error_messages = [
            1 => 'Please select a valid store and enter a valid amount.',
            2 => 'Failed to save income. Please try again.'
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">' . esc_html($message) . '</div>';
    }

    $default_date = current_time('Y-m-d');

    ob_start();
    ?>
    <div class="mx-auto max-w-3xl space-y-6">
        <?php if (!empty($messages)): ?>
            <div class="space-y-3"><?php echo $messages; ?></div>
        <?php endif; ?>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400"><?php esc_html_e('Income', 'enc'); ?></p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900"><?php esc_html_e('Record new income', 'enc'); ?></h2>
                <p class="mt-1 text-sm text-slate-600"><?php esc_html_e('Log store revenue with accurate dates, amounts, and contextual notes.', 'enc'); ?></p>
            </div>

            <form method="post" class="space-y-6">
                <?php wp_nonce_field('enc_income_submit', 'enc_income_nonce'); ?>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Store *', 'enc'); ?></label>
                    <select name="store_id" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value=""><?php esc_html_e('Select Store', 'enc'); ?></option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo esc_attr($store->id); ?>"><?php echo esc_html($store->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Date *', 'enc'); ?></label>
                        <div class="relative">
                            <input type="date" name="entry_date" value="<?php echo esc_attr($default_date); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white pl-11 pr-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
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
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Amount *', 'enc'); ?></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">$</span>
                            <input type="number" name="amount" step="0.01" min="0.01" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white pl-10 pr-4 text-right text-sm font-semibold text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Notes', 'enc'); ?></label>
                    <textarea name="notes" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 resize-none"></textarea>
                </div>

                <div>
                    <button type="submit" name="enc_income_submit" class="inline-flex h-12 w-full items-center justify-center rounded-2xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                        <?php esc_html_e('Submit Income', 'enc'); ?>
                    </button>
                </div>
            </form>
        </section>
    </div>
    <?php
    return ob_get_clean();
}