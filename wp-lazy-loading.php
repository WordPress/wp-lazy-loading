<?php
/**
 * Plugin initialization file.
 *
 * @package WordPress
 *
 * @wordpress-plugin
 * Plugin Name: WP Lazy Loading
 * Plugin URI:  https://wordpress.org/plugins/wp-lazy-loading/
 * Description: WordPress feature plugin for testing and experimenting with automatically adding the "loading" HTML attribute.
 * Version:     1.0
 * Author:      The WordPress Team
 * Author URI:  https://github.com/WordPress/wp-lazy-loading/
 * License:     GNU General Public License v2 (or later)
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

/**
 * Initialise filters and actions.
 */
function _wp_lazy_loading_initialize_filters() {
	// The following filters would be merged into core.
	foreach ( array( 'the_content', 'the_excerpt', 'comment_text', 'widget_text_content' ) as $filter ) {
		// After parsing blocks and shortcodes.
		add_filter( $filter, 'wp_add_lazy_load_attributes', 25 );
	}

	// The following filters are only needed while this is a feature plugin.
	add_filter( 'wp_get_attachment_image_attributes', '_wp_lazy_loading_add_attribute_to_attachment_image' );
	add_filter( 'get_avatar', '_wp_lazy_loading_add_attribute_to_avatar' );
}

add_action( 'plugins_loaded', '_wp_lazy_loading_initialize_filters', 1 );


// The following functions are only needed while this is a plugin.

/**
 * Adds loading attribute to an avatar image tag.
 *
 * If merged to core, this should instead happen directly in
 * {@see get_avatar()}, as a default attribute.
 *
 * @since 1.0
 * @access private
 * @see get_avatar()
 *
 * @param string $avatar Avatar image tag markup.
 * @return string Modified tag.
 */
function _wp_lazy_loading_add_attribute_to_avatar( $avatar ) {
	if ( wp_lazy_loading_enabled( 'img', 'get_avatar' ) && false === strpos( $avatar, ' loading=' ) ) {
		$avatar = str_replace( '<img ', '<img loading="lazy" ', $avatar );
	}

	return $avatar;
}

/**
 * Adds loading attribute to an attachment image tag.
 *
 * If merged to core, this should instead happen directly in
 * {@see wp_get_attachment_image()}, as a default attribute.
 *
 * @since 1.0
 * @access private
 * @see wp_get_attachment_image()
 *
 * @param array $attr Associative array of attributes for the image tag.
 * @return array Modified attributes.
 */
function _wp_lazy_loading_add_attribute_to_attachment_image( $attr ) {
	if ( wp_lazy_loading_enabled( 'img', 'wp_get_attachment_image' ) && ! isset( $attr['loading'] ) ) {
		$attr['loading'] = 'lazy';
	}

	return $attr;
}


// The following functions will be merged to core.

/**
 * Determine whether to add the `loading` attribute to the specified tag in the specified context.
 *
 * @since (TBD)
 *
 * @param string  $tag_name The tag name.
 * @param string  $context Additional context, like the current filter name or the function name from where this was called.
 * @return boolean Whether to add the attribute.
 */
function wp_lazy_loading_enabled( $tag_name, $context ) {
	// By default add to all 'img' tags.
	// See https://github.com/whatwg/html/issues/2806
	$default = ( 'img' === $tag_name );

	/**
	 * Filters whether to add the `loading` attribute to the specified tag in the specified context.
	 *
	 * @since (TBD)
	 *
	 * @param boolean $default Default value.
	 * @param string  $tag_name The tag name.
	 * @param string  $context Additional context, like the current filter name or the function name from where this was called.
	 */
	return (bool) apply_filters( 'wp_lazy_loading_enabled', $default, $tag_name, $context );
}

/**
 * Add `loading="lazy"` to `img` HTML tags.
 *
 * Currently the "loading" attribute is only supported for `img`, and is enabled by default.
 *
 * @since (TBD)
 *
 * @param string $content The HTML content to be filtered.
 * @param string $context Optional. Additional context to pass to the filters. Defaults to `current_filter()` when not set.
 * @return string Converted content with 'loading' attributes added to images.
 */
function wp_add_lazy_load_attributes( $content, $context = null ) {
	if ( null === $context ) {
		$context = current_filter();
	}

	if ( ! wp_lazy_loading_enabled( 'img', $context ) ) {
		return $content;
	}

	return preg_replace_callback(
		'/<img\s[^>]+>/',
		function( array $matches ) use( $content, $context ) {
			if ( ! preg_match( '/\sloading\s*=/', $matches[0] ) ) {
				$tag_html = $matches[0];

				/**
				 * Filters the `loading` attribute value. Default `lazy`.
				 *
				 * Returning `false` or an empty string will not add the attribute.
				 * Returning `true` will add the default value.
				 *
				 * @since (TBD)
				 *
				 * @param string $default The filtered value, defaults to `lazy`.
				 * @param string $tag_html The tag's HTML.
				 * @param string $content The HTML containing the image tag.
				 * @param string $context Optional. Additional context. Defaults to `current_filter()`.
				 */
				$value = apply_filters( 'wp_set_image_loading_attr', 'lazy', $tag_html, $content, $context );

				if ( $value ) {
					if ( ! in_array( $value, array( 'lazy', 'eager' ), true ) ) {
						$value = 'lazy';
					}

					return str_replace( '<img', '<img loading="' . $value . '"', $tag_html );
				}
			}

			return $matches[0];
		},
		$content
	);
}
