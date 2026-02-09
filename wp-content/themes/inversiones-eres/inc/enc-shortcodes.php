<?php
/**
 * Administrator Portal (ENC Tracker)
 *
 * Shortcodes:
 *  - [enc_app]
 *  - [enc_tracker]
 */

add_shortcode('enc_app', 'enc_administrator_portal_shortcode');
add_shortcode('enc_tracker', 'enc_administrator_portal_shortcode');

function enc_administrator_portal_shortcode($atts = [])
{
    // Require login
    if (!is_user_logged_in()) {
        $login_url = wp_login_url(get_permalink());
        return '
        <div class="min-h-[60vh] ">
            <div class="mx-auto max-w-5xl px-4 py-10">
                <div class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Administrator Portal</h2>
                    <p class="mt-1 text-sm text-slate-600">Please log in to continue.</p>
                    <a class="mt-5 inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition no-underline"
                       href="' . esc_url($login_url) . '">Log in</a>
                </div>
            </div>
        </div>';
    }

    // Admin-only access
    if (!current_user_can('manage_options')) {
        return '
        <div class="min-h-[60vh] bg-slate-50">
            <div class="mx-auto max-w-5xl px-4 py-10">
                <div class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Administrator Portal</h2>
                    <p class="mt-2 text-sm text-slate-700">This area is restricted to administrators.</p>
                </div>
            </div>
        </div>';
    }

    // Tabs (admins can access all)
    $tabs = [
        'dashboard'   => ['label' => __('Dashboard', 'enc'),   'render' => 'enc_dashboard_view'],
        'incomes'     => ['label' => __('Incomes', 'enc'),     'render' => 'enc_incomes_view'],
        'withdrawals' => ['label' => __('Withdrawals', 'enc'), 'render' => 'enc_withdrawals_view'],
        'invoices'    => ['label' => __('Invoices', 'enc'),    'render' => 'enc_invoices_view'],
    ];

    $current_tab = sanitize_key($_GET['tab'] ?? 'dashboard');
    if (!isset($tabs[$current_tab])) {
        $current_tab = 'dashboard';
    }

    $base_url = get_permalink();
    $user = wp_get_current_user();

    ob_start(); ?>
    <div class="bg-slate-50">
        <div class="mx-auto max-w-7xl px-4 py-10 space-y-6">

            <!-- Header -->
            <header class="rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">
                            <?php esc_html_e('Administrator Portal', 'enc'); ?>
                        </p>
                        <h1 class="text-2xl font-semibold text-slate-900">
                            <?php esc_html_e('ENC Tracker', 'enc'); ?>
                        </h1>
                        <p class="text-sm text-slate-600">
                            <?php echo esc_html__('Signed in as', 'enc'); ?>
                            <span class="font-semibold text-slate-800"><?php echo esc_html($user->display_name ?: $user->user_login); ?></span>
                        </p>
                    </div>

                    <nav class="flex flex-wrap items-center gap-2">
                        <?php foreach ($tabs as $key => $cfg): ?>
                            <?php
                            $url = add_query_arg('tab', $key, $base_url);
                            $active = ($current_tab === $key);
                            $classes = $active
                                ? 'bg-slate-900 text-white ring-slate-900'
                                : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50';
                            ?>
                            <a href="<?php echo esc_url($url); ?>"
                               class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold no-underline ring-1 transition <?php echo esc_attr($classes); ?>">
                                <?php echo esc_html($cfg['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </header>

            <!-- Content -->
            <main class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <?php
                $renderer = $tabs[$current_tab]['render'];
                if (is_callable($renderer)) {
                    echo call_user_func($renderer);
                } else {
                    echo '<p class="text-slate-700">' . esc_html__('This view is not available.', 'enc') . '</p>';
                }
                ?>
            </main>

        </div>
    </div>
    <?php
    return ob_get_clean();
}
