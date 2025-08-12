<?php
function dottorbot_enqueue_assets() {
    $theme_dir = get_template_directory_uri();
    $style_path = get_template_directory() . '/dist/style.css';
    if (file_exists($style_path)) {
        wp_enqueue_style('dottorbot-theme', $theme_dir . '/dist/style.css', array(), filemtime($style_path));
    }
    $script_path = get_template_directory() . '/dist/chat.js';
    if (file_exists($script_path)) {
        wp_enqueue_script('dottorbot-chat', $theme_dir . '/dist/chat.js', array(), filemtime($script_path), true);
    }
}
add_action('wp_enqueue_scripts', 'dottorbot_enqueue_assets');

function dottorbot_render_shortcode() {
    wp_enqueue_script('dottorbot-chat');
    return '<div id="dottorbot-chat"></div>';
}
add_shortcode('dottorbot', 'dottorbot_render_shortcode');

function dottorbot_register_block() {
    wp_register_script(
        'dottorbot-block-editor',
        get_template_directory_uri() . '/block/index.js',
        array('wp-blocks', 'wp-element'),
        filemtime(get_template_directory() . '/block/index.js')
    );
    register_block_type('dottorbot/chat', array(
        'editor_script' => 'dottorbot-block-editor',
        'render_callback' => 'dottorbot_render_shortcode',
    ));
}
add_action('init', 'dottorbot_register_block');
