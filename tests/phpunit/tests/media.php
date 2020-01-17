<?php

/**
 * @group media
 * @group shortcode
 */
class Tests_Media extends WP_UnitTestCase {
	protected static $large_id;

	public static function wpSetUpBeforeClass( $factory ) {
		$filename       = DIR_TESTDATA . '/images/test-image-large.png';
		self::$large_id = $factory->attachment->create_upload_object( $filename );
	}

	public static function wpTearDownAfterClass() {
		wp_delete_post( self::$large_id, true );
		self::$large_id = null;
	}

	/**
	 * @ticket 44427
	 */
	function test_wp_lazy_load_content_media() {
		$img       = get_image_tag( self::$large_id, '', '', '', 'medium' );
		$img_xhtml = str_replace( ' />', '/>', $img );
		$img_html5 = str_replace( ' />', '>', $img );
		$iframe    = '<iframe src="https://www.example.com"></iframe>';

		$lazy_img       = str_replace( '<img ', '<img loading="lazy" ', $img );
		$lazy_img_xhtml = str_replace( '<img ', '<img loading="lazy" ', $img_xhtml );
		$lazy_img_html5 = str_replace( '<img ', '<img loading="lazy" ', $img_html5 );

		// The following should not be modified because there already is a 'loading' attribute.
		$img_eager = str_replace( ' />', ' loading="eager" />', $img );

		// The following should not be modified either, because 'skip-lazy' is present.
		$img_skiplazy = str_replace( 'class="', 'class="skip-lazy ', $img );

		$content = '
			<p>Image, standard.</p>
			%1$s

			<p>Image, XHTML 1.0 style (no space before the closing slash).</p>
			%2$s

			<p>Image, HTML 5.0 style.</p>
			%3$s

			<p>Image, with pre-existing "loading" attribute.</p>
			%5$s

			<p>Image, with "skip-lazy" set, not to be modified.</p>
			%6$s

			<p>Iframe, standard. Should not be modified by default.</p>
			%4$s';

		$content_unfiltered = sprintf( $content, $img, $img_xhtml, $img_html5, $iframe, $img_eager, $img_skiplazy );
		$content_filtered   = sprintf( $content, $lazy_img, $lazy_img_xhtml, $lazy_img_html5, $iframe, $img_eager, $img_skiplazy );

		$this->assertSame( $content_filtered, wp_add_lazy_load_attributes( $content_unfiltered ) );
	}

	/**
	 * @ticket 44427
	 */
	function test_wp_lazy_load_content_media_opted_in() {
		$img    = get_image_tag( self::$large_id, '', '', '', 'medium' );
		$iframe = '<iframe src="https://www.example.com"></iframe>';

		$lazy_img    = str_replace( '<img ', '<img loading="lazy" ', $img );
		$lazy_iframe = str_replace( '<iframe ', '<iframe loading="lazy" ', $iframe );

		$content = '
			<p>Image, standard.</p>
			%1$s

			<p>Iframe, standard.</p>
			%2$s';

		$content_unfiltered = sprintf( $content, $img, $iframe );
		$content_filtered   = sprintf( $content, $lazy_img, $lazy_iframe );

		add_filter( 'wp_get_lazy_load_tags', '__return_true' );

		$this->assertSame( $content_filtered, wp_add_lazy_load_attributes( $content_unfiltered ) );
		remove_filter( 'wp_get_lazy_load_tags', '__return_true' );
	}

	/**
	 * @ticket 44427
	 */
	function test_wp_lazy_load_content_media_opted_out() {
		$img = get_image_tag( self::$large_id, '', '', '', 'medium' );

		$content = '
			<p>Image, standard.</p>
			%1$s';
		$content = sprintf( $content, $img );

		add_filter( 'wp_get_lazy_load_tags', '__return_false' );

		$this->assertSame( $content, wp_add_lazy_load_attributes( $content ) );
		remove_filter( 'wp_get_lazy_load_tags', '__return_false' );
	}
}
