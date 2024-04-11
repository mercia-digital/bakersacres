<?php
function md_enqueue_font_awesome_assets() {
    wp_enqueue_script('font-awesome-kit-js', '//kit.fontawesome.com/0c4ce5631f.js', null, null, true);
}
add_action('wp_enqueue_scripts', 'md_enqueue_font_awesome_assets');
add_action('admin_init', 'md_enqueue_font_awesome_assets');