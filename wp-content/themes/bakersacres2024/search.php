<?php get_header() ?>
<div id="catalog-header-wrapper">
    <div id="catalog-header">
        <h1>Search Results for "<?=$_GET['s']?>"</h1>
        <div class="background" style="background-image: url(<?=get_field('search_page_header_image', 'option')['url']?>)"></div>
    </div>
</div>
<div id="variety-list-wrapper" class="search">
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
                    $stock_flags = array(
                        -1 => 'Out of Stock',
                        0 => 'Planned',
                        1 => 'In Production',
                        2 => 'In Store'
                    );

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
                    <div class="stock-price"><?=$stock_flags[$flag]?><?=$price_string?></div>
                </div>
                
            </div> <?php
        } ?>
        <div class="pagination"><?php
            echo paginate_links(['end_size' => 3, 'mid_size' => 1, 'type' => 'plain']); ?>
        </div>
    </div> 

</div>

<?php get_footer() ?>