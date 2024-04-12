<?php get_header() ?>
<?php
//get variety-category
$variety_category = '';
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$parts = explode('/', rtrim($url, '/')); // Trim trailing slash and split URL
$index = array_search('product-category', $parts); // Find 'product-category' index

if ($index !== false && isset($parts[$index + 1])) {
    $variety_category = $parts[$index + 1]; // Return the segment after 'product-category'
}

$q_obj = get_term_by('slug', $variety_category, 'variety-category');

//get filter taxonomies
$has_filters = false;

if (isset($_GET['initial-letter'])) {
    $has_filters = true;
}

$taxonomies = [
    //['Category','variety-category'],
    ['Attracts','attracts'],
    ['Flower Color','flower-color'],
    ['Light Requirements','light-requirements'],
    //['Mature Size','mature-size'],
    ['Resists','resists'],
];

//build query with var-cat and filters
$args = [
    'post_type' => 'variety',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'tax_query' => [
        'relation' => 'AND',
        [
            'taxonomy' => 'variety-category',
            'field' => 'slug',
            'terms' => [$variety_category],
        ]
    ],
];
foreach($taxonomies as $tax) {
    $query_value = isset($_GET[$tax[1]])?$_GET[$tax[1]]:false;
    if ($query_value) {
        $has_filters = true;
        $args['tax_query'][] = [
            'taxonomy' => $tax[1],
            'field' => 'slug',
            'terms' => explode(',', $query_value),
        ];
    }
    
}
$full_query = new WP_Query($args); ?>

<div id="catalog-header-wrapper">
    <div id="catalog-header">
        <h1><?=$q_obj->name?></h1>
        <div class="background" style="background-image: url(<?=get_field('category_image', $q_obj)?>)"></div>
        <!-- <h4><?=get_field('catalog_header', 'option')?></h4> -->
    </div>
</div>
<div id="variety-list-wrapper">
    <div id="title-groups">
        <button class="title-group" data-letters="a,b,c,d,e,f">A-F</button>
        <button class="title-group" data-letters="g,h,i,j,k,l">G-L</button>
        <button class="title-group" data-letters="m,n,o,p,q,r">M-R</button>
        <button class="title-group" data-letters="s,t,u,v,w,x,y,z">S-Z</button>
    </div>
    <div id="variety-list-sidebar">
        <button class="clear-filters <?=$has_filters ? 'active' : ''?>" name="clear-filters">Clear Filters</button>
        <div class="category-list">
            <div class="mobile-filters">
            <div class="heading rough-line rough-underline-lime">
                Filters
                <div class="indicator-open">+</div>
                <div class="indicator-close">-</div>
            </div>
            </div>
            <div class="filters"> <?php
                foreach($taxonomies as $tax) {
                    $termsTitle = $tax[0];
                    $taxonomy_name = $tax[1];

                    $queryValue = isset($_GET[$taxonomy_name])?$_GET[$taxonomy_name]:'';

                    // Get terms associated with those post IDs for the current taxonomy
                    $terms = wp_get_object_terms($full_query->posts, $taxonomy_name, array('fields' => 'all'));
                    
                    // Check if any terms are found
                    if (!empty($terms) && !is_wp_error($terms)) { ?>
                        <div class="heading rough-line rough-underline-purple">
                            <?=$termsTitle?>
                            <div class="indicator-open">+</div>
                            <div class="indicator-close">-</div>
                        </div>
                        <ul><?php
                        foreach ($terms as $term) {
                            $checked = '';
                            if (in_array($term->slug, explode(',', $queryValue))) {
                                $checked = ' checked ';
                            } ?>
                            <li>
                                <input  type="checkbox"
                                        class="variety-tax-cb"
                                        <?=$checked?> 
                                        data-term="<?=$term->slug?>" 
                                        data-taxonomy="<?=$taxonomy_name?>"
                                        name="<?=$taxonomy_name."__".$term->slug?>"
                                        id="<?=$taxonomy_name."__".$term->slug?>" />
                                <label for="<?=$taxonomy_name."__".$term->slug?>"><?=esc_html( $term->name )?></span></label>
                            </li> <?php
                        }
                        echo '</ul>';
                    }
                } ?>
            </div>
        </div>
        <div class="print">
            <div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--70)">
                <div class="wp-block-button">
                    <a class="wp-block-button__link has-green-bakers-background-color has-background wp-element-button" href="/print-out/<?=$variety_category?>" target="_blank" style="border-radius:0px">Print this Category</a>
                </div>
            </div>
        </div>
        <div class="legend">
            <div class="content">
                <?=apply_filters('the_content', get_field('status_names_legend', 'option'))?>
            </div>
        </div>
    </div>
    <div id="variety-list"> <?php
        while (have_posts()) { the_post(); ?>
            <div class="variety-card rough rough-catalog-item"> <?php
                $img = get_field('imageURL');
                if (!$img || strlen($img) < 50) {
                    $img = get_field('placeholder', 'options')['url'];
                } ?>
                <div class="image-wrapper" style="background-image: url('<?=$img?>')">
                    <a href="<?=get_the_permalink()?>"></a>
                </div>
                <div class="info">
                    <div class="title"><a href="<?=get_the_permalink()?>"><?=get_the_title()?></a></div>
                    <!-- Common Name --> <?php
                    $common = get_field('common_name');
                    if ($common) { ?>
                        <div class="common-name"><?=$common?></div> <?php
                    } ?>
                    <!-- Stock Status --> <?php
                    $stock_flag = "";
                    $sizes = get_field('sizes');
                    $prices = [];

                    usort($sizes, function($a, $b) {
                        return $a['Price'] <=> $b['Price'];
                    });

                    $flag = -2;
                    foreach ($sizes as $size) {
                        $flag = max($flag, $size['stock_flag']);
                        $prices[] = $size['Price'];
                    }

                    $price_string = '';
                    if (count($prices) && $flag == 2) {
                        $price_string = " $".number_format($prices[0], 2, '.', ',');
                        if (count($prices) > 1) {
                            $price_string .= " - $" . number_format($prices[count($prices) - 1], 2, '.', ',');
                        }
                    } ?>
                    <div class="stock-price"><?=get_status_name($flag)?><?=$price_string?></div>
                </div>
                
            </div> <?php
        } ?>
        <div class="pagination"><?php
            echo paginate_links(['end_size' => 3, 'mid_size' => 1, 'type' => 'plain']); ?>
        </div>
    </div> 

</div>

<?php get_footer() ?>