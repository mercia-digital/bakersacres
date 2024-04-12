<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<title><?=wp_title()?></title>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<div id="masthead">
		<div class="wrapper">
			<div class="main-nav row animate-all">
				<div class="main-nav-section main-nav-left animate-all"><?php
					wp_nav_menu(array(
						'theme_location' => 'main-nav-left',
					));?>
				</div>
				<div class="site-logo">
					<?php $_logo = get_field('header_logo', 'option');?>
					<a href="/"><img src="<?=esc_url($_logo['url']) ?>" alt="<?=esc_attr($_logo['alt']) ?>" class="site-logo" /></a>
				</div>				
				<div class="main-nav-section main-nav-right animate-all"><?php
					wp_nav_menu(array(
						'theme_location' => 'main-nav-right',
					));?>
					<i class="search-toggle fa-solid fa-magnifying-glass"></i>
				</div>
				<button class="hamburger hamburger--squeeze" type="button">
					<span class="hamburger-box">
						<span class="hamburger-inner"></span>
					</span>
				</button>
				<div class="mobile-nav">
					<div class="mobile-main-nav"><?php
						wp_nav_menu(array(
							'theme_location' => 'main-nav-left',
						));
						wp_nav_menu(array(
							'theme_location' => 'main-nav-right',
						));?>
						<div class="search-form-wrapper-mobile">
							<?=get_search_form()?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="search-form-wrapper">
			<?=get_search_form()?>
		</div>
	</div>

	<div id="content" class="site-content">
		<div id="primary" class="content-area">
			<main id="main" class="site-main">
