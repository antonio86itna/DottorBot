<?php
/**
 * Plugin Name: DottorBot
 * Description: Adds REST API endpoints and settings for DottorBot.
 * Version: 1.0.0
 * Author: DottorBot
 * Text Domain: dottorbot
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'dottorbot_load_textdomain');
function dottorbot_load_textdomain(): void {
    load_plugin_textdomain('dottorbot', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Create custom log table on activation.
register_activation_hook(__FILE__, 'dottorbot_install');
function dottorbot_install(): void {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'dottorbot_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql_logs = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        hash CHAR(64) NOT NULL,
        action VARCHAR(50) NOT NULL,
        data LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) $charset_collate;";

    $diary_table = $wpdb->prefix . 'dottorbot_diary';
    $sql_diary  = "CREATE TABLE $diary_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        entry LONGTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_logs);
    dbDelta($sql_diary);
}

/**
 * Remove common PII from text using regex and optional NER.
 */
function dottorbot_scrub_pii(string $text): string {
    $patterns = [
        // Emails
        '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/' => '[email]',
        // Phone numbers (simple)
        '/\b\+?\d{1,3}[\s.-]?\d{3}[\s.-]?\d{3,4}[\s.-]?\d{3,4}\b/' => '[phone]',
        // Credit card numbers
        '/\b(?:\d[ -]*?){13,16}\b/'                                    => '[card]',
    ];

    foreach ($patterns as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }

    // Optional Named Entity Recognition if available.
    if (function_exists('ner_tag')) {
        try {
            $entities = ner_tag($text);
            if (is_array($entities)) {
                foreach ($entities as $entity) {
                    if (!empty($entity['tag']) && 'PERSON' === $entity['tag']) {
                        $text = str_replace($entity['word'], '[name]', $text);
                    }
                }
            }
        } catch (Throwable $e) {
            // Ignore NER failures.
        }
    }

    return $text;
}

/**
 * Store user consent in user meta.
 */
function dottorbot_save_consent(int $user_id, bool $consent): void {
    update_user_meta($user_id, 'dottorbot_consent', $consent ? 1 : 0);
}

function dottorbot_user_has_consented(int $user_id): bool {
    return (bool) get_user_meta($user_id, 'dottorbot_consent', true);
}

/**
 * Determine if a user has a premium subscription.
 */
function dottorbot_user_is_premium(int $user_id): bool {
    // WooCommerce Subscriptions check.
    if (function_exists('wcs_user_has_subscription')) {
        if (wcs_user_has_subscription($user_id, '', 'active')) {
            return true;
        }
    }

    // Fallback to user meta flag.
    return (bool) get_user_meta($user_id, 'dottorbot_premium', true);
}

function dottorbot_get_message_count(int $user_id): int {
    return (int) get_user_meta($user_id, 'dottorbot_message_count', true);
}

function dottorbot_increment_message_count(int $user_id): void {
    $count = dottorbot_get_message_count($user_id);
    update_user_meta($user_id, 'dottorbot_message_count', $count + 1);
}

function dottorbot_get_usage_limit(int $user_id): int {
    if (dottorbot_user_is_premium($user_id)) {
        return (int) get_option('dottorbot_premium_limit', 0);
    }
    return (int) get_option('dottorbot_usage_limit', 5);
}

/**
 * Retrieve or assign the A/B test variant for a user.
 */
function dottorbot_get_ab_variant(int $user_id): string {
    $variant = get_user_meta($user_id, 'dottorbot_ab_variant', true);
    if ($variant) {
        return (string) $variant;
    }
    $enabled = (bool) get_option('dottorbot_ab_enabled', 0);
    if (!$enabled) {
        $variant = 'A';
    } else {
        $ratio   = (int) get_option('dottorbot_ab_ratio', 50);
        $variant = (wp_rand(1, 100) <= $ratio) ? 'B' : 'A';
    }
    update_user_meta($user_id, 'dottorbot_ab_variant', $variant);
    dottorbot_log_event($user_id, 'ab_assign', $variant);
    return $variant;
}

/**
 * Create a salted hash for audit logging.
 */
function dottorbot_user_hash(int $user_id, string $action, string $data = ''): string {
    $salt = wp_salt('dottorbot_log');
    return hash('sha256', $salt . $user_id . $action . $data);
}

