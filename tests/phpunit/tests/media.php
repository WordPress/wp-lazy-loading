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

		$content = '
			<p>Image, standard.</p>
			%1$s

			<p>Image, XHTML 1.0 style (no space before the closing slash).</p>
			%2$s

			<p>Image, HTML 5.0 style.</p>
			%3$s

			<p>Image, with pre-existing "loading" attribute.</p>
			%5$s

			<p>Iframe, standard. Should not be modified.</p>
			%4$s';

		$content_unfiltered = sprintf( $content, $img, $img_xhtml, $img_html5, $iframe, $img_eager );
		$content_filtered   = sprintf( $content, $lazy_img, $lazy_img_xhtml, $lazy_img_html5, $iframe, $img_eager );

		$this->assertSame( $content_filtered, wp_filter_content_tags( $content_unfiltered ) );
	}

	/**
	 * @ticket 44427
	 */
	function test_wp_lazy_load_content_media_opted_in() {
		$img = get_image_tag( self::$large_id, '', '', '', 'medium' );

		$lazy_img = str_replace( '<img ', '<img loading="lazy" ', $img );

		$content = '
			<p>Image, standard.</p>
			%1$s';

		$content_unfiltered = sprintf( $content, $img );
		$content_filtered   = sprintf( $content, $lazy_img );

		// Enable globally for all tags.
		add_filter( 'wp_lazy_loading_enabled', '__return_true' );

		$this->assertSame( $content_filtered, wp_filter_content_tags( $content_unfiltered ) );
		remove_filter( 'wp_lazy_loading_enabled', '__return_true' );
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

		// Disable globally for all tags.
		add_filter( 'wp_lazy_loading_enabled', '__return_false' );

		$this->assertSame( $content, wp_filter_content_tags( $content ) );
		remove_filter( 'wp_lazy_loading_enabled', '__return_false' );
	}
}
