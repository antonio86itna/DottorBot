<?php
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <main class="prose mx-auto p-4">
        <?php
        echo do_shortcode('[dottorbot]');
        if (shortcode_exists('dottorbot_diary')) {
            echo do_shortcode('[dottorbot_diary]');
        }
        if (shortcode_exists('dottorbot_privacy')) {
            echo do_shortcode('[dottorbot_privacy]');
        }
        ?>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
