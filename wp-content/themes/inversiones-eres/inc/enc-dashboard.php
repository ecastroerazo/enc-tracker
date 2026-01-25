<?php
/**
 * ENC Tracker Dashboard Module
 */

// Dashboard view
function enc_dashboard_view()
{
    global $wpdb;

    $store_id = intval($_GET['store_id'] ?? 0);
    $date_from = enc_parse_date($_GET['date_from'] ?? '', current_time('Y-m-01'));
    $date_to = enc_parse_date($_GET['date_to'] ?? '', current_time('Y-m-d'));
    $stores = enc_get_stores();

    $where_income = "WHERE 1=1";
    $where_withdrawal = "WHERE 1=1";
    $income_params = [];
    $withdrawal_params = [];

    if ($store_id > 0) {
        $where_income .= " AND i.store_id = %d";
        $where_withdrawal .= " AND w.store_id = %d";
        $income_params[] = $store_id;
        $withdrawal_params[] = $store_id;
    }

    $where_income .= " AND i.entry_date BETWEEN %s AND %s";
    $where_withdrawal .= " AND w.withdrawal_date BETWEEN %s AND %s";
    $income_params[] = $date_from;
    $income_params[] = $date_to;
    $withdrawal_params[] = $date_from;
    $withdrawal_params[] = $date_to;

    // Get filtered totals
    $total_income = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(i.amount), 0) FROM {$wpdb->prefix}enc_incomes i 
         $where_income",
        ...$income_params
    ));

    $total_withdrawals = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(w.amount), 0) FROM {$wpdb->prefix}enc_withdrawals w 
         $where_withdrawal",
        ...$withdrawal_params
    ));

    $recent_incomes = $wpdb->get_results($wpdb->prepare(
        "SELECT i.entry_date, s.name AS store_name, i.amount, i.notes 
         FROM {$wpdb->prefix}enc_incomes i 
         JOIN {$wpdb->prefix}enc_stores s ON i.store_id = s.id 
         $where_income ORDER BY i.entry_date DESC LIMIT 5",
        ...$income_params
    ));

    $recent_withdrawals = $wpdb->get_results($wpdb->prepare(
        "SELECT w.withdrawal_date, s.name AS store_name, w.amount, w.category, w.notes 
         FROM {$wpdb->prefix}enc_withdrawals w 
         JOIN {$wpdb->prefix}enc_stores s ON w.store_id = s.id 
         $where_withdrawal ORDER BY w.withdrawal_date DESC LIMIT 5",
        ...$withdrawal_params
    ));

    $net_profit = $total_income - $total_withdrawals;
    $period_start_label = date_i18n('M j', strtotime($date_from));
    $period_end_label = date_i18n('M j, Y', strtotime($date_to));
    $period_days = max(1, floor((strtotime($date_to) - strtotime($date_from)) / DAY_IN_SECONDS) + 1);
    $average_daily_income = $period_days > 0 ? $total_income / $period_days : $total_income;
    $withdrawal_ratio = $total_income > 0 ? min(100, round(($total_withdrawals / max($total_income, 0.01)) * 100)) : 0;
    $health_label = $net_profit >= 0 ? __('Healthy balance', 'enc') : __('Needs attention', 'enc');

    ob_start();
    ?>
    <h2 class="text-2xl font-bold mb-6">Dashboard Overview</h2>

    <?php
    $filter_clear_url = remove_query_arg(
        ['store_id', 'date_from', 'date_to', 'paged'],
        add_query_arg('tab', 'dashboard', get_permalink())
    );

    if (function_exists('enc_render_report_filters')) {
        echo enc_render_report_filters([
            'tab' => 'dashboard',
            'stores' => $stores,
            'selected_store' => $store_id,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'clear_url' => $filter_clear_url,
            'actions_align' => 'center',
        ]);
    } else {
    ?>
    <?php
    }
    ?>

    <!-- Summary Stats -->
        <div class="mb-10 space-y-6"> <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">Period Summary</p>
                <h3 class="text-2xl font-semibold text-slate-900 mt-1"><?php esc_html_e('Financial snapshot', 'enc'); ?></h3>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full bg-slate-900/5 px-4 py-1.5 text-sm font-medium text-slate-700">
                <span class="h-2 w-2 rounded-full <?php echo $net_profit >= 0 ? 'bg-emerald-500' : 'bg-rose-500'; ?>"></span>
                <?php echo esc_html($period_start_label . ' – ' . $period_end_label); ?>
            </span>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500"><?php esc_html_e('Total Income', 'enc'); ?></p>
                        <p class="text-3xl font-semibold text-slate-900 leading-tight">$<?php echo enc_money($total_income); ?></p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <svg class="h-5 w-5" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 18v-4"></path>
                            <path d="M12 18v-7"></path>
                            <path d="M18 18v-10"></path>
                            <path d="M4 19h16"></path>
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-xs text-slate-400"><?php printf(esc_html__('Avg. %s per day', 'enc'), '$' . enc_money($average_daily_income)); ?></p>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500"><?php esc_html_e('Total Withdrawals', 'enc'); ?></p>
                        <p class="text-3xl font-semibold text-slate-900 leading-tight">$<?php echo enc_money($total_withdrawals); ?></p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                        <svg class="h-5 w-5" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="7.5"></circle>
                            <path d="M12 8v5"></path>
                            <path d="M9 13l3 3 3-3"></path>
                        </svg>
                    </span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-slate-900 text-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-300"><?php esc_html_e('Net Balance', 'enc'); ?></p>
                        <p class="text-3xl font-semibold leading-tight">$<?php echo enc_money($net_profit); ?></p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-white">
                        <svg class="h-5 w-5" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 16l4-3 4 2 4-6 4 3"></path>
                            <path d="M4 20h16"></path>
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-xs text-slate-300"><?php echo esc_html($health_label); ?></p>
            </div>
        </div>
    </div>

    <div
        class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <!-- Recent Incomes -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="h-1 w-full bg-green-500"></div>

            <div class="flex items-center justify-between px-4 py-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 ring-1 ring-green-200">
                        Income
                    </span>
                    <h3 class="text-base font-semibold text-gray-900">Recent Incomes</h3>
                </div>

                <a class="text-sm font-bold text-gray-600 hover:text-gray-900 underline underline-offset-4 decoration-gray-300 hover:decoration-gray-500 transition" href="<?php echo esc_url(add_query_arg('tab', 'incomes', get_permalink())); ?>">
                    View all ->
                </a>
            </div>

            <?php if (empty($recent_incomes)): ?>
                <p class="px-4 pb-5 text-sm text-gray-500">No income activity for this period.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/70 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Date</th>
                                <th class="px-4 py-3 text-left font-semibold">Store</th>
                                <th class="px-4 py-3 text-right font-semibold">Amount</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100">
                            <?php foreach ($recent_incomes as $income): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td
                                        class="px-4 py-3 text-gray-700 whitespace-nowrap"><?php echo esc_html((date_i18n('M j, Y', strtotime($income->entry_date)))); ?>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-gray-700"><?php echo esc_html($income->store_name); ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">
                                        <span class="text-green-600">+$</span>
                                        <?php echo enc_money($income->amount); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Withdrawals -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="h-1 w-full bg-red-500"></div>

            <div class="flex items-center justify-between px-4 py-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200">
                        Withdrawal
                    </span>
                    <h3 class="text-base font-semibold text-gray-900">Recent Withdrawals</h3>
                </div>

                <a class="text-sm font-bold text-gray-600 hover:text-gray-900 underline underline-offset-4 decoration-gray-300 hover:decoration-gray-500 transition" href="<?php echo esc_url(add_query_arg('tab', 'withdrawals', get_permalink())); ?>">
                    View all ->
                </a>
            </div>

            <?php if (empty($recent_withdrawals)): ?>
                <p class="px-4 pb-5 text-sm text-gray-500">No withdrawal activity for this period.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/70 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Date</th>
                                <th class="px-4 py-3 text-left font-semibold">Store</th>
                                <th class="px-4 py-3 text-right font-semibold">Amount</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100">
                            <?php foreach ($recent_withdrawals as $withdrawal): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td
                                        class="px-4 py-3 text-gray-700 whitespace-nowrap"><?php echo esc_html((date_i18n('M j, Y', strtotime($withdrawal->withdrawal_date)))); ?>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-gray-700"><?php echo esc_html($withdrawal->store_name); ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">
                                        <span class="text-red-600">-$</span>
                                        <?php echo enc_money($withdrawal->amount); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>


    <?php
    return ob_get_clean();
}