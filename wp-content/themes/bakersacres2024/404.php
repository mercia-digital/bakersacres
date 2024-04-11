<?php get_header() ?>
<?=get_the_content(null, null,url_to_postid(get_field('404_page', 'option'))) ?>
<?php get_footer() ?>