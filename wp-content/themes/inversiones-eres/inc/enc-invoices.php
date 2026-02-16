<?php
/**
 * ENC Tracker Invoices Module
 */

add_action('template_redirect', 'enc_handle_invoice_export');
add_action('template_redirect', 'enc_handle_invoice_forms');

function enc_invoices_view()
{
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        return enc_invoice_edit_shortcode(['id' => intval($_GET['id'])]);
    }

    if (!current_user_can('enc_manage_invoices') && !current_user_can('manage_options')) {
        return '<p>Access denied.</p>';
    }

    global $wpdb;

    $store_id = intval($_GET['store_id'] ?? 0);
    $company_id = intval($_GET['company_id'] ?? 0);
    $status_filter = sanitize_key($_GET['status'] ?? '');
    $date_from = enc_parse_date($_GET['date_from'] ?? '', current_time('Y-m-01'));
    $date_to = enc_parse_date($_GET['date_to'] ?? '', current_time('Y-m-d'));

    $stores = enc_get_stores();
    $companies = enc_get_companies();
    $statuses = enc_get_invoice_statuses();

    $base_invoices_url = add_query_arg('tab', 'invoices', get_permalink());
    $add_invoice_url = add_query_arg('view', 'add', $base_invoices_url);

    if (isset($_GET['view']) && $_GET['view'] === 'add') {
        return enc_invoice_add_view([
            'base_url' => $base_invoices_url,
            'stores' => $stores,
            'companies' => $companies,
        ]);
    }

    $messages = '';
    if (isset($_GET['invoice_success'])) {
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Invoice recorded successfully!</div>';
    } elseif (isset($_GET['invoice_updated'])) {
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Invoice updated successfully!</div>';
    } elseif (isset($_GET['invoice_error'])) {
        $error_code = intval($_GET['invoice_error']);
        $error_messages = [
            1 => 'Please complete all required fields and select valid options.',
            2 => 'Unable to save invoice. Please try again.',
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">' . esc_html($message) . '</div>';
    }

    if (isset($_GET['invoice_deleted'])) {
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Invoice deleted successfully!</div>';
    }

    if (isset($_GET['company_success'])) {
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Company added successfully!</div>';
    } elseif (isset($_GET['company_error'])) {
        $error_code = intval($_GET['company_error']);
        $error_messages = [
            1 => 'Company name is required.',
            2 => 'Unable to save company. Please try again.',
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">' . esc_html($message) . '</div>';
    }

    $where = 'WHERE 1=1';
    $params = [];

    if ($store_id > 0) {
        $where .= ' AND i.store_id = %d';
        $params[] = $store_id;
    }

    if ($company_id > 0) {
        $where .= ' AND i.company_id = %d';
        $params[] = $company_id;
    }

    if ($status_filter && isset($statuses[$status_filter])) {
        $where .= ' AND i.status = %s';
        $params[] = $status_filter;
    }

    $where .= ' AND i.invoice_date BETWEEN %s AND %s';
    $params[] = $date_from;
    $params[] = $date_to;

    $invoice_query = "SELECT i.*, s.name AS store_name, c.name AS company_name
        FROM {$wpdb->prefix}enc_invoices i
        JOIN {$wpdb->prefix}enc_stores s ON i.store_id = s.id
        LEFT JOIN {$wpdb->prefix}enc_companies c ON i.company_id = c.id
        $where
        ORDER BY i.invoice_date DESC, i.id DESC";

    $invoices = $wpdb->get_results($wpdb->prepare($invoice_query . ' LIMIT 50', $params));

    $record_count = count($invoices);
    $total_amount = array_sum(array_map(function ($invoice) {
        return (float) $invoice->amount;
    }, $invoices));

    $open_amount = array_sum(array_map(function ($invoice) {
        return in_array($invoice->status, ['pending', 'overdue'], true) ? (float) $invoice->amount : 0;
    }, $invoices));

    $period_start_label = date_i18n('M j, Y', strtotime($date_from));
    $period_end_label = date_i18n('M j, Y', strtotime($date_to));

    $filter_clear_url = remove_query_arg(
        ['store_id', 'company_id', 'status', 'date_from', 'date_to', 'enc_export', 'paged'],
        add_query_arg('tab', 'invoices', get_permalink())
    );

    $export_url = '';
    if (current_user_can('enc_manage_invoices') || current_user_can('manage_options')) {
        $export_args = [
            'tab' => 'invoices',
            'enc_export' => 'invoices',
        ];

        if ($store_id > 0) {
            $export_args['store_id'] = $store_id;
        }
        if ($company_id > 0) {
            $export_args['company_id'] = $company_id;
        }
        if ($status_filter && isset($statuses[$status_filter])) {
            $export_args['status'] = $status_filter;
        }
        if ($date_from) {
            $export_args['date_from'] = $date_from;
        }
        if ($date_to) {
            $export_args['date_to'] = $date_to;
        }

        $export_url = add_query_arg($export_args, get_permalink());
    }

    ob_start();
    ?>
    <div class="space-y-6">
        <?php if (!empty($messages)): ?>
            <div class="space-y-3"><?php echo $messages; ?></div>
        <?php endif; ?>

        <section class="py-2">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div class="space-y-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Trade Finance</p>
                        <h2 class="text-3xl font-bold tracking-tight text-slate-900">Invoice Ledger</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Tracking invoices from
                            <span class="font-medium text-slate-700"><?php echo esc_html($period_start_label); ?></span>
                            to
                            <span class="font-medium text-slate-700"><?php echo esc_html($period_end_label); ?></span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-[12px] font-medium text-slate-600 shadow-sm">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-300 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                            </span>
                            <?php echo esc_html($record_count); ?> invoices
                        </div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-[12px] font-medium text-slate-600 shadow-sm">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            $<?php echo enc_money($total_amount); ?> billed
                        </div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-[12px] font-medium text-slate-600 shadow-sm">
                            <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                            $<?php echo enc_money($open_amount); ?> open
                        </div>
                        <a href="<?php echo esc_url($add_invoice_url); ?>" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition-all hover:bg-slate-800 hover:shadow-lg active:scale-95">
                            <span>Add Invoice</span>
                            <svg class="h-4 w-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14m-7-7l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    </div>
                </div>

                <?php if ($export_url): ?>
                    <a href="<?php echo esc_url($export_url); ?>" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-900/10 bg-white px-4 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M12 5v14M5 12l7 7 7-7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Export CSV
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <form method="get" class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <input type="hidden" name="tab" value="invoices" />
            <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                <div class="md:col-span-3">
                    <label class="mb-1.5 block px-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Store</label>
                    <div class="relative">
                        <select name="store_id" class="h-11 w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-4 pr-10 text-sm font-medium text-slate-700 focus:border-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-slate-900/5">
                            <option value="">All Stores</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?php echo esc_attr($store->id); ?>" <?php selected($store->id, $store_id); ?>><?php echo esc_html($store->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label class="mb-1.5 block px-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Company</label>
                    <div class="relative">
                        <select name="company_id" class="h-11 w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-4 pr-10 text-sm font-medium text-slate-700 focus:border-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-slate-900/5">
                            <option value="">All Companies</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo esc_attr($company->id); ?>" <?php selected($company->id, $company_id); ?>><?php echo esc_html($company->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block px-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Status</label>
                    <div class="relative">
                        <select name="status" class="h-11 w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-4 pr-10 text-sm font-medium text-slate-700 focus:border-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-slate-900/5">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($status_filter, $key); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-4">
                    <label class="mb-1.5 block px-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Date Range</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-700 focus:border-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-slate-900/5">
                        <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-700 focus:border-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-slate-900/5">
                    </div>
                </div>

                <div class="flex items-center gap-2 md:col-span-12">
                    <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-slate-900 px-4 text-sm font-bold text-white transition hover:bg-slate-800 active:scale-[0.98]">Filter</button>
                    <a href="<?php echo esc_url($filter_clear_url); ?>" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">Clear</a>
                </div>
            </div>
        </form>

        <div class="space-y-6">
            <div class="space-y-6">
                <?php if (empty($invoices)): ?>
                    <div class="rounded-3xl border border-slate-100 bg-white px-6 py-12 text-center shadow-sm">
                        <p class="text-base font-semibold text-slate-800">No invoices found</p>
                        <p class="mt-2 text-sm text-slate-500">Adjust filters or record a new invoice.</p>
                    </div>
                <?php else: ?>
                    <div class="rounded-3xl border border-slate-100 bg-white shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Invoice #</th>
                                        <th class="px-4 py-3 text-left font-semibold">Client</th>
                                        <th class="px-4 py-3 text-left font-semibold">Store</th>
                                        <th class="px-4 py-3 text-left font-semibold">Company</th>
                                        <th class="px-4 py-3 text-left font-semibold">Date</th>
                                        <th class="px-4 py-3 text-left font-semibold">Due</th>
                                        <th class="px-4 py-3 text-right font-semibold">Amount</th>
                                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                                        <th class="px-4 py-3 text-center font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($invoices as $invoice): ?>
                                        <?php
                                        $delete_url = add_query_arg([
                                            'tab' => 'invoices',
                                            'action' => 'delete-invoice',
                                            'id' => $invoice->id,
                                            'nonce' => wp_create_nonce('delete_invoice_' . $invoice->id),
                                        ], get_permalink());
                                        $edit_url = add_query_arg([
                                            'tab' => 'invoices',
                                            'action' => 'edit',
                                            'id' => $invoice->id,
                                        ], get_permalink());
                                        ?>
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-4 font-semibold text-slate-900"><?php echo esc_html($invoice->invoice_number); ?></td>
                                            <td class="px-4 py-4 text-slate-700"><?php echo esc_html($invoice->client_name); ?></td>
                                            <td class="px-4 py-4 text-slate-600"><?php echo esc_html($invoice->store_name); ?></td>
                                            <td class="px-4 py-4 text-slate-600"><?php echo esc_html($invoice->company_name ?: '—'); ?></td>
                                            <td class="px-4 py-4 text-slate-600"><?php echo esc_html(date_i18n('M j, Y', strtotime($invoice->invoice_date))); ?></td>
                                            <td class="px-4 py-4 text-slate-600"><?php echo esc_html(date_i18n('M j, Y', strtotime($invoice->due_date))); ?></td>
                                            <td class="px-4 py-4 text-right font-semibold text-slate-900">$<?php echo enc_money($invoice->amount); ?></td>
                                            <td class="px-4 py-4"><?php echo enc_invoice_status_badge($invoice->status); ?></td>
                                            <td class="px-4 py-4 text-center">
                                                <div class="inline-flex items-center gap-2">
                                                    <a href="<?php echo esc_url($edit_url); ?>" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">Edit</a>
                                                    <a href="<?php echo esc_url($delete_url); ?>" class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-100" onclick="return confirm('Delete this invoice?');">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-slate-100 px-6 py-4 text-sm text-slate-600">
                            <span class="font-semibold text-slate-900">$<?php echo enc_money($total_amount); ?></span>
                            total across the current view.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function enc_invoice_add_view($args = [])
{
    if (!current_user_can('enc_manage_invoices') && !current_user_can('manage_options')) {
        return '<p>Access denied.</p>';
    }

    $defaults = [
        'base_url' => add_query_arg('tab', 'invoices', get_permalink()),
        'stores' => [],
        'companies' => [],
    ];
    $args = wp_parse_args($args, $defaults);

    $messages = '';
    if (isset($_GET['invoice_error'])) {
        $error_code = intval($_GET['invoice_error']);
        $error_messages = [
            1 => 'Please complete all required fields and select valid options.',
            2 => 'Unable to save invoice. Please try again.',
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">' . esc_html($message) . '</div>';
    }

    if (isset($_GET['company_success'])) {
        $messages .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">Company added successfully!</div>';
    } elseif (isset($_GET['company_error'])) {
        $error_code = intval($_GET['company_error']);
        $error_messages = [
            1 => 'Company name is required.',
            2 => 'Unable to save company. Please try again.',
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">' . esc_html($message) . '</div>';
    }

    $back_url = $args['base_url'];

    ob_start();
    ?>
    <div class="mx-auto max-w-4xl space-y-5">
        <?php if (!empty($messages)): ?>
            <div class="space-y-3"><?php echo $messages; ?></div>
        <?php endif; ?>

        <a href="<?php echo esc_url($back_url); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900">
            <span aria-hidden="true">&larr;</span>
            <span><?php esc_html_e('Back to invoice ledger', 'enc'); ?></span>
        </a>

        <div class="space-y-6">
            <?php echo enc_render_invoice_form([
                'stores' => $args['stores'],
                'companies' => $args['companies'],
            ]); ?>

            <?php echo enc_render_company_form(); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function enc_handle_invoice_export()
{
    if (!isset($_GET['enc_export']) || $_GET['enc_export'] !== 'invoices') {
        return;
    }

    if (!current_user_can('enc_manage_invoices') && !current_user_can('manage_options')) {
        wp_die('You do not have permission to export invoice reports.');
    }

    global $wpdb;

    $statuses = enc_get_invoice_statuses();
    $store_id = intval($_GET['store_id'] ?? 0);
    $company_id = intval($_GET['company_id'] ?? 0);
    $status_filter = sanitize_key($_GET['status'] ?? '');
    $date_from = enc_parse_date($_GET['date_from'] ?? '', current_time('Y-m-01'));
    $date_to = enc_parse_date($_GET['date_to'] ?? '', current_time('Y-m-d'));

    $where = 'WHERE 1=1';
    $params = [];

    if ($store_id > 0) {
        $where .= ' AND i.store_id = %d';
        $params[] = $store_id;
    }
    if ($company_id > 0) {
        $where .= ' AND i.company_id = %d';
        $params[] = $company_id;
    }
    if ($status_filter && isset($statuses[$status_filter])) {
        $where .= ' AND i.status = %s';
        $params[] = $status_filter;
    }
    $where .= ' AND i.invoice_date BETWEEN %s AND %s';
    $params[] = $date_from;
    $params[] = $date_to;

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT i.invoice_number, i.client_name, s.name AS store_name, c.name AS company_name, i.amount, i.invoice_date, i.due_date, i.status
         FROM {$wpdb->prefix}enc_invoices i
         JOIN {$wpdb->prefix}enc_stores s ON i.store_id = s.id
         LEFT JOIN {$wpdb->prefix}enc_companies c ON i.company_id = c.id
         $where ORDER BY i.invoice_date DESC, i.id DESC",
        $params
    ));

    $rows = array_map(function ($row) use ($statuses) {
        return [
            $row->invoice_number,
            $row->client_name,
            $row->store_name,
            $row->company_name,
            $row->amount,
            $row->invoice_date,
            $row->due_date,
            $statuses[$row->status] ?? ucfirst($row->status),
        ];
    }, $results);

    enc_stream_csv('enc-invoices-' . date('Ymd') . '.csv', ['Invoice #', 'Client', 'Store', 'Company', 'Amount', 'Invoice Date', 'Due Date', 'Status'], $rows);
}

function enc_handle_invoice_forms()
{
    // Handle invoice submission
    if (isset($_POST['enc_invoice_submit'])) {
        if (!current_user_can('enc_manage_invoices') && !current_user_can('manage_options')) {
            return;
        }
        if (!wp_verify_nonce($_POST['enc_invoice_nonce'] ?? '', 'enc_invoice_submit')) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $store_id = intval($_POST['store_id'] ?? 0);
        $company_id = intval($_POST['company_id'] ?? 0);
        $invoice_number = sanitize_text_field($_POST['invoice_number'] ?? '');
        $client_name = sanitize_text_field($_POST['client_name'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $invoice_date = enc_parse_date($_POST['invoice_date'] ?? '', current_time('Y-m-d'));
        $due_date = enc_parse_date($_POST['due_date'] ?? '', current_time('Y-m-d'));
        $status = sanitize_key($_POST['status'] ?? 'pending');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $valid_store = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}enc_stores WHERE id = %d AND is_active = 1", $store_id));
        $valid_company = null;
        if ($company_id > 0) {
            $valid_company = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}enc_companies WHERE id = %d", $company_id));
        }

        $status_options = enc_get_invoice_statuses();
        $has_required_fields = $valid_store && !empty($invoice_number) && !empty($client_name) && $amount > 0 && isset($status_options[$status]);

        $base_url = strtok((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '?');
        $ledger_url = add_query_arg('tab', 'invoices', $base_url);
        $add_view_url = add_query_arg(['tab' => 'invoices', 'view' => 'add'], $base_url);

        if (!$has_required_fields) {
            wp_safe_redirect(add_query_arg('invoice_error', 1, $add_view_url));
            exit;
        }

        $data = [
            'store_id' => $store_id,
            'invoice_number' => $invoice_number,
            'client_name' => $client_name,
            'amount' => $amount,
            'invoice_date' => $invoice_date,
            'due_date' => $due_date,
            'status' => $status,
            'description' => $description,
            'notes' => $notes,
            'created_by' => get_current_user_id(),
        ];
        $format = ['%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%d'];

        if ($company_id > 0 && $valid_company) {
            $data['company_id'] = $company_id;
            $format[] = '%d';
        }

        $result = $wpdb->insert($wpdb->prefix . 'enc_invoices', $data, $format);

        if ($result) {
            wp_safe_redirect(add_query_arg('invoice_success', 1, $ledger_url));
        } else {
            wp_safe_redirect(add_query_arg('invoice_error', 2, $add_view_url));
        }
        exit;
    }

    // Handle invoice updates
    if (isset($_POST['enc_invoice_update'])) {
        if (!current_user_can('enc_manage_invoices') && !current_user_can('manage_options')) {
            return;
        }
        if (!wp_verify_nonce($_POST['enc_invoice_nonce'] ?? '', 'enc_invoice_update')) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $invoice_id = intval($_POST['invoice_id'] ?? 0);
        $store_id = intval($_POST['store_id'] ?? 0);
        $company_id = intval($_POST['company_id'] ?? 0);
        $invoice_number = sanitize_text_field($_POST['invoice_number'] ?? '');
        $client_name = sanitize_text_field($_POST['client_name'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $invoice_date = enc_parse_date($_POST['invoice_date'] ?? '', current_time('Y-m-d'));
        $due_date = enc_parse_date($_POST['due_date'] ?? '', current_time('Y-m-d'));
        $status = sanitize_key($_POST['status'] ?? 'pending');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $redirect_base = add_query_arg('tab', 'invoices', strtok((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '?'));

        if ($invoice_id <= 0) {
            wp_safe_redirect(add_query_arg('invoice_error', 2, $redirect_base));
            exit;
        }

        $existing_invoice = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}enc_invoices WHERE id = %d", $invoice_id));
        if (!$existing_invoice) {
            wp_safe_redirect(add_query_arg('invoice_error', 2, $redirect_base));
            exit;
        }

        $valid_store = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}enc_stores WHERE id = %d AND is_active = 1", $store_id));
        $valid_company = null;
        if ($company_id > 0) {
            $valid_company = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}enc_companies WHERE id = %d", $company_id));
        }

        $status_options = enc_get_invoice_statuses();
        $has_required_fields = $valid_store && !empty($invoice_number) && !empty($client_name) && $amount > 0 && isset($status_options[$status]);

        if (!$has_required_fields) {
            wp_safe_redirect(add_query_arg('invoice_error', 1, $redirect_base));
            exit;
        }

        $data = [
            'store_id' => $store_id,
            'invoice_number' => $invoice_number,
            'client_name' => $client_name,
            'amount' => $amount,
            'invoice_date' => $invoice_date,
            'due_date' => $due_date,
            'status' => $status,
            'description' => $description,
            'notes' => $notes,
        ];
        $format = ['%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s'];

        $reset_company = false;
        if ($company_id > 0 && $valid_company) {
            $data['company_id'] = $company_id;
            $format[] = '%d';
        } else {
            $reset_company = true;
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'enc_invoices',
            $data,
            ['id' => $invoice_id],
            $format,
            ['%d']
        );

        $operation_success = ($result !== false);

        if ($reset_company) {
            $null_result = $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}enc_invoices SET company_id = NULL WHERE id = %d",
                $invoice_id
            ));
            if ($null_result === false) {
                $operation_success = false;
            }
        }

        if ($operation_success) {
            wp_safe_redirect(add_query_arg('invoice_updated', 1, $redirect_base));
        } else {
            wp_safe_redirect(add_query_arg('invoice_error', 2, $redirect_base));
        }
        exit;
    }

    // Handle company submission
    if (isset($_POST['enc_company_submit'])) {
        if (!current_user_can('enc_manage_companies') && !current_user_can('manage_options')) {
            return;
        }
        if (!wp_verify_nonce($_POST['enc_company_nonce'] ?? '', 'enc_company_submit')) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $name = sanitize_text_field($_POST['company_name'] ?? '');
        $contact_person = sanitize_text_field($_POST['contact_person'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $notes = sanitize_textarea_field($_POST['company_notes'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $base_url = strtok((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '?');
        $redirect_base = add_query_arg(['tab' => 'invoices', 'view' => 'add'], $base_url);

        if (empty($name)) {
            wp_safe_redirect(add_query_arg('company_error', 1, $redirect_base));
            exit;
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'enc_companies',
            [
                'name' => $name,
                'contact_person' => $contact_person,
                'phone' => $phone,
                'notes' => $notes,
                'is_active' => $is_active,
                'created_by' => get_current_user_id(),
            ],
            ['%s', '%s', '%s', '%s', '%d', '%d']
        );

        if ($result) {
            wp_safe_redirect(add_query_arg('company_success', 1, $redirect_base));
        } else {
            wp_safe_redirect(add_query_arg('company_error', 2, $redirect_base));
        }
        exit;
    }

    // Handle invoice deletion
    if (isset($_GET['action']) && $_GET['action'] === 'delete-invoice' && isset($_GET['id']) && ($_GET['tab'] ?? '') === 'invoices') {
        if (!current_user_can('enc_manage_invoices') && !current_user_can('manage_options')) {
            return;
        }

        $invoice_id = intval($_GET['id']);
        $nonce = sanitize_text_field($_GET['nonce'] ?? '');

        if (!wp_verify_nonce($nonce, 'delete_invoice_' . $invoice_id)) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'enc_invoices', ['id' => $invoice_id], ['%d']);

        $redirect_base = add_query_arg('tab', 'invoices', remove_query_arg(['action', 'id', 'nonce'], (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']));
        wp_safe_redirect(add_query_arg('invoice_deleted', 1, $redirect_base));
        exit;
    }
}

add_shortcode('enc_invoice_edit', 'enc_invoice_edit_shortcode');
function enc_invoice_edit_shortcode($atts = [])
{
    if (!is_user_logged_in() || (!current_user_can('enc_manage_invoices') && !current_user_can('manage_options'))) {
        return '<p>Access denied.</p>';
    }

    $atts = shortcode_atts(['id' => 0], $atts, 'enc_invoice_edit');
    $invoice_id = intval($atts['id']) ?: intval($_GET['id'] ?? 0);

    if ($invoice_id <= 0) {
        return '<p>Invalid invoice ID.</p>';
    }

    global $wpdb;
    $invoice = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}enc_invoices WHERE id = %d",
        $invoice_id
    ));

    if (!$invoice) {
        return '<p>Invoice not found.</p>';
    }

    $stores = enc_get_stores();
    if (empty($stores)) {
        return '<p>No stores available. Please add a store first.</p>';
    }

    $companies = enc_get_companies();
    $statuses = enc_get_invoice_statuses();

    $messages = '';
    if (isset($_GET['invoice_error'])) {
        $error_code = intval($_GET['invoice_error']);
        $error_messages = [
            1 => 'Please complete all required fields and select valid options.',
            2 => 'Unable to save invoice. Please try again.',
        ];
        $message = $error_messages[$error_code] ?? 'An error occurred. Please try again.';
        $messages .= '<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">' . esc_html($message) . '</div>';
    }

    $back_url = remove_query_arg(['action', 'id']);
    $today = current_time('timestamp');
    $invoice_date = !empty($invoice->invoice_date) ? date('Y-m-d', strtotime($invoice->invoice_date)) : date('Y-m-d', $today);
    $due_date = !empty($invoice->due_date) ? date('Y-m-d', strtotime($invoice->due_date)) : date('Y-m-d', $today);
    $amount_value = number_format((float) $invoice->amount, 2, '.', '');
    $current_company_id = $invoice->company_id ? (int) $invoice->company_id : 0;

    ob_start();
    ?>
    <div class="mx-auto max-w-3xl space-y-5">
        <?php if (!empty($messages)): ?>
            <div class="space-y-3"><?php echo $messages; ?></div>
        <?php endif; ?>

        <a href="<?php echo esc_url($back_url); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900">
            <span aria-hidden="true">&larr;</span>
            <span><?php esc_html_e('Back to invoice ledger', 'enc'); ?></span>
        </a>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400"><?php esc_html_e('Invoices', 'enc'); ?></p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900"><?php esc_html_e('Edit invoice', 'enc'); ?></h2>
                <p class="mt-1 text-sm text-slate-600"><?php esc_html_e('Update billing details, company links, dates, and notes from a focused workspace.', 'enc'); ?></p>
            </div>

            <form method="post" class="space-y-5">
                <?php wp_nonce_field('enc_invoice_update', 'enc_invoice_nonce'); ?>
                <input type="hidden" name="invoice_id" value="<?php echo esc_attr($invoice->id); ?>">

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Store *', 'enc'); ?></label>
                    <select name="store_id" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value=""><?php esc_html_e('Select Store', 'enc'); ?></option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo esc_attr($store->id); ?>" <?php selected($store->id, $invoice->store_id); ?>><?php echo esc_html($store->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Invoice Number *', 'enc'); ?></label>
                    <input type="text" name="invoice_number" value="<?php echo esc_attr($invoice->invoice_number); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Client Name *', 'enc'); ?></label>
                    <input type="text" name="client_name" value="<?php echo esc_attr($invoice->client_name); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Company', 'enc'); ?></label>
                    <select name="company_id" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value="" <?php selected(0, $current_company_id); ?>><?php esc_html_e('Unassigned', 'enc'); ?></option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo esc_attr($company->id); ?>" <?php selected($company->id, $current_company_id); ?>><?php echo esc_html($company->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Invoice Date *', 'enc'); ?></label>
                        <input type="date" name="invoice_date" value="<?php echo esc_attr($invoice_date); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Due Date *', 'enc'); ?></label>
                        <input type="date" name="due_date" value="<?php echo esc_attr($due_date); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Amount *', 'enc'); ?></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">$</span>
                        <input type="number" name="amount" value="<?php echo esc_attr($amount_value); ?>" step="0.01" min="0.01" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white pl-10 pr-4 text-right text-sm font-semibold text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Status *', 'enc'); ?></label>
                    <select name="status" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($key, $invoice->status); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Description', 'enc'); ?></label>
                    <textarea name="description" rows="3" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 resize-none"><?php echo esc_textarea($invoice->description); ?></textarea>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Internal Notes', 'enc'); ?></label>
                    <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 resize-none"><?php echo esc_textarea($invoice->notes); ?></textarea>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" name="enc_invoice_update" class="inline-flex h-12 flex-1 items-center justify-center rounded-2xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"><?php esc_html_e('Save invoice', 'enc'); ?></button>
                    <a href="<?php echo esc_url($back_url); ?>" class="inline-flex h-12 flex-1 items-center justify-center rounded-2xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><?php esc_html_e('Cancel', 'enc'); ?></a>
                </div>
            </form>
        </section>
    </div>
    <?php
    return ob_get_clean();
}

function enc_render_invoice_form($args = [])
{
    $defaults = [
        'stores' => [],
        'companies' => [],
    ];
    $args = wp_parse_args($args, $defaults);

    $today = current_time('timestamp');
    $default_date = date('Y-m-d', $today);
    $default_due = date('Y-m-d', $today + (DAY_IN_SECONDS * 15));
    $statuses = enc_get_invoice_statuses();

    $form_values = [
        'store_id' => '',
        'invoice_number' => '',
        'client_name' => '',
        'company_id' => '',
        'invoice_date' => $default_date,
        'due_date' => $default_due,
        'amount' => '',
        'status' => 'pending',
        'description' => '',
        'notes' => '',
    ];

    ob_start();
    ?>
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400"><?php esc_html_e('Invoices', 'enc'); ?></p>
            <h3 class="mt-1 text-xl font-semibold text-slate-900"><?php esc_html_e('Record new invoice', 'enc'); ?></h3>
            <p class="mt-1 text-sm text-slate-500"><?php esc_html_e('Log billed work, assign a store and optionally link a company.', 'enc'); ?></p>
        </div>

        <?php if (empty($args['stores'])): ?>
            <p class="text-sm text-rose-600"><?php esc_html_e('No stores available. Please add a store first.', 'enc'); ?></p>
        <?php else: ?>
            <form method="post" class="space-y-4">
                <?php wp_nonce_field('enc_invoice_submit', 'enc_invoice_nonce'); ?>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Store *', 'enc'); ?></label>
                    <select name="store_id" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value=""><?php esc_html_e('Select Store', 'enc'); ?></option>
                        <?php foreach ($args['stores'] as $store): ?>
                            <option value="<?php echo esc_attr($store->id); ?>" <?php selected($store->id, $form_values['store_id']); ?>><?php echo esc_html($store->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Invoice Number *', 'enc'); ?></label>
                    <input type="text" name="invoice_number" value="<?php echo esc_attr($form_values['invoice_number']); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10" placeholder="INV-0001">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Client Name *', 'enc'); ?></label>
                    <input type="text" name="client_name" value="<?php echo esc_attr($form_values['client_name']); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Company', 'enc'); ?></label>
                    <select name="company_id" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value="" <?php selected('', $form_values['company_id']); ?>><?php esc_html_e('Unassigned', 'enc'); ?></option>
                        <?php foreach ($args['companies'] as $company): ?>
                            <option value="<?php echo esc_attr($company->id); ?>" <?php selected($company->id, $form_values['company_id']); ?>><?php echo esc_html($company->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($args['companies'])): ?>
                        <p class="text-[11px] text-slate-400"><?php esc_html_e('Need a company? Use the form below to add one.', 'enc'); ?></p>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Invoice Date *', 'enc'); ?></label>
                        <input type="date" name="invoice_date" value="<?php echo esc_attr($form_values['invoice_date']); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Due Date *', 'enc'); ?></label>
                        <input type="date" name="due_date" value="<?php echo esc_attr($form_values['due_date']); ?>" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Amount *', 'enc'); ?></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">$</span>
                        <input type="number" name="amount" value="<?php echo esc_attr($form_values['amount']); ?>" step="0.01" min="0.01" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white pl-10 pr-4 text-right text-sm font-semibold text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Status *', 'enc'); ?></label>
                    <select name="status" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($key, $form_values['status']); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Description', 'enc'); ?></label>
                    <textarea name="description" rows="3" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 resize-none"><?php echo esc_textarea($form_values['description']); ?></textarea>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Internal Notes', 'enc'); ?></label>
                    <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 resize-none"><?php echo esc_textarea($form_values['notes']); ?></textarea>
                </div>

                <div>
                    <button type="submit" name="enc_invoice_submit" class="inline-flex h-12 w-full items-center justify-center rounded-2xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                        <?php esc_html_e('Submit Invoice', 'enc'); ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function enc_render_company_form()
{
    if (!current_user_can('enc_manage_companies') && !current_user_can('manage_options')) {
        return '<section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm text-slate-600">' . esc_html__('You do not have permission to manage companies.', 'enc') . '</p></section>';
    }

    ob_start();
    ?>
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400"><?php esc_html_e('Companies', 'enc'); ?></p>
            <h3 class="mt-1 text-xl font-semibold text-slate-900"><?php esc_html_e('Add company', 'enc'); ?></h3>
            <p class="mt-1 text-sm text-slate-500"><?php esc_html_e('Store frequently used partners to speed up billing.', 'enc'); ?></p>
        </div>

        <form method="post" class="space-y-4">
            <?php wp_nonce_field('enc_company_submit', 'enc_company_nonce'); ?>

            <div class="space-y-1.5">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Company Name *', 'enc'); ?></label>
                <input type="text" name="company_name" required class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Contact Person', 'enc'); ?></label>
                <input type="text" name="contact_person" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Phone', 'enc'); ?></label>
                <input type="text" name="phone" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?php esc_html_e('Notes', 'enc'); ?></label>
                <textarea name="company_notes" rows="3" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 resize-none"></textarea>
            </div>

            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-600">
                <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/20" checked>
                <span><?php esc_html_e('Active', 'enc'); ?></span>
            </label>

            <div>
                <button type="submit" name="enc_company_submit" class="inline-flex h-11 w-full items-center justify-center rounded-2xl border border-slate-900/10 bg-white px-4 text-sm font-semibold text-slate-900 transition hover:bg-slate-50"><?php esc_html_e('Save Company', 'enc'); ?></button>
            </div>
        </form>
    </section>
    <?php
    return ob_get_clean();
}

function enc_invoice_status_badge($status)
{
    $map = [
        'pending' => ['label' => __('Pending', 'enc'), 'class' => 'bg-amber-50 text-amber-700'],
        'paid' => ['label' => __('Paid', 'enc'), 'class' => 'bg-emerald-50 text-emerald-700'],
        'overdue' => ['label' => __('Overdue', 'enc'), 'class' => 'bg-rose-50 text-rose-700'],
        'cancelled' => ['label' => __('Cancelled', 'enc'), 'class' => 'bg-slate-100 text-slate-600'],
    ];

    $item = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'bg-slate-100 text-slate-600'];

    return '<span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ' . esc_attr($item['class']) . '">' . esc_html($item['label']) . '</span>';
}

add_shortcode('enc_invoice_form', 'enc_invoice_form_shortcode');
function enc_invoice_form_shortcode()
{
    if (!is_user_logged_in() || (!current_user_can('enc_manage_invoices') && !current_user_can('manage_options'))) {
        return '<p>Access denied.</p>';
    }

    return enc_render_invoice_form([
        'stores' => enc_get_stores(),
        'companies' => enc_get_companies(),
    ]);
}

add_shortcode('enc_company_form', 'enc_company_form_shortcode');
function enc_company_form_shortcode()
{
    if (!is_user_logged_in() || (!current_user_can('enc_manage_companies') && !current_user_can('manage_options'))) {
        return '<p>Access denied.</p>';
    }

    return enc_render_company_form();
}