/**
 * Log events to custom table after scrubbing PII.
 */
function dottorbot_log_event(int $user_id, string $action, string $data = ''): void {
    global $wpdb;
    $table = $wpdb->prefix . 'dottorbot_logs';
    $wpdb->insert(
        $table,
        [
            'user_id'    => $user_id,
            'hash'       => dottorbot_user_hash($user_id, $action, $data),
            'action'     => $action,
            'data'       => dottorbot_scrub_pii($data),
            'created_at' => current_time('mysql', 1),
        ]
    );
    /**
     * Allow external analytics systems to hook into event logging.
     */
    do_action('dottorbot_event_logged', $user_id, $action, $data);
}

function dottorbot_diary_table(): string {
    global $wpdb;
    return $wpdb->prefix . 'dottorbot_diary';
}

function dottorbot_diary_key(): string {
    $secret = defined('DOTTORBOT_DIARY_KEY') ? DOTTORBOT_DIARY_KEY : AUTH_KEY;
    return hash('sha256', $secret, true);
}

function dottorbot_diary_encrypt(array $entry): string {
    $key = dottorbot_diary_key();
    $iv  = random_bytes(16);
    $payload = openssl_encrypt(json_encode($entry), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $payload);
}

function dottorbot_diary_decrypt(string $data): ?array {
    $key = dottorbot_diary_key();
    $raw = base64_decode($data, true);
    if (!$raw || strlen($raw) < 17) {
        return null;
    }
    $iv   = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $json = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $json ? json_decode($json, true) : null;
}

