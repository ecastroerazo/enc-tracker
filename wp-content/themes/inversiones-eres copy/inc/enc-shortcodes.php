<?php
/**
 * ENC Tracker Shortcodes
 */

// Main app shortcode [enc_app]
add_shortcode('enc_app', 'enc_tracker_shortcode');
add_shortcode('enc_tracker', 'enc_tracker_shortcode');
function enc_tracker_shortcode($atts = []) {
    if (!is_user_logged_in()) {
        return '<p>Please log in to access this content.</p>';
    }
    
    $current_tab = sanitize_key($_GET['tab'] ?? 'dashboard');
    $valid_tabs = ['dashboard', 'incomes', 'withdrawals'];
    
    if (!in_array($current_tab, $valid_tabs)) {
        $current_tab = 'dashboard';
    }

    $can_view_reports = current_user_can('enc_view_reports') || current_user_can('manage_options');
    $can_manage_withdrawals = current_user_can('enc_manage_withdrawals') || current_user_can('manage_options');
    $tab_permissions = [
        'dashboard' => true,
        'incomes' => $can_view_reports,
        'withdrawals' => $can_manage_withdrawals,
    ];
    
    ob_start();
    ?>
    <div class="max-w-6xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">ENC Tracker</h1>
        
        <!-- Navigation -->
        <div class="flex gap-2 flex-wrap mb-6">
            <?php
            $tabs = [
                'dashboard' => 'Dashboard',
                'incomes' => 'Incomes',
                'withdrawals' => 'Withdrawals'
            ];
            
            foreach ($tabs as $tab => $label):
                if (empty($tab_permissions[$tab])) {
                    continue;
                }
                $url = add_query_arg('tab', $tab, get_permalink());
                $active = ($current_tab === $tab) ? ' font-bold border-black bg-gray-100' : ' border-gray-300 hover:bg-gray-50';
                ?>
                <a href="<?php echo esc_url($url); ?>" class="px-4 py-2 rounded-full border no-underline inline-block transition-colors<?php echo $active; ?>">
                    <?php echo esc_html($label); ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Content -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <?php
            switch ($current_tab) {
                case 'dashboard':
                    echo enc_dashboard_view();
                    break;
                case 'incomes':
                    if (!$can_view_reports) {
                        echo '<p>You do not have permission to view income reports.</p>';
                    } else {
                        echo enc_incomes_view();
                    }
                    break;
                case 'withdrawals':
                    if (!$can_manage_withdrawals) {
                        echo '<p>You do not have permission to view withdrawal reports.</p>';
                    } else {
                        echo enc_withdrawals_view();
                    }
                    break;
                default:
                    echo enc_dashboard_view();
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
