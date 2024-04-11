<?php
function md_register_custom_menus() {
    register_nav_menus(
        array(
            'main-nav-left' => __('Primary Navigation Left'),
            'main-nav-right' => __('Primary Navigation Right'),
            'mobile-main-nav' => __('Mobile Navigation'),
        )
    );
}
add_action('after_setup_theme', 'md_register_custom_menus');