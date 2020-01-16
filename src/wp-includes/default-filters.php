<?php
/**
 * Default filters and actions.
 *
 * @package WordPress
 */

/*
The following filters would be merged into core.
 */

foreach ( array( 'the_content', 'the_excerpt', 'comment_text', 'widget_text_content' ) as $filter ) {
	// Before parsing blocks and shortcodes.
	add_filter( $filter, 'wp_lazy_load_content_media', 8 );
}

/*
The following filters only exist because of this being a feature plugin.
 */

foreach ( array( 'the_content', 'the_excerpt', 'the_post_thumbnail_caption', 'comment_text', 'widget_text_content' ) as $filter ) {
	// After converting smilies.
	add_filter( $filter, 'wp_add_loading_lazy_to_translated_smilies', 21 );
}

add_filter( 'wp_get_attachment_image_attributes', 'wp_add_loading_lazy_to_attachment_image' );
add_filter( 'get_avatar', 'wp_add_loading_lazy_to_avatar' );

unset( $filter );
