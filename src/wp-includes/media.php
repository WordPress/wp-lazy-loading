<?php
/**
 * WordPress API for media display.
 *
 * @package WordPress
 */

if ( ! function_exists( 'wp_get_lazy_load_tags' ) ) {

	/**
	 * Gets the tags to lazy-load.
	 *
	 * @since 1.0
	 *
	 * @return array List of tags to add loading="lazy" attributes to.
	 */
	function wp_get_lazy_load_tags() {
		$supported_tags = array( 'img', 'iframe' );

		/**
		 * Filters which media should be lazy-loaded with loading="lazy" attributes.
		 *
		 * @since 1.0
		 *
		 * @param array $tags List of tags to add loading="lazy" attributes to. Default is an array with 'img'.
		 */
		$tags = apply_filters( 'wp_lazy_load_content_media', array( 'img' ) );

		// Support a boolean, just in case.
		if ( ! is_array( $tags ) ) {
			return $tags ? $supported_tags : array();
		}

		// Only allow supported tags to be included.
		return array_values( array_intersect( $tags, $supported_tags ) );
	}
}

if ( ! function_exists( 'wp_lazy_load_content_media' ) ) {

	/**
	 * Filters 'img' and 'iframe' elements in post content to add 'loading' attributes.
	 *
	 * @since 1.0
	 *
	 * @param string $content The raw post content to be filtered.
	 * @return string Converted content with 'loading' attributes added to images.
	 */
	function wp_lazy_load_content_media( $content ) {
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
function wp_add_loading_lazy_to_attachment_image( $attr ) {
	if ( in_array( 'img', wp_get_lazy_load_tags(), true ) ) {
		$attr['loading'] = 'lazy';
	}

	return $attr;
}
