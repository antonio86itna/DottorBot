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
});

function dottorbot_rest_chat(WP_REST_Request $request): WP_REST_Response {
    return new WP_REST_Response(['message' => 'Chat endpoint placeholder'], 200);
}

function dottorbot_rest_diary(WP_REST_Request $request): WP_REST_Response {
    return new WP_REST_Response(['message' => 'Diary endpoint placeholder'], 200);
}

function dottorbot_rest_export(WP_REST_Request $request): WP_REST_Response {
    return new WP_REST_Response(['message' => 'Export endpoint placeholder'], 200);
}

function dottorbot_rest_purge(WP_REST_Request $request): WP_REST_Response {
    return new WP_REST_Response(['message' => 'Purge endpoint placeholder'], 200);
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

