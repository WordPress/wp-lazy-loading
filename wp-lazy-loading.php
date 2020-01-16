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
 * Author URI:  https://wordpress.org
 * License:     GNU General Public License v2 (or later)
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

define( 'WP_LAZY_LOADING_INC_PATH', plugin_dir_path( __FILE__ ) . 'src/wp-includes' );

require_once WP_LAZY_LOADING_INC_PATH . '/default-filters.php';
require_once WP_LAZY_LOADING_INC_PATH . '/formatting.php';
require_once WP_LAZY_LOADING_INC_PATH . '/media.php';
require_once WP_LAZY_LOADING_INC_PATH . '/pluggable.php';
