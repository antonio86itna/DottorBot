<?php
/**
 * Plugin Name: DottorBot
 * Description: Adds REST API endpoints and settings for DottorBot.
 * Version: 1.0.0
 * Author: DottorBot
 */

if (!defined('ABSPATH')) {
    exit;
}

// Create custom log table on activation.
register_activation_hook(__FILE__, 'dottorbot_install');
function dottorbot_install(): void {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'dottorbot_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        action VARCHAR(50) NOT NULL,
        data LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
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
 * Log events to custom table after scrubbing PII.
 */
function dottorbot_log_event(int $user_id, string $action, string $data = ''): void {
    global $wpdb;
    $table = $wpdb->prefix . 'dottorbot_logs';
    $wpdb->insert(
        $table,
        [
            'user_id'    => $user_id,
            'action'     => $action,
            'data'       => dottorbot_scrub_pii($data),
            'created_at' => current_time('mysql', 1),
        ]
    );
}

// Register REST API routes.
add_action('rest_api_init', function () {
    register_rest_route('dottorbot/v1', '/chat', [
        'methods'  => 'POST',
        'callback' => 'dottorbot_rest_chat',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('dottorbot/v1', '/diary', [
        'methods'  => 'POST',
        'callback' => 'dottorbot_rest_diary',
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
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
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

    $message  = (string) $request->get_param('message');
    $scrubbed = dottorbot_scrub_pii($message);
    dottorbot_log_event($user_id, 'chat', $scrubbed);

    return new WP_REST_Response(['message' => 'Chat endpoint placeholder'], 200);
}

function dottorbot_rest_diary(WP_REST_Request $request): WP_REST_Response {
    $user_id = get_current_user_id();
    if (!$user_id || !dottorbot_user_has_consented($user_id)) {
        return new WP_REST_Response(['error' => 'Consent required'], 403);
    }

    $entry    = (string) $request->get_param('entry');
    $scrubbed = dottorbot_scrub_pii($entry);
    dottorbot_log_event($user_id, 'diary', $scrubbed);

    return new WP_REST_Response(['message' => 'Diary endpoint placeholder'], 200);
}

function dottorbot_rest_export(WP_REST_Request $request): WP_REST_Response {
    $user_id = get_current_user_id();
    if (!$user_id || !dottorbot_user_has_consented($user_id)) {
        return new WP_REST_Response(['error' => 'Consent required'], 403);
    }

    dottorbot_log_event($user_id, 'export');
    return new WP_REST_Response(['message' => 'Export endpoint placeholder'], 200);
}

function dottorbot_rest_purge(WP_REST_Request $request): WP_REST_Response {
    $user_id = get_current_user_id();
    dottorbot_log_event($user_id, 'purge');
    return new WP_REST_Response(['message' => 'Purge endpoint placeholder'], 200);
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
});

add_action('admin_init', function () {
    register_setting('dottorbot_options', 'dottorbot_api_key');
    register_setting('dottorbot_options', 'dottorbot_default_model');
    register_setting('dottorbot_options', 'dottorbot_usage_limit');

    add_settings_section('dottorbot_main', __('General Settings', 'dottorbot'), '__return_false', 'dottorbot');

    add_settings_field('dottorbot_api_key', __('API Key', 'dottorbot'), 'dottorbot_render_api_key_field', 'dottorbot', 'dottorbot_main');
    add_settings_field('dottorbot_default_model', __('Default Model', 'dottorbot'), 'dottorbot_render_default_model_field', 'dottorbot', 'dottorbot_main');
    add_settings_field('dottorbot_usage_limit', __('Usage Limit', 'dottorbot'), 'dottorbot_render_usage_limit_field', 'dottorbot', 'dottorbot_main');
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

