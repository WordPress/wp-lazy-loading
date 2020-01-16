<?php
/**
 * Main WordPress Formatting API.
 *
 * @package WordPress
 */

/**
 * Adds loading attribute to smiley image tags.
 *
 * If merged to core, this should instead happen directly in
 * {@see translate_smiley()}, without any replacements or filters.
 *
 * @since 1.0
 * @access private
 * @see translate_smiley()
 *
 * @param string $content Content with smiley image tags.
 * @return string Modified content.
 */
function wp_add_loading_lazy_to_translated_smilies( $content ) {
	if ( in_array( 'img', wp_get_lazy_load_tags(), true ) ) {
		$content = str_replace(
			' class="wp-smiley" style="height: 1em; max-height: 1em;"',
			' class="wp-smiley" style="height: 1em; max-height: 1em;" loading="lazy"',
			$content
		);
	}

	return $content;
}
