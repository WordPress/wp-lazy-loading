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

			<p>Iframe, standard. Should not be modified by default.</p>
			%4$s';

		$content_unfiltered = sprintf( $content, $img, $img_xhtml, $img_html5, $iframe, $img_eager );
		$content_filtered   = sprintf( $content, $lazy_img, $lazy_img_xhtml, $lazy_img_html5, $iframe, $img_eager );

		$this->assertSame( $content_filtered, _wp_filter_html_tags( $content_unfiltered ) );
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

		add_filter( 'wp_add_lazy_loading_to', '__return_true' );
		add_filter( 'wp_get_tags_to_filter', array( $this, 'wp_get_tags_to_filter_callback' ) );
		add_filter( 'wp_filter_iframe_tags', array( $this, 'wp_filter_iframe_tags_callback' ) );

		$this->assertSame( $content_filtered, _wp_filter_html_tags( $content_unfiltered ) );
		remove_filter( 'wp_add_lazy_loading_to', '__return_true' );
		remove_filter( 'wp_get_tags_to_filter', array( $this, 'wp_get_tags_to_filter_callback' ) );
		remove_filter( 'wp_filter_iframe_tags', array( $this, 'wp_filter_iframe_tags_callback' ) );
	}

	function wp_get_tags_to_filter_callback() {
		return array( 'img', 'iframe' );
	}

	function wp_filter_iframe_tags_callback( $tag_html ) {
		return str_replace( '<iframe', '<iframe loading="lazy"', $tag_html );
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

		add_filter( 'wp_add_lazy_loading_to', '__return_false' );

		$this->assertSame( $content, _wp_filter_html_tags( $content ) );
		remove_filter( 'wp_add_lazy_loading_to', '__return_false' );
	}
}
