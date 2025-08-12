<?php
require_once get_template_directory() . '/api.php';

function dottorbot_theme_setup() {
    load_theme_textdomain('dottorbot', get_template_directory() . '/languages');
}
add_action('after_setup_theme', 'dottorbot_theme_setup');

function dottorbot_add_manifest() {
    echo '<link rel="manifest" href="' . esc_url(get_template_directory_uri() . '/manifest.json') . '">';
}
add_action('wp_head', 'dottorbot_add_manifest');

function dottorbot_enqueue_assets() {
    $theme_dir = get_template_directory_uri();
    $style_path = get_template_directory() . '/dist/style.css';
    if (file_exists($style_path)) {
        wp_enqueue_style('dottorbot-theme', $theme_dir . '/dist/style.css', array(), filemtime($style_path));
    }
    $chat_path = get_template_directory() . '/dist/chat.js';
    if (file_exists($chat_path)) {
        wp_enqueue_script('dottorbot-chat', $theme_dir . '/dist/chat.js', array(), filemtime($chat_path), true);
        wp_localize_script('dottorbot-chat', 'dottorbotChat', array(
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    $privacy_path = get_template_directory() . '/dist/privacy.js';
    if (file_exists($privacy_path)) {
        wp_enqueue_script('dottorbot-privacy', $theme_dir . '/dist/privacy.js', array(), filemtime($privacy_path), true);
        wp_localize_script('dottorbot-privacy', 'dottorbotPrivacy', array(
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    $diary_path = get_template_directory() . '/dist/diary.js';
    if (file_exists($diary_path)) {
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);
        wp_enqueue_script('jspdf', 'https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js', array(), null, true);
        wp_enqueue_script('dottorbot-diary', $theme_dir . '/dist/diary.js', array('chartjs', 'jspdf'), filemtime($diary_path), true);
        wp_localize_script('dottorbot-diary', 'dottorbotDiary', array(
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    $pwa_path = get_template_directory() . '/dist/pwa.js';
    if (file_exists($pwa_path)) {
        wp_enqueue_script('dottorbot-pwa', $theme_dir . '/dist/pwa.js', array(), filemtime($pwa_path), true);
        dottorbot_localize_pwa();
    }
}
add_action('wp_enqueue_scripts', 'dottorbot_enqueue_assets');

function dottorbot_register_block() {
    wp_register_script(
        'dottorbot-block-editor',
        get_template_directory_uri() . '/block/index.js',
        array('wp-blocks', 'wp-element'),
        filemtime(get_template_directory() . '/block/index.js')
    );
    register_block_type('dottorbot/chat', array(
        'editor_script' => 'dottorbot-block-editor',
        'render_callback' => 'dottorbot_render_chat_shortcode',
    ));
}
add_action('init', 'dottorbot_register_block');

function dottorbot_localize_pwa() {
    wp_localize_script('dottorbot-pwa', 'dottorbotPwa', array(
        'swUrl' => get_template_directory_uri() . '/service-worker.js',
        'restUrl' => rest_url('dottorbot/v1/subscribe'),
        'vapidPublicKey' => dottorbot_get_vapid_public_key(),
    ));
}

function dottorbot_schedule_reminder() {
    if (!wp_next_scheduled('dottorbot_daily_reminder')) {
        wp_schedule_event(time(), 'daily', 'dottorbot_daily_reminder');
    }
}
add_action('init', 'dottorbot_schedule_reminder');
