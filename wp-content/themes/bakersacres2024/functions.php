<?php
require "functions/QoL.php";
require "functions/Head.php";
require "functions/Menus.php";
require "functions/AddOns.php";
require "js/roughConfigurator.php";

if ( ! function_exists( 'bka_styles' ) ) {
	function bka_styles() {
		// Register theme stylesheet.
		$theme_version = wp_get_theme()->get( 'Version' );

        $version_string = is_string( $theme_version ) ? $theme_version : false;		
		wp_register_style(
			'bka-style',
			get_template_directory_uri() . '/style.css',
			array(),
			$version_string
		);

		// Enqueue theme stylesheet.
		wp_enqueue_style( 'bka-style' );
	}
}
add_action( 'wp_enqueue_scripts', 'bka_styles' );

function bka_register_widgets() {
 
	register_sidebar( array(
	 'name' => __( 'Footer', 'bka' ),
	 'id' => 'footer-widget-area',
	 'description' => __( 'Footer Content', 'bka' ),
	 'before_widget' => '<div id="%1$s" class="widget-container %2$s">',
	 'after_widget' => '</div>',
	 'before_title' => '<h3 class="widget-title">',
	 'after_title' => '</h3>',
   
	) );
   
}
   
add_action( 'widgets_init', 'bka_register_widgets' );

//hours shortcode
function format_business_hours($hours) {
    $formatted_hours = [];
    $current_group = [];
    $previous_hours = '';

    foreach ($hours as $day) {
        $day_hours = $day['closed'] ? 'Closed' : $day['open'] . '-' . $day['close'];

        if ($day_hours === $previous_hours) {
            // Continue the current group
            $current_group['end'] = ucwords($day['day']['value']);
        } else {
            // Save previous group if exists
            if (!empty($current_group)) {
                $formatted_hours[] = format_group($current_group);
            }
            // Start a new group
            $current_group = [
                'start' => ucwords($day['day']['value']),
                'end' => ucwords($day['day']['value']),
                'hours' => $day_hours,
            ];
        }

        $previous_hours = $day_hours;
    }

    // Add the last group to the list
    if (!empty($current_group)) {
        $formatted_hours[] = format_group($current_group);
    }

    return implode("<br>", $formatted_hours);
}

function format_group($group) {
    if ($group['start'] === $group['end']) {
        return "<span class='days'>".$group['start'] . '</span> <span class="hours">' . $group['hours'].'</span>';
    } else {
        return "<span class='days'>".$group['start'] . '-' . $group['end'] . '</span> <span class="hours">' . $group['hours'].'</span>';
    }
}

function shortcode_bka_hours($atts) {
	$a = shortcode_atts( array(
		'class' => '',
	), $atts );

    $business_hours = get_field('hours', 'option');

    return "<div class='bka-hours ".$a['class']."'>".format_business_hours($business_hours)."</div>";
}
add_shortcode('bka-hours', 'shortcode_bka_hours');

// function md_site_favicon() {
// 	if (function_exists('acf')) {
// 		$favicon_url = get_field('site_favicon', 'option');
// 	} else {
// 		$favicon_url = get_template_directory_uri() . '/favicon.ico';
// 	}    

//     echo '<link rel="icon" href="' . esc_url($favicon_url) . '" type="image/x-icon" />';
// }
// add_action('wp_head', 'md_site_favicon');

//editor styles
add_action( 'enqueue_block_editor_assets', function() {
    wp_enqueue_style( 'md_editor_styles', get_stylesheet_directory_uri() . "/block-editor.css", false, '1.0', 'all' );
} );

//varieties posts per page
function variety_posts_per_page($query) {
	$varietiesPPP = 50;
	$postTypeTarget = 'variety';

    if (!is_admin() && $query->is_main_query()) {
		if (is_post_type_archive($postTypeTarget) || is_tax(get_object_taxonomies('variety', 'names'))) {
            $query->set('posts_per_page', $varietiesPPP);
		}
	}
}
add_action('pre_get_posts', 'variety_posts_per_page');

//varieties post order
function variety_posts_order($query) {
	$postTypeTarget = 'variety';

    if (!is_admin() && $query->is_main_query()) {
		if (is_post_type_archive($postTypeTarget) || is_tax(get_object_taxonomies('variety', 'names'))) {
            $query->set('order', 'ASC');
			$query->set('orderby', 'title');
		}
	}

    //var_dump($query);
}
add_action('pre_get_posts', 'variety_posts_order');

//variety archive and taxonomy templates
add_filter('template_include', 'variety_and_taxonomy_page_template');

function variety_and_taxonomy_page_template($template) {
	$postTypeTarget = 'variety';
	$varietyAndTaxPageTemplate = "archive-variety.php";

    if ( is_post_type_archive($postTypeTarget) || is_tax(get_object_taxonomies('variety', 'names')) ) {
        $custom_template = locate_template($varietyAndTaxPageTemplate);

        if (!empty($custom_template)) {
            return $custom_template;
        }
    }

    // Return the default template if conditions aren't met
    return $template;
}

//variety filters
function filter_variety_posts_by_taxonomies($query) {
	if (!is_admin() && $query->is_main_query() && is_tax('variety-category')) {
		$vars = ['attracts', 'flower-color', 'initial-letter', 'light-requirements', 'mature-size', 'resists'];
		$tax_query = (array) $query->get('tax_query');

		foreach ($vars as $var) {
			$query_value = isset($_GET[$var])?$_GET[$var]:false;
			if ($query_value) {
				$tax_query['relation'] = 'AND';
				$tax_query[] = [
					'taxonomy' => $var,
					'field' => 'slug',
					'terms' => explode(',', $query_value),
				];
			}
		}
		$query->set('tax_query', $tax_query);
	}
}
add_action('pre_get_posts', 'filter_variety_posts_by_taxonomies');

//printable views
function custom_variety_rewrite_rule() {
    add_rewrite_rule('^print-out/([^/]*)/?', 'index.php?variety_category_print=$matches[1]', 'top');
}
add_action('init', 'custom_variety_rewrite_rule');

function custom_query_vars_filter($vars) {
    $vars[] .= 'variety_category_print';
    return $vars;
}
add_filter('query_vars', 'custom_query_vars_filter');

function custom_template_redirect() {
    global $wp_query;
    // Check if our custom query var is set
    if (isset($wp_query->query_vars['variety_category_print'])) {
        $category_slug = $wp_query->query_vars['variety_category_print'];

        $posts = new WP_Query(array(
            'post_type' => 'variety',
			'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'variety-category',
                    'field'    => 'slug',
                    'terms'    => $category_slug,
                ),
            ),
        ));

        // Assume 'template-print.php' is your print-friendly template
        // Make sure to create this template file in your theme
        include(get_template_directory() . '/print-out.php');
        exit; // Stop further execution
    }
}
add_action('template_redirect', 'custom_template_redirect');

//search only varieties
add_filter('pre_get_posts', 'bka_filter_search_cpt');
function bka_filter_search_cpt($query)
{
    if( $query->is_search ) $query->set('post_type', array('variety'));

    return $query;
}