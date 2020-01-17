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
 * Version:     0.1
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
		// Before parsing blocks and shortcodes.
		// TODO: Comments do not support images. Revisit.
		// TODO: This should not exclude images from dynamic blocks and shortcodes. Look at fixing the filter priority.
		add_filter( $filter, 'wp_add_lazy_load_attributes', 8 );
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
	if ( in_array( 'img', wp_get_lazy_load_tags(), true ) && false === strpos( $avatar, ' loading=' ) ) {
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
	if ( in_array( 'img', wp_get_lazy_load_tags(), true ) && ! isset( $attr['loading'] ) ) {
		$attr['loading'] = 'lazy';
	}

	return $attr;
}


// The following functions will be merged to core.

/**
 * Get the HTML tags to lazy-load.
 *
 * @since (TBD)
 *
 * @return array List of tags to add loading="lazy" attributes to.
 */
function wp_get_lazy_load_tags() {
	// See https://github.com/whatwg/html/issues/2806.
	$supported_tags = array( 'img', 'iframe' );

	/**
	 * Filters on which HTML tags to add `loading="lazy"`.
	 *
	 * @since (TBD)
	 *
	 * @param array $tags List of tags to add loading="lazy" attributes to. Default is an array with 'img'.
	 *                    Supports passing a boolean: `false` removes all tags, `true` resets the tags to the default value.
	 *                    Example: `add_filter( 'wp_get_lazy_load_tags', '__return_true' );` will enable it for images and iframes.
	 *                    Note that the HTML specs are not finalized yet. See https://github.com/whatwg/html/pull/3752/files,
	 *                    and support for iframes has been postponed (for now).
	 */
	$tags = apply_filters( 'wp_get_lazy_load_tags', array( 'img' ) );

	// Support a boolean, false to disable, true to reset to default.
	if ( ! is_array( $tags ) ) {
		return $tags === true ? $supported_tags : array();
	}

	// Only include supported tags.
	return array_values( array_intersect( $tags, $supported_tags ) );
}

// TODO: update docs.
/**
 * Add `loading="lazy"` to `img` and/or `iframe` HTML tags.
 *
 * Currently the "loading" attribute is only supported for `img`, and is enabled by default.
 * Support for `iframe` is for testing purposes only.
 *
 * @since (TBD)
 *
 * @param string $content The raw post content to be filtered.
 * @return string Converted content with 'loading' attributes added to images.
 */
function wp_add_lazy_load_attributes( $content ) {
	$tags = wp_get_lazy_load_tags();

	if ( empty( $tags ) ) {
		return $content;
	}

	return preg_replace_callback(
		'/<(' . implode( '|', $tags ) . ')(\s)[^>]+>/',
		function( array $matches ) {
			if ( ! preg_match( '/\sloading\s*=/', $matches[0] ) ) {
				$tag   = $matches[1];
				$space = $matches[2];

				return str_replace( '<' . $tag . $space, '<' . $tag . $space . 'loading="lazy" ', $matches[0] );
			}

			return $matches[0];
		},
		$content
	);
}

