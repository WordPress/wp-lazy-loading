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
		add_filter( $filter, 'wp_filter_content_attachment_images' );
	}

	// The following filters are only needed while this is a feature plugin.
	add_filter( 'wp_get_attachment_image_attributes', '_wp_lazy_loading_add_attribute_to_attachment_image' );
	add_filter( 'get_avatar', '_wp_lazy_loading_add_attribute_to_avatar' );

	// The following relevant filter from core should be removed when merged.
	remove_filter( 'the_content', 'wp_make_content_images_responsive' );
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
 * Filters 'img' elements in post content and modifies their markup.
 *
 * @since (TBD)
 *
 * @see wp_image_add_srcset_and_sizes()
 * @see wp_image_add_loading()
 *
 * @param string $content The HTML content to be filtered.
 * @param string $context Optional. Additional context to pass to the filters. Defaults to `current_filter()` when not set.
 * @return string Converted content with images modified.
 */
function wp_filter_content_attachment_images( $content, $context = null ) {
	if ( null === $context ) {
		$context = current_filter();
	}

	$add_srcset_sizes = 'the_content' === $context;
	$add_loading_attr = wp_lazy_loading_enabled( 'img', $context );

	if ( false === strpos( $content, '<img' ) || ( ! $add_srcset_sizes && ! $add_loading_attr ) ) {
		return $content;
	}

	if ( ! preg_match_all( '/<img\s[^>]+>/', $content, $matches ) ) {
		return $content;
	}

	$selected_images = array();
	$attachment_ids  = array();

	foreach ( $matches[0] as $image ) {
		if ( preg_match( '/wp-image-([0-9]+)/i', $image, $class_id ) ) {
			$attachment_id = absint( $class_id[1] );

			if ( $attachment_id ) {
				/*
				 * If exactly the same image tag is used more than once, overwrite it.
				 * All identical tags will be replaced later with 'str_replace()'.
				 */
				$selected_images[ $image ] = $attachment_id;

				// Overwrite the ID when the same image is included more than once.
				$attachment_ids[ $attachment_id ] = true;
			}
		}
	}

	if ( count( $attachment_ids ) > 1 ) {
		/*
		 * Warm the object cache with post and meta information for all found
		 * images to avoid making individual database calls.
		 */
		_prime_post_caches( array_keys( $attachment_ids ), false, true );
	}

	foreach ( $selected_images as $image => $attachment_id ) {
		$image_meta = wp_get_attachment_metadata( $attachment_id );

		$filtered_image = $image;

		// Add 'srcset' and 'sizes' attributes if applicable.
		if ( $add_srcset_sizes && false === strpos( $filtered_image, ' srcset=' ) ) {
			$filtered_image = wp_image_add_srcset_and_sizes( $filtered_image, $image_meta, $attachment_id );
		}

		// Add 'loading' attribute if applicable.
		if ( $add_loading_attr && false === strpos( $filtered_image, ' loading=' ) ) {
			$filtered_image = wp_image_add_loading_attr( $filtered_image, $image_meta, $attachment_id, $content, $context );
		}

		$content = str_replace( $image, $filtered_image, $content );
	}

	return $content;
}

/**
 * Adds a 'loading' attribute to an existing 'img' element.
 *
 * @since (TBD)
 *
 * @param string $image         An HTML 'img' element to be filtered.
 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
 * @param int    $attachment_id Image attachment ID.
 * @param string $content       The HTML content that the 'img' element is part of.
 * @param string $context       Additional context to pass to the filters.
 * @return string Converted 'img' element with 'loading' attribute added.
 */
function wp_image_add_loading_attr( $image, $image_meta, $attachment_id, $content, $context ) {
	/**
	 * Filters the `loading` attribute value. Default `lazy`.
	 *
	 * Returning `false` or an empty string will not add the attribute.
	 * Returning `true` will add the default value.
	 *
	 * @since (TBD)
	 *
	 * @param string     $value         The 'loading' attribute value, defaults to `lazy`.
	 * @param string     $image         The 'img' tag's HTML.
	 * @param array|null $image_meta    The image meta data as returned by wp_get_attachment_metadata() or null.
	 * @param int        $attachment_id Image attachment ID of the original image or 0.
	 * @param string     $content       The HTML containing the image tag.
	 * @param string     $context       Additional context, typically the current filter.
	 */
	$value = apply_filters( 'wp_image_add_loading_attr', 'lazy', $image, $image_meta, $attachment_id, $content, $context );

	if ( $value ) {
		if ( ! in_array( $value, array( 'lazy', 'eager' ), true ) ) {
			$value = 'lazy';
		}

		return str_replace( '<img', '<img loading="' . $value . '"', $image );
	}

	return $image;
}