// Register REST API routes.
add_action('rest_api_init', function () {
    register_rest_route('dottorbot/v1', '/chat', [
        'methods'  => 'POST',
        'callback' => 'dottorbot_rest_chat',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('dottorbot/v1', '/stripe/checkout', [
        'methods'  => 'POST',
        'callback' => 'dottorbot_stripe_checkout',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    register_rest_route('dottorbot/v1', '/event', [
        'methods'  => 'POST',
        'callback' => 'dottorbot_rest_event',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('dottorbot/v1', '/diary', [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'dottorbot_diary_list',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('dottorbot/v1', '/diary', [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => 'dottorbot_diary_create',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('dottorbot/v1', '/diary/(?P<id>\d+)', [
        'methods'  => WP_REST_Server::EDITABLE,
        'callback' => 'dottorbot_diary_update',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('dottorbot/v1', '/diary/(?P<id>\d+)', [
        'methods'  => WP_REST_Server::DELETABLE,
        'callback' => 'dottorbot_diary_delete',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('dottorbot/v1', '/export', [
        'methods'  => 'GET',
        'callback' => 'dottorbot_rest_export',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('dottorbot/v1', '/purge', [
        'methods'  => 'DELETE',
        'callback' => 'dottorbot_rest_purge',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('dottorbot/v1', '/consent', [
        'methods'  => 'POST',
        'callback' => 'dottorbot_rest_consent',
        'permission_callback' => '__return_true',
    ]);
});

function dottorbot_rest_chat(WP_REST_Request $request): WP_REST_Response {
    $user_id = get_current_user_id();
    if (!$user_id || !dottorbot_user_has_consented($user_id)) {
        return new WP_REST_Response(['error' => 'Consent required'], 403);
    }

    $limit = dottorbot_get_usage_limit($user_id);
    $count = dottorbot_get_message_count($user_id);
    $is_premium = dottorbot_user_is_premium($user_id);
    $paywall = !$is_premium && $limit > 0 && $count >= $limit;

    $model = $paywall ? 'gpt-5-nano' : get_option('dottorbot_default_model', 'gpt-5');

    $message  = (string) $request->get_param('message');
    $scrubbed = dottorbot_scrub_pii($message);
    dottorbot_log_event($user_id, 'chat', $scrubbed);
    dottorbot_increment_message_count($user_id);

    $variant = dottorbot_get_ab_variant($user_id);

    $response = [
        'message' => 'Chat endpoint placeholder',
        'model'   => $model,
        'paywall' => $paywall,
        'variant' => $variant,
    ];
    if ($paywall) {
        $response['upgrade_url'] = rest_url('dottorbot/v1/stripe/checkout');
    }

    return new WP_REST_Response($response, 200);
}

function dottorbot_stripe_checkout(WP_REST_Request $request): WP_REST_Response {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_REST_Response(['error' => 'Unauthorized'], 401);
    }

    $secret = get_option('dottorbot_stripe_secret', getenv('STRIPE_SECRET_KEY'));
    $price  = get_option('dottorbot_stripe_price_id', getenv('STRIPE_PRICE_ID'));
    if (!$secret || !$price) {
        return new WP_REST_Response(['error' => 'Stripe not configured'], 500);
    }

    $success = add_query_arg('dottorbot-stripe', 'success', home_url());
    $cancel  = add_query_arg('dottorbot-stripe', 'cancel', home_url());

    $body = http_build_query([
        'mode' => 'subscription',
        'line_items[0][price]' => $price,
        'line_items[0][quantity]' => 1,
        'success_url' => $success,
        'cancel_url'  => $cancel,
        'client_reference_id' => $user_id,
    ]);

    $response = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $secret,
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
        'body' => $body,
    ]);

    if (is_wp_error($response)) {
        return new WP_REST_Response(['error' => $response->get_error_message()], 500);
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $url = $data['url'] ?? '';
    if (!$url) {
        return new WP_REST_Response(['error' => 'Stripe error'], 500);
    }

    return new WP_REST_Response(['url' => $url], 200);
}

function dottorbot_rest_event(WP_REST_Request $request): WP_REST_Response {
    $user_id = get_current_user_id();
    if (!$user_id || !dottorbot_user_has_consented($user_id)) {
        return new WP_REST_Response(['error' => 'Consent required'], 403);
    }
    $action = sanitize_text_field($request->get_param('action'));
    $data   = (string) $request->get_param('data');
    if ($action) {
        dottorbot_log_event($user_id, $action, $data);
    }
    return new WP_REST_Response(['status' => 'ok'], 200);
}

function dottorbot_diary_list(WP_REST_Request $request): WP_REST_Response {
    $user_id = get_current_user_id();
    if (!$user_id || !dottorbot_user_has_consented($user_id)) {
        return new WP_REST_Response(['error' => 'Consent required'], 403);
    }
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare('SELECT id, entry FROM ' . dottorbot_diary_table() . ' WHERE user_id=%d ORDER BY created_at ASC', $user_id), ARRAY_A);
    $entries = [];
    foreach ($rows as $row) {
        $data = dottorbot_diary_decrypt($row['entry']);
        if ($data) {
            $data['id'] = (int) $row['id'];
            $entries[]  = $data;
        }
    }
    dottorbot_log_event($user_id, 'diary_open');
    return new WP_REST_Response($entries, 200);
}

function dottorbot_diary_create(WP_REST_Request $request): WP_REST_Response {
    $user_id = get_current_user_id();
    if (!$user_id || !dottorbot_user_has_consented($user_id)) {
        return new WP_REST_Response(['error' => 'Consent required'], 403);
    }
    $entry = [
        'timestamp' => (int) ($request->get_param('timestamp') ?? time()),
        'mood'      => (int) $request->get_param('mood'),
        'symptoms'  => (int) $request->get_param('symptoms'),
        'notes'     => (string) $request->get_param('notes'),
    ];
    $enc = dottorbot_diary_encrypt($entry);
    global $wpdb;
    $wpdb->insert(dottorbot_diary_table(), [
        'user_id'    => $user_id,
        'entry'      => $enc,
        'created_at' => current_time('mysql', 1),
    ]);
    $entry['id'] = (int) $wpdb->insert_id;
    dottorbot_log_event($user_id, 'diary_create');
    dottorbot_update_badges($user_id);
    return new WP_REST_Response($entry, 201);
}

function dottorbot_diary_update(WP_REST_Request $request): WP_REST_Response {
    $user_id = get_current_user_id();
    if (!$user_id || !dottorbot_user_has_consented($user_id)) {
        return new WP_REST_Response(['error' => 'Consent required'], 403);
    }
    $id    = (int) $request['id'];
    $entry = [
        'timestamp' => (int) ($request->get_param('timestamp') ?? time()),
        'mood'      => (int) $request->get_param('mood'),
        'symptoms'  => (int) $request->get_param('symptoms'),
        'notes'     => (string) $request->get_param('notes'),
    ];
    $enc = dottorbot_diary_encrypt($entry);
    global $wpdb;
    $wpdb->update(dottorbot_diary_table(), ['entry' => $enc], ['id' => $id, 'user_id' => $user_id]);
    $entry['id'] = $id;
    dottorbot_log_event($user_id, 'diary_update');
    return new WP_REST_Response($entry, 200);
}

function dottorbot_diary_delete(WP_REST_Request $request): WP_REST_Response {
    $user_id = get_current_user_id();
    if (!$user_id || !dottorbot_user_has_consented($user_id)) {
        return new WP_REST_Response(['error' => 'Consent required'], 403);
    }
    $id = (int) $request['id'];
    global $wpdb;
    $wpdb->delete(dottorbot_diary_table(), ['id' => $id, 'user_id' => $user_id]);
    dottorbot_log_event($user_id, 'diary_delete');
    return new WP_REST_Response(['deleted' => true], 200);
}

function dottorbot_diary_fetch_all(int $user_id): array {
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare('SELECT id, entry FROM ' . dottorbot_diary_table() . ' WHERE user_id=%d ORDER BY created_at ASC', $user_id), ARRAY_A);
    $entries = [];
    foreach ($rows as $row) {
        $data = dottorbot_diary_decrypt($row['entry']);
        if ($data) {
            $data['id'] = (int) $row['id'];
            $entries[]  = $data;
        }
    }
    return $entries;
}

function dottorbot_rest_export(WP_REST_Request $request): WP_REST_Response {
    $user_id = get_current_user_id();
    if (!$user_id || !dottorbot_user_has_consented($user_id)) {
        return new WP_REST_Response(['error' => 'Consent required'], 403);
    }

    $format  = strtolower((string) $request->get_param('format'));
    $entries = dottorbot_diary_fetch_all($user_id);
    dottorbot_log_event($user_id, 'export', $format ?: 'json');

    if ('csv' === $format) {
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['id', 'timestamp', 'mood', 'symptoms', 'notes']);
        foreach ($entries as $e) {
            fputcsv($fh, [$e['id'], $e['timestamp'], $e['mood'], $e['symptoms'], $e['notes']]);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return new WP_REST_Response($csv, 200, ['Content-Type' => 'text/csv']);
    }

    return new WP_REST_Response($entries, 200);
}

function dottorbot_rest_purge(WP_REST_Request $request): WP_REST_Response {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_REST_Response(['error' => 'Unauthorized'], 401);
    }

    global $wpdb;
    $wpdb->delete(dottorbot_diary_table(), ['user_id' => $user_id]);
    delete_user_meta($user_id, 'dottorbot_message_count');
    delete_user_meta($user_id, 'dottorbot_weekly_notes');
    delete_user_meta($user_id, 'dottorbot_consent');

    dottorbot_log_event($user_id, 'purge');
    return new WP_REST_Response(['purged' => true], 200);
}

function dottorbot_rest_consent(WP_REST_Request $request): WP_REST_Response {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_REST_Response(['error' => 'Unauthorized'], 401);
    }

    $consent = (bool) $request->get_param('consent');
    dottorbot_save_consent($user_id, $consent);
    dottorbot_log_event($user_id, 'consent', $consent ? 'granted' : 'denied');

    return new WP_REST_Response(['consent' => $consent], 200);
}

// Settings page.
add_action('admin_menu', function () {
    add_options_page(
        __('DottorBot Settings', 'dottorbot'),
        __('DottorBot', 'dottorbot'),
        'manage_options',
        'dottorbot',
        'dottorbot_render_settings_page'
    );

    add_options_page(
        __('DottorBot Analytics', 'dottorbot'),
        __('DottorBot Analytics', 'dottorbot'),
        'manage_options',
        'dottorbot-analytics',
        'dottorbot_render_analytics_page'
    );
});

add_action('admin_init', function () {
    register_setting('dottorbot_options', 'dottorbot_api_key');
    register_setting('dottorbot_options', 'dottorbot_default_model');
    register_setting('dottorbot_options', 'dottorbot_usage_limit');
    register_setting('dottorbot_options', 'dottorbot_premium_limit');
    register_setting('dottorbot_options', 'dottorbot_stripe_price_id');
    register_setting('dottorbot_options', 'dottorbot_stripe_secret');

    register_setting('dottorbot_ab', 'dottorbot_ab_enabled');
    register_setting('dottorbot_ab', 'dottorbot_ab_ratio');

    add_settings_section('dottorbot_main', __('General Settings', 'dottorbot'), '__return_false', 'dottorbot');

    add_settings_field('dottorbot_api_key', __('API Key', 'dottorbot'), 'dottorbot_render_api_key_field', 'dottorbot', 'dottorbot_main');
    add_settings_field('dottorbot_default_model', __('Default Model', 'dottorbot'), 'dottorbot_render_default_model_field', 'dottorbot', 'dottorbot_main');
    add_settings_field('dottorbot_usage_limit', __('Usage Limit', 'dottorbot'), 'dottorbot_render_usage_limit_field', 'dottorbot', 'dottorbot_main');
    add_settings_field('dottorbot_premium_limit', __('Premium Usage Limit', 'dottorbot'), 'dottorbot_render_premium_limit_field', 'dottorbot', 'dottorbot_main');
    add_settings_field('dottorbot_stripe_price_id', __('Stripe Price ID', 'dottorbot'), 'dottorbot_render_stripe_price_id_field', 'dottorbot', 'dottorbot_main');
    add_settings_field('dottorbot_stripe_secret', __('Stripe Secret Key', 'dottorbot'), 'dottorbot_render_stripe_secret_field', 'dottorbot', 'dottorbot_main');
});

function dottorbot_render_settings_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('DottorBot Settings', 'dottorbot'); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('dottorbot_options');
            do_settings_sections('dottorbot');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function dottorbot_render_api_key_field(): void {
    $value = get_option('dottorbot_api_key', '');
    echo '<input type="text" name="dottorbot_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
}

function dottorbot_render_default_model_field(): void {
    $value = get_option('dottorbot_default_model', '');
    echo '<input type="text" name="dottorbot_default_model" value="' . esc_attr($value) . '" class="regular-text" />';
}

function dottorbot_render_usage_limit_field(): void {
    $value = get_option('dottorbot_usage_limit', '');
    echo '<input type="number" name="dottorbot_usage_limit" value="' . esc_attr($value) . '" class="regular-text" />';
}

function dottorbot_render_premium_limit_field(): void {
    $value = get_option('dottorbot_premium_limit', '');
    echo '<input type="number" name="dottorbot_premium_limit" value="' . esc_attr($value) . '" class="regular-text" />';
}

function dottorbot_render_stripe_price_id_field(): void {
    $value = get_option('dottorbot_stripe_price_id', '');
    echo '<input type="text" name="dottorbot_stripe_price_id" value="' . esc_attr($value) . '" class="regular-text" />';
}

function dottorbot_render_stripe_secret_field(): void {
    $value = get_option('dottorbot_stripe_secret', '');
    echo '<input type="text" name="dottorbot_stripe_secret" value="' . esc_attr($value) . '" class="regular-text" />';
}

function dottorbot_render_analytics_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }
    global $wpdb;
    $table = $wpdb->prefix . 'dottorbot_logs';
    $rows  = $wpdb->get_results("SELECT action, COUNT(*) AS total FROM {$table} GROUP BY action ORDER BY total DESC", ARRAY_A);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('DottorBot Analytics', 'dottorbot'); ?></h1>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Event', 'dottorbot'); ?></th>
                    <th><?php esc_html_e('Count', 'dottorbot'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($rows) {
                    foreach ($rows as $row) {
                        echo '<tr><td>' . esc_html($row['action']) . '</td><td>' . esc_html($row['total']) . '</td></tr>';
                    }
                } else {
                    echo '<tr><td colspan="2">' . esc_html__('No data', 'dottorbot') . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <h2><?php esc_html_e('A/B Test', 'dottorbot'); ?></h2>
        <form action="options.php" method="post">
            <?php settings_fields('dottorbot_ab'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable A/B Test', 'dottorbot'); ?></th>
                    <td><input type="checkbox" name="dottorbot_ab_enabled" value="1" <?php checked(1, get_option('dottorbot_ab_enabled', 0)); ?> /></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Variant B Percentage', 'dottorbot'); ?></th>
                    <td><input type="number" name="dottorbot_ab_ratio" value="<?php echo esc_attr(get_option('dottorbot_ab_ratio', 50)); ?>" min="0" max="100" /> %</td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Update weekly note streaks and badges.
 */
function dottorbot_update_badges(int $user_id): void {
    global $wpdb;
    $table = dottorbot_diary_table();
    $count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY)",
            $user_id
        )
    );

    update_user_meta($user_id, 'dottorbot_weekly_notes', $count);

    $current_week = gmdate('oW');
    $last_week    = get_user_meta($user_id, 'dottorbot_last_badge_week', true);

    if ($count >= 3 && $last_week !== $current_week) {
        $prev_week = gmdate('oW', strtotime('-7 days'));
        $streak    = ($last_week === $prev_week) ? (int) get_user_meta($user_id, 'dottorbot_streak', true) + 1 : 1;
        update_user_meta($user_id, 'dottorbot_streak', $streak);
        update_user_meta($user_id, 'dottorbot_last_badge_week', $current_week);

        $badges = (array) get_user_meta($user_id, 'dottorbot_badges', true);
        if (!in_array('weekly_3_notes', $badges, true)) {
            $badges[] = 'weekly_3_notes';
            update_user_meta($user_id, 'dottorbot_badges', $badges);
        }

        update_user_meta($user_id, 'dottorbot_premium', 1);
        update_user_meta($user_id, 'dottorbot_show_badge_notification', 1);
    } elseif ($count < 3 && $last_week && $last_week !== $current_week) {
        $prev_week = gmdate('oW', strtotime('-7 days'));
        if ($last_week !== $prev_week) {
            update_user_meta($user_id, 'dottorbot_streak', 0);
        }
    }
}

/**
 * Shortcode to render progress ring for weekly notes.
 */
function dottorbot_render_progress_shortcode(): string {
    if (!is_user_logged_in()) {
        return '';
    }
    $user_id = get_current_user_id();
    $count   = (int) get_user_meta($user_id, 'dottorbot_weekly_notes', true);
    $max     = 3;
    ob_start();
    ?>
    <div id="dottorbot-progress" data-count="<?php echo esc_attr($count); ?>">
        <svg class="dottorbot-progress-ring" width="120" height="120">
            <circle class="dottorbot-progress-ring__bg" stroke="#eee" stroke-width="10" fill="transparent" r="52" cx="60" cy="60"></circle>
            <circle class="dottorbot-progress-ring__circle" stroke="#4caf50" stroke-width="10" fill="transparent" r="52" cx="60" cy="60"></circle>
        </svg>
        <div class="dottorbot-progress-ring__text"><?php echo esc_html($count . '/' . $max); ?></div>
    </div>
    <style>
        .dottorbot-progress-ring { position: relative; }
        .dottorbot-progress-ring__circle { transition: stroke-dashoffset 0.35s; transform: rotate(-90deg); transform-origin: 50% 50%; }
        .dottorbot-progress-ring__text { position:absolute; top:0; left:0; width:120px; height:120px; display:flex; align-items:center; justify-content:center; font-weight:bold; }
        .dottorbot-badge-notice { position:fixed; bottom:20px; right:20px; background:#4caf50; color:#fff; padding:10px 20px; border-radius:4px; }
    </style>
    <script>
    (function(){
        var container = document.getElementById('dottorbot-progress');
        if (!container) return;
        var count = parseInt(container.dataset.count, 10) || 0;
        var max = <?php echo (int) $max; ?>;
        var circle = container.querySelector('.dottorbot-progress-ring__circle');
        var radius = circle.r.baseVal.value;
        var circumference = 2 * Math.PI * radius;
        circle.style.strokeDasharray = circumference;
        circle.style.strokeDashoffset = circumference;
        var percent = Math.min(count / max, 1);
        circle.style.strokeDashoffset = circumference - percent * circumference;
    })();
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Display badge notification when earned.
 */
function dottorbot_render_badge_notification(): void {
    if (!is_user_logged_in()) {
        return;
    }
    $user_id = get_current_user_id();
    if (get_user_meta($user_id, 'dottorbot_show_badge_notification', true)) {
        echo '<div class="dottorbot-badge-notice">' . esc_html__('Badge settimanale sbloccato! Contenuti premium attivati.', 'dottorbot') . '</div>';
        delete_user_meta($user_id, 'dottorbot_show_badge_notification');
    }
}

add_shortcode('dottorbot_progress', 'dottorbot_render_progress_shortcode');
add_action('wp_footer', 'dottorbot_render_badge_notification');

/**
 * Retrieve user preferences for tone and detail level.
 */
function dottorbot_get_user_preferences(int $user_id): array {
    $tone   = get_user_meta($user_id, 'dottorbot_tone', true) ?: 'semplice';
    $detail = get_user_meta($user_id, 'dottorbot_detail', true) ?: 'breve';
    return array(
        'tone'   => $tone,
        'detail' => $detail,
    );
}

/**
 * Display preference fields on user profile.
 */
function dottorbot_user_preferences_fields($user): void {
    $prefs  = dottorbot_get_user_preferences($user->ID);
    $tone   = $prefs['tone'];
    $detail = $prefs['detail'];
    ?>
    <h2><?php echo esc_html(__('Preferenze DottorBot', 'dottorbot')); ?></h2>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="dottorbot_tone"><?php echo esc_html(__('Tono delle risposte', 'dottorbot')); ?></label></th>
            <td>
                <select name="dottorbot_tone" id="dottorbot_tone">
                    <option value="semplice" <?php selected($tone, 'semplice'); ?>><?php echo esc_html(__('Semplice', 'dottorbot')); ?></option>
                    <option value="esperto" <?php selected($tone, 'esperto'); ?>><?php echo esc_html(__('Esperto', 'dottorbot')); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="dottorbot_detail"><?php echo esc_html(__('Livello di dettaglio', 'dottorbot')); ?></label></th>
            <td>
                <select name="dottorbot_detail" id="dottorbot_detail">
                    <option value="breve" <?php selected($detail, 'breve'); ?>><?php echo esc_html(__('Breve', 'dottorbot')); ?></option>
                    <option value="approfondito" <?php selected($detail, 'approfondito'); ?>><?php echo esc_html(__('Approfondito', 'dottorbot')); ?></option>
                </select>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'dottorbot_user_preferences_fields');
add_action('edit_user_profile', 'dottorbot_user_preferences_fields');

/**
 * Save user preferences from profile form.
 */
function dottorbot_save_user_preferences(int $user_id): void {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }
    $tone   = isset($_POST['dottorbot_tone']) ? sanitize_text_field(wp_unslash($_POST['dottorbot_tone'])) : 'semplice';
    $detail = isset($_POST['dottorbot_detail']) ? sanitize_text_field(wp_unslash($_POST['dottorbot_detail'])) : 'breve';
    update_user_meta($user_id, 'dottorbot_tone', in_array($tone, array('semplice', 'esperto'), true) ? $tone : 'semplice');
    update_user_meta($user_id, 'dottorbot_detail', in_array($detail, array('breve', 'approfondito'), true) ? $detail : 'breve');
}
add_action('personal_options_update', 'dottorbot_save_user_preferences');
add_action('edit_user_profile_update', 'dottorbot_save_user_preferences');

/**
 * Enqueue front-end tracking script for source clicks.
 */
function dottorbot_enqueue_scripts(): void {
    if (!is_user_logged_in()) {
        return;
    }
    wp_register_script('dottorbot-tracking', '', [], false, true);
    wp_enqueue_script('dottorbot-tracking');
    $url   = esc_url_raw(rest_url('dottorbot/v1/event'));
    $nonce = wp_create_nonce('wp_rest');
    $script = sprintf(
        "document.addEventListener('click',function(e){var l=e.target.closest('[data-dottorbot-source]');if(!l){return;}fetch('%s',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-WP-Nonce':'%s'},body:JSON.stringify({action:'source_click',data:l.href})});});",
        $url,
        $nonce
    );
    wp_add_inline_script('dottorbot-tracking', $script);
}
add_action('wp_enqueue_scripts', 'dottorbot_enqueue_scripts');

