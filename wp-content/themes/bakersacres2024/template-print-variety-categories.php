<?php
//Template Name: Print Variety Category List ?>
<?php get_header() ?>
<div id="print-outs-header"><h1>Print Outs</h1></div>
<div id="variety-category-archive" class="print-outs"><?php
    $taxonomies = [
        ['Category','variety-category'],
        //['Attracts','attracts'],
        //['Flower Color','flower-color'],
        //['Light Requirements','light-requirements'],
        //['Mature Size','mature-size'],
        //['Resists','resists'],
    ];
    foreach($taxonomies as $tax) {
        $termsTitle = $tax[0];

        $terms = get_terms(array(
            'taxonomy'   => $tax[1],
            'hide_empty' => true, // Only retrieve terms that have posts
            'order' => 'asc'
        ));
        
        // Check if any terms are found
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) { 
                if(get_field('hide_on_front', $term)) { continue; }?>
                <div class="variety-category" style="background-image: url(<?=get_field('category_image', $term)?>)">
        	        <a target="_blank" href="/print-out/<?=$term->slug?>"></a>
                    <h2><?=$term->name?></h2>                    
                </div> <?php
            }
        }
    } ?>    
</div>
<?php get_footer() ?>