<?php

/**
 * @group media
 * @group shortcode
 */
class Tests_Media extends WP_UnitTestCase {

	public function testNothingUseful() {
		$this->assertTrue( defined( 'ABSPATH' ) );
	}
}
