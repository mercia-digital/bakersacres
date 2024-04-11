				</main><!-- #main -->
			</div><!-- #primary -->
		</div><!-- #content -->

		<footer id="colophon" class="site-footer">
			<div class="grid-container">
				<!-- <div class="row">
					<div class="footer-logo">
						<?php $_logo = get_field('footer_logo', 'option');?>
						<img src="<?=esc_url($_logo['url']) ?>" alt="<?=esc_attr($_logo['alt']) ?>" class="footer-logo" />
					</div>
				</div> --> <?php
				if ( is_active_sidebar( 'footer-widget-area' ) ) { 
					dynamic_sidebar( 'footer-widget-area' );
				} ?>
				<div class="row copyright">
					<div><?=get_field('copyright', 'option')?></div>
					<div class="mercia">Website by <a href="https://mercia.digital" target="_blank">Mercia Digital LLC</a></div>
				</div>
			</div>
		</footer><!-- #colophon -->

	</div><!-- #page -->

	<?php wp_footer(); ?>

	</body>
</html>
