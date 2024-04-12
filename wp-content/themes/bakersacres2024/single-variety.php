<?php get_header() ?>
<div id="variety-details-wrapper">
    <div id="variety-details">
        <h1><?=get_the_title()?></h1>
        <div id="variety-data"> <?php
            if (get_field("common_name")) { ?>
                <h3><?=get_field("common_name")?></h3> <?php
            }
            if (get_field("TagDescription")) { ?>
                <div class="description"><?=get_field("TagDescription")?></div> <?php
            } ?>
            <div class="attributes"> <?php
                $taxonomies = [
                    ['Category','variety-category'],
                    ['Attracts','attracts'],
                    ['Flower Color','flower-color'],
                    ['Light Requirements','light-requirements'],
                    ['Mature Size','mature-size'],
                    ['Resists','resists'],
                ];
                foreach ($taxonomies as $tax) {
                    $terms = get_the_terms(get_the_ID(), $tax[1]);
                    if (!empty($terms) && !is_wp_error($terms)) { ?>
                        <div><?=$tax[0]?>: <?php
                            foreach ($terms as $term) {
                                $name = $term->name;
                                $name = str_replace('[ft]', '\'', $name);
                                $name = str_replace('[in]', '\"', $name); ?>
                                <span><?=$name?></span><?php
                            } ?>
                        </div> <?php
                    }
                } ?>
            </div>
            <div class="sizes">
                <h4>Sizes</h4>
                <div class="table"> <?php
                    foreach(get_field('sizes') as $size) {
                        if (!$size['SizeName']) {
                            continue;
                        } ?>
                        <div class="size">
                            <div><?=$size['SizeName']?></div>
                            <div><?php if ($size['Price'] > 0 && $size['Avail']) : echo "$".number_format($size['Price'], 2, '.', ','); endif;?></div>
                            <div><?=get_status_name($size['stock_flag'])?></div>
                        </div> <?php
                    } ?>
                </div>
            </div>
        </div>
        <div id="variety-images"> <?php
            $img = get_field('imageURL');
            if (!$img || strlen($img) < 50) {
                $img = get_field('placeholder', 'options')['url'];
            } ?>
            <img src="<?=$img?>" alt="Image of <?=get_the_title()?>"/>
            <!-- <div class="image-wrapper" style="background-image: url('<?=$img?>')"></div> -->
        </div>
    </div>
</div>
<?php get_footer() ?>