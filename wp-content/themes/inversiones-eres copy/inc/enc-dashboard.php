<?php
/**
 * ENC Tracker Dashboard Module
 */

// Dashboard view
function enc_dashboard_view() {
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
        ]);
    } else {
        ?>
        <form method="get" class="mb-6 p-4 bg-gray-50 rounded-lg">
            <input type="hidden" name="tab" value="dashboard" />
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Store</label>
                    <select name="store_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Stores</option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo esc_attr($store->id); ?>" <?php selected($store->id, $store_id); ?>>
                                <?php echo esc_html($store->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">Filter</button>
                </div>
            </div>
        </form>
        <?php
    }
    ?>

    <!-- Summary Stats -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold mb-4">Period Summary (<?php echo date('M j, Y', strtotime($date_from)); ?> - <?php echo date('M j, Y', strtotime($date_to)); ?>)</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <div class="text-green-800 font-medium">Total Income</div>
                <div class="text-2xl font-bold text-green-900">$<?php echo enc_money($total_income); ?></div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                <div class="text-red-800 font-medium">Total Withdrawals</div>
                <div class="text-2xl font-bold text-red-900">$<?php echo enc_money($total_withdrawals); ?></div>
            </div>
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <div class="text-blue-800 font-medium">Total Balance</div>
                <div class="text-2xl font-bold <?php echo $net_profit >= 0 ? 'text-blue-900' : 'text-red-900'; ?>">
                    $<?php echo enc_money($net_profit); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="border border-gray-200 rounded-lg bg-white">
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <h3 class="text-lg font-semibold">Recent Incomes</h3>
                <a class="text-sm text-blue-600 hover:text-blue-800" href="<?php echo esc_url(add_query_arg('tab', 'incomes', get_permalink())); ?>">View all</a>
            </div>
            <?php if (empty($recent_incomes)): ?>
                <p class="px-4 py-6 text-sm text-gray-500">No income activity for this period.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-600">Date</th>
                                <th class="px-4 py-2 text-left text-gray-600">Store</th>
                                <th class="px-4 py-2 text-right text-gray-600">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_incomes as $income): ?>
                                <tr class="border-t">
                                    <td class="px-4 py-2 text-gray-800"><?php echo esc_html($income->entry_date); ?></td>
                                    <td class="px-4 py-2 text-gray-800"><?php echo esc_html($income->store_name); ?></td>
                                    <td class="px-4 py-2 text-right text-gray-900 font-medium">$<?php echo enc_money($income->amount); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="border border-gray-200 rounded-lg bg-white">
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <h3 class="text-lg font-semibold">Recent Withdrawals</h3>
                <a class="text-sm text-blue-600 hover:text-blue-800" href="<?php echo esc_url(add_query_arg('tab', 'withdrawals', get_permalink())); ?>">View all</a>
            </div>
            <?php if (empty($recent_withdrawals)): ?>
                <p class="px-4 py-6 text-sm text-gray-500">No withdrawal activity for this period.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-600">Date</th>
                                <th class="px-4 py-2 text-left text-gray-600">Store</th>
                                <th class="px-4 py-2 text-right text-gray-600">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_withdrawals as $withdrawal): ?>
                                <tr class="border-t">
                                    <td class="px-4 py-2 text-gray-800"><?php echo esc_html($withdrawal->withdrawal_date); ?></td>
                                    <td class="px-4 py-2 text-gray-800"><?php echo esc_html($withdrawal->store_name); ?></td>
                                    <td class="px-4 py-2 text-right text-gray-900 font-medium">$<?php echo enc_money($withdrawal->amount); ?></td>
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