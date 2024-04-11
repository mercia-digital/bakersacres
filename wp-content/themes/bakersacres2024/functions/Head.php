<?php
function md_preconnect() { ?>
  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link rel="preconnect" href="https://api.fontshare.com" crossorigin> <?php
}
add_action('wp_head', 'md_preconnect');

function bakers_enqueue_site_js() {
  wp_enqueue_script('jquery');
  wp_enqueue_script('bakers-js', get_template_directory_uri() . '/site.js', array('jquery', 'rough-js'), '1.0', true);
  wp_enqueue_script('rough-js', get_template_directory_uri() . '/js/rough.js', array('jquery'), '4.6.6', true);
}

add_action('wp_enqueue_scripts', 'bakers_enqueue_site_js');