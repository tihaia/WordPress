<?php
function usm_theme_enqueue_styles() {
    wp_enqueue_style('usm-theme-style', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'usm_theme_enqueue_styles');