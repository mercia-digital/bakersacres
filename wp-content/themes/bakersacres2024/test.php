<?php

//Template Name: test ?>
<?php get_header() ?>
<?php 

//$start_time = microtime(true);

$BakersFMAPI = new BakersFMAPI();
//$data = $BakersFMCloudSync->RetrieveVarietyInfoByID('ECF0960D-4E3D-4270-9180-F9EA34D92DE0');
//BakersFMImport();
//$data = $BakersFMCloudSync->RetrieveVarietyInfoByLastChange('01/10/2024');

// function remove_duplicate_sizes() {
//     $args = [
//         'post_type'      => 'variety', // Adjust to your specific post type
//         'posts_per_page' => -1, // Process all posts
//         'post_status'    => 'any',
//         'fields'         => 'ids', // Retrieve only post IDs to save memory
//     ];

//     $posts = get_posts($args);

//     foreach ($posts as $post_id) {
//         // Assuming 'sizes' is your repeater field name
//         $sizes = get_field('sizes', $post_id);
//         if (empty($sizes)) {
//             continue; // Skip posts without sizes
//         }

//         $cleaned_sizes = [];
//         $seen = []; // Track seen ProductionPlanID values

//         foreach ($sizes as $size) {
//             $prodPlanID = $size['ProductionPlanID'];
//             if (!in_array($prodPlanID, $seen)) {
//                 $cleaned_sizes[] = $size; // Add size to cleaned list if not a duplicate
//                 $seen[] = $prodPlanID; // Mark this ProductionPlanID as seen
//             }
//         }

//         // Update the post's sizes with the cleaned list if changes were made
//         if (count($cleaned_sizes) !== count($sizes)) {
//             update_field('sizes', $cleaned_sizes, $post_id);
//         }
//     }

//     echo "Duplicate sizes removal process completed.";
// }

//remove_duplicate_sizes();

//BakersFMCloudImport();

// $data = $BakersFMAPI->RetrieveProductionPlan(intval(date("Y")));

// $items = json_decode($data); 

function get_variety_posts_where_avail_is_true() {
    global $wpdb;

    // The meta_key pattern for repeater fields is {repeater_field_name}_{row_index}_{sub_field_name}
    // We're looking for any row index, hence the LIKE '%\_\\_%\_avail' pattern (escaped for SQL)
    $sql = "
        SELECT DISTINCT post_id
        FROM {$wpdb->postmeta}
        WHERE meta_key LIKE 'sizes\_%\_avail'
        AND meta_value = '1'
    ";

    $post_ids = $wpdb->get_col($sql);

    // If no posts found, return an empty array to avoid errors with WP_Query
    if (empty($post_ids)) {
        return [];
    }

    // Now we can use the post IDs to create a WP_Query
    $query_args = [
        'post_type' => 'variety',
        'posts_per_page' => -1,
        'post__in' => $post_ids,
        'orderby' => 'post__in',
    ];

    $query = new WP_Query($query_args);

    return $query;
}

// Usage
$variety_posts_query = get_variety_posts_where_avail_is_true();
if ($variety_posts_query->have_posts()) {
    while ($variety_posts_query->have_posts()) {
        $variety_posts_query->the_post();
        echo "<a style='color:white' href='".get_the_permalink()."'>".get_the_title()."</>";
    }
    wp_reset_postdata();
}

?><pre style="color: white"><?php
//var_dump($items);?>
</pre><?php


?>
<?php get_footer() ?>

