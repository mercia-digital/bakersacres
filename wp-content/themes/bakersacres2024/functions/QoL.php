<?php
if (is_admin()) { 
    function md_disable_editor_fullscreen_by_default() {
    $script = "jQuery( window ).load(function() { const isFullscreenMode = wp.data.select( 'core/edit-post' ).isFeatureActive( 'fullscreenMode' ); if ( isFullscreenMode ) { wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'fullscreenMode' ); } });";
    wp_add_inline_script( 'wp-blocks', $script );
}
add_action( 'enqueue_block_editor_assets', 'md_disable_editor_fullscreen_by_default' );
}

function md_add_block_group_inner( $block_content ) {
	libxml_use_internal_errors( true );
	$dom = new DOMDocument();
	$dom->loadHTML(
		mb_convert_encoding(
			'<html>' . $block_content . '</html>',
			'HTML-ENTITIES',
			'UTF-8'
		),
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
	
	foreach ( $dom->getElementsByTagName( 'div' ) as $div ) {
		// check for desired class name
			//skip if
				// NOT wp-block-group
				// OR IS wp-block-group__inner-container
				// OR IS wp-block-group AND IS is-layout-flex
				// OR IS wp-block-group AND IS is-layout-grid
		if (
			strpos( $div->getAttribute( 'class' ), 'wp-block-group' ) === false
			|| strpos( $div->getAttribute( 'class' ), 'wp-block-group__inner-container' ) !== false
			|| 	(	strpos( $div->getAttribute( 'class' ), 'wp-block-group' ) !== false 
				&&	strpos( $div->getAttribute( 'class' ), 'is-layout-flex' ) !== false)
			|| 	(	strpos( $div->getAttribute( 'class' ), 'wp-block-group' ) !== false 
				&&	strpos( $div->getAttribute( 'class' ), 'is-layout-grid' ) !== false)
		) {
			continue;
		}
		
		// check if we already processed this div
		foreach ( $div->childNodes as $childNode ) {
			if (
				method_exists( $childNode, 'getAttribute' )
				&& strpos( $childNode->getAttribute( 'class' ), 'wp-block-group__inner-container' ) !== false
			) {
				continue 2;
			}
		}
		
		// create the inner container element
		$inner_container = $dom->createElement( 'div' );
		$inner_container->setAttribute( 'class', 'wp-block-group__inner-container' );
		// get all children of the current group
		$children = iterator_to_array( $div->childNodes );
		
		// append all children to the inner container
		foreach ( $children as $child ) {
			$inner_container->appendChild( $child );
		}
		
		// append new inner container to the group block
		$div->appendChild( $inner_container );
	}
	
	return str_replace( [ '<html>', '</html>' ], '', $dom->saveHTML( $dom->documentElement ) );
}

add_filter( 'render_block_core/group', 'md_add_block_group_inner' );