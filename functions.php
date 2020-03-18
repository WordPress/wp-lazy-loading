<?php
/**
 * Part of the WP Lazy Loading feature plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

/**
 * Initialise filters and actions.
 */
function _wp_lazy_loading_initialize_filters() {
	// The following filters would be merged into core.
	foreach ( array( 'the_content', 'the_excerpt', 'widget_text_content' ) as $filter ) {
		add_filter( $filter, 'wp_filter_content_tags' );
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
	// See https://html.spec.whatwg.org/multipage/embedded-content.html#attr-img-loading
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
 * Filters specific tags in post content and modifies their markup.
 *
 * This function adds `srcset`, `sizes`, and `loading` attributes to `img` HTML tags.
 *
 * @since (TBD)
 *
 * @see wp_img_tag_add_loading_attr()
 * @see wp_img_tag_add_srcset_and_sizes_attr()
 *
 * @param string $content The HTML content to be filtered.
 * @param string $context Optional. Additional context to pass to the filters. Defaults to `current_filter()` when not set.
 * @return string Converted content with images modified.
 */
function wp_filter_content_tags( $content, $context = null ) {
	if ( null === $context ) {
		$context = current_filter();
	}

	$add_loading_attr = wp_lazy_loading_enabled( 'img', $context );

	if ( false === strpos( $content, '<img' ) ) {
		return $content;
	}

	if ( ! preg_match_all( '/<img\s[^>]+>/', $content, $matches ) ) {
		return $content;
	}

	// List of the unique `img` tags found in $content.
	$images = array();

	foreach ( $matches[0] as $image ) {
		if ( preg_match( '/wp-image-([0-9]+)/i', $image, $class_id ) ) {
			$attachment_id = absint( $class_id[1] );

			if ( $attachment_id ) {
				/*
				 * If exactly the same image tag is used more than once, overwrite it.
				 * All identical tags will be replaced later with 'str_replace()'.
				 */
				$images[ $image ] = $attachment_id;
				continue;
			}
		}

		$images[ $image ] = 0;
	}

	// Reduce the array to unique attachment IDs.
	$attachment_ids = array_unique( array_filter( array_values( $images ) ) );

	if ( count( $attachment_ids ) > 1 ) {
		/*
		 * Warm the object cache with post and meta information for all found
		 * images to avoid making individual database calls.
		 */
		_prime_post_caches( $attachment_ids, false, true );
	}

	foreach ( $images as $image => $attachment_id ) {
		$filtered_image = $image;

		// Add 'srcset' and 'sizes' attributes if applicable.
		if ( $attachment_id > 0 && false === strpos( $filtered_image, ' srcset=' ) ) {
			$filtered_image = wp_img_tag_add_srcset_and_sizes_attr( $filtered_image, $context, $attachment_id );
		}

		// Add 'loading' attribute if applicable.
		if ( $add_loading_attr && false === strpos( $filtered_image, ' loading=' ) ) {
			$filtered_image = wp_img_tag_add_loading_attr( $filtered_image, $context );
		}

		if ( $filtered_image !== $image ) {
			$content = str_replace( $image, $filtered_image, $content );
		}
	}

	return $content;
}

/**
 * Adds `loading` attribute to an existing `img` HTML tag.
 *
 * @since (TBD)
 *
 * @param string $image   The HTML `img` tag where the attribute should be added.
 * @param string $context Additional context to pass to the filters.
 * @return string Converted `img` tag with `loading` attribute added.
 */
function wp_img_tag_add_loading_attr( $image, $context ) {
	/**
	 * Filters the `loading` attribute value. Default `lazy`.
	 *
	 * Returning `false` or an empty string will not add the attribute.
	 * Returning `true` will add the default value.
	 *
	 * @since (TBD)
	 *
	 * @param string $value   The 'loading' attribute value, defaults to `lazy`.
	 * @param string $image   The HTML 'img' element to be filtered.
	 * @param string $context Additional context about how the function was called or where the img tag is.
	 */
	$value = apply_filters( 'wp_img_tag_add_loading_attr', 'lazy', $image, $context );

	if ( $value ) {
		if ( ! in_array( $value, array( 'lazy', 'eager' ), true ) ) {
			$value = 'lazy';
		}

		return str_replace( '<img', '<img loading="' . $value . '"', $image );
	}

	return $image;
}

/**
 * Adds `srcset` and `sizes` attributes to an existing `img` HTML tag.
 *
 * @since (TBD)
 *
 * @param string $image         The HTML `img` tag where the attribute should be added.
 * @param string $context       Additional context to pass to the filters.
 * @param int    $attachment_id Image attachment ID.
 * @return string Converted 'img' element with 'loading' attribute added.
 */
function wp_img_tag_add_srcset_and_sizes_attr( $image, $context, $attachment_id ) {
	/**
	 * Filters whether to add the `srcset` and `sizes` HTML attributes to the img tag. Default `true`.
	 *
	 * Returning anything else than `true` will not add the attributes.
	 *
	 * @since (TBD)
	 *
	 * @param bool   $value         The filtered value, defaults to `true`.
	 * @param string $image         The HTML `img` tag where the attribute should be added.
	 * @param string $context       Additional context about how the function was called or where the img tag is.
	 * @param int    $attachment_id The image attachment ID.
	 */
	$add = apply_filters( 'wp_img_tag_add_srcset_and_sizes_attr', true, $image, $context, $attachment_id );

	if ( true === $add ) {
		$image_meta = wp_get_attachment_metadata( $attachment_id );
		return wp_image_add_srcset_and_sizes( $image, $image_meta, $attachment_id );
	}

	return $image;
}
