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
		// After parsing blocks and shortcodes.
		// TODO: Comments and excerpts do not support images. Revisit?
		// TODO: This includes images added from shortcodes. Needs more testing.
		add_filter( $filter, '_wp_filter_html_tags', 25 );
	}

	add_filter( 'wp_filter_img_tags', 'wp_add_lazy_loading_to_img_tags', 10, 3 );

	// The following filters are only needed while this is a feature plugin.
	add_filter( 'wp_get_attachment_image_attributes', '_wp_lazy_loading_add_attribute_to_attachment_image' );
	add_filter( 'get_avatar', '_wp_lazy_loading_add_attribute_to_avatar' );

	// Exprerimental, testing only.
	// To test with iframes: add `?wp-lazy-loading-iframes` to the current URL.
	if ( isset( $_GET['wp-lazy-loading-iframes'] ) ) {
		// Add to all tags.
		add_filter( 'wp_add_lazy_loading_to', '__return_true' );

		// Add filterig for the iframe tag.
		add_filter( 'wp_get_tags_to_filter', 'wp_lazy_loading_add_iframe_tag', 10, 2 );

		// Add the actual attribute, and filter the tag after that.
		add_filter( 'wp_filter_iframe_tags', 'wp_add_lazy_loading_to_iframe_tags', 10, 3 );
	}
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
	if ( wp_add_lazy_loading_to( 'img', 'get_avatar' ) && false === strpos( $avatar, ' loading=' ) ) {
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
	if ( wp_add_lazy_loading_to( 'img', 'wp_get_attachment_image' ) && ! isset( $attr['loading'] ) ) {
		$attr['loading'] = 'lazy';
	}

	return $attr;
}


// Helper functions to enable adding of `loading="lazy"` to iframes (not for merging for now).

function wp_add_lazy_loading_to_iframe_tags( $tag_html, $content, $context = null ) {
	if ( wp_add_lazy_loading_to( 'iframe', $context ) && ! preg_match( '/\bloading\s*=/', $tag_html ) ) {
		$unfiltered = $tag_html;
		$tag_html   = str_replace( '<iframe', '<iframe loading="lazy"', $tag_html );

		// See the docs for the similar 'wp_add_lazy_loading_to_img_tags'.
		$tag_html = apply_filters( 'wp_add_lazy_loading_to_iframe_tags', $tag_html, $unfiltered, $content, $context );
	}

	return $tag_html;
}

function wp_lazy_loading_add_iframe_tag( $default_tags, $context ) {
	if ( 'the_content' === $context ) {
		$default_tags[] = 'iframe';
	}

	return $default_tags;
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
function wp_add_lazy_loading_to( $tag_name, $context ) {
	// By default add to all 'img' tags.
	// See https://github.com/whatwg/html/issues/2806
	$add = ( 'img' === $tag_name );

	/**
	 * Filters whether to add the `loading` attribute to the specified tag in the specified context.
	 *
	 * @since (TBD)
	 *
	 * @param boolean $add Defatls value.
	 * @param string  $tag_name The tag name.
	 * @param string  $context Additional context, like the current filter name or the function name from where this was called.
	 */
	return (bool) apply_filters( 'wp_add_lazy_loading_to', $add, $tag_name, $context );
}

/**
 * Get the HTML tags to lazy-load.
 *
 * @since (TBD)
 *
 * @param string $context Context for use when filtering the list of tag names. For example `current_filter()`.
 * @return array List of tags to filter on display.
 */
function wp_get_tags_to_filter( $context ) {
	// For adding the `loading` attribute. See https://github.com/whatwg/html/issues/2806.
	$default_tags = array( 'img' );

	/**
	 * Add or remove HTML tags that will be filtered on display.
	 *
	 * @since (TBD)
	 *
	 * @param array $tags List of tags to filter. Default is an array with 'img'.
	 *                    Supports passing a boolean: `false` removes all tags, `true` resets the tags to the default value.
	 *                    Example: `add_filter( 'wp_get_tags_to_filter', '__return_true' );` will enable filtering of img tags.
	 */
	$tags = apply_filters( 'wp_get_tags_to_filter', $default_tags, $context );

	// Support a boolean, false to disable, true to reset to default.
	if ( ! is_array( $tags ) ) {
		return $tags === true ? $default_tags : array();
	}

	return array_unique( $tags );
}

// TODO: update docs.
/**
 * Add `loading="lazy"` to `<img>` tags.
 *
 * Currently the "loading" attribute is only supported for `img`, and is enabled by default.
 * Support for `iframe` can be enabled for testing purposes.
 *
 * @since (TBD)
 *
 * @param string $tag_html The tag markup.
 * @param string $content The (HTML) content where the img tag is.
 * @param string $context Optional. Additional context passed to the function.
 * @return string Converted content with 'loading' attributes added to images.
 */
function wp_add_lazy_loading_to_img_tags( $tag_html, $content, $context = null ) {
	if ( wp_add_lazy_loading_to( 'img', $context ) && ! preg_match( '/\bloading\s*=/', $tag_html ) ) {
		$unfiltered = $tag_html;
		$tag_html   = str_replace( '<img', '<img loading="lazy"', $tag_html );

		/**
		 * Filters on adding `loading="lazy"` to img tags.
		 *
		 * @since (TBD)
		 *
		 * @param string $tag_html The img tag with added attribute.
		 * @param string $unfiltered The img tag before adding the attribute.
		 * @param string $content The (HTML) content where the img tag is.
		 * @param string $context Optional. Additional context passed to the function.
		 */
		$tag_html = apply_filters( 'wp_add_lazy_loading_to_img_tags', $tag_html, $unfiltered, $content, $context );
	}

	return $tag_html;
}

/**
 * Find specific HTML tags in the passed content and filter them.
 *
 * @since (TBD)
 * @access private
 *
 * @param string $content The (HTML) content to be searched.
 * @param string $context Optional. Additional context that may be passed when calling the function directly.
 * @return string The content with filtered tags.
 */
function _wp_filter_html_tags( $content, $context = null ) {
	if ( null === $context ) {
		$context = current_filter();
	}

	$tags = wp_get_tags_to_filter( $context );

	// Check if the tag exists in $content and there are filters for the tag.
	foreach ( $tags as $index => $tag_name ) {
		if ( ! has_filter( "wp_filter_{$tag_name}_tags" ) || false === strpos( $content, "<$tag_name" ) ) {
			unset( $tags[ $index ] );
		}
	}

	if ( empty( $tags ) ) {
		return $content;
	}

	// Expects well-formed HTML 5.0.
	// Intended for `img` and `iframe`.
	return preg_replace_callback(
		'/<(' . implode( '|', $tags ) . ')(?:\s[^>]+)?>/',
		function( array $matches ) use ( $content, $context ) {
			$tag_html = $matches[0];
			$tag_name = $matches[1];

			/**
			 * Filters each of the HTML tags that were found in $content.
			 *
			 * The variable part of the filter is the name of the matched tag.
			 *
			 * @since (TBD)
			 *
			 * @param string $tag_html The matched HTML markup.
			 * @param string $content The (HTML) content being filtered.
			 * @param string $context Optional. Additional context. May be the current filter name or specific context passed to the function.
			 */
			return apply_filters( "wp_filter_{$tag_name}_tags", $tag_html, $content, $context );
		},
		$content
	);
}
