<?php
/**
 * Pluggable functions that can be replaced via plugins.
 *
 * @package WordPress
 */

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
function wp_add_loading_lazy_to_avatar( $avatar ) {
	if ( in_array( 'img', wp_get_lazy_load_tags(), true ) ) {
		$avatar = str_replace(
			'<img ',
			'<img loading="lazy" ',
			$avatar
		);
	}

	return $avatar;
}
