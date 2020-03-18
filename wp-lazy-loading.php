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
 * Version:     1.1
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
 * Load only when needed.
 */
if (
	! function_exists( 'wp_lazy_loading_enabled' ) &&
	! function_exists( 'wp_filter_content_tags' ) &&
	! function_exists( 'wp_img_tag_add_loading_attr' ) &&
	! function_exists( 'wp_img_tag_add_srcset_and_sizes_attr' )
) {
	include_once plugin_dir_path( __FILE__ ) . 'functions.php';
}
