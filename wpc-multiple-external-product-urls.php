<?php
/*
Plugin Name: WPC Multiple External Product URLs for WooCommerce
Plugin URI: https://wpclever.net/
Description: Allows you to create multiple external / affiliate product URLs for any product and variation.
Version: 1.0.1
Author: WPClever
Author URI: https://wpclever.net
Text Domain: wpc-multiple-external-product-urls
Domain Path: /languages/
Requires Plugins: woocommerce
Requires at least: 4.0
Tested up to: 6.7
WC requires at least: 3.0
WC tested up to: 9.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

defined( 'ABSPATH' ) || exit;

! defined( 'WPCME_VERSION' ) && define( 'WPCME_VERSION', '1.0.1' );
! defined( 'WPCME_LITE' ) && define( 'WPCME_LITE', __FILE__ );
! defined( 'WPCME_FILE' ) && define( 'WPCME_FILE', __FILE__ );
! defined( 'WPCME_DIR' ) && define( 'WPCME_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'WPCME_URI' ) && define( 'WPCME_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WPCME_SUPPORT' ) && define( 'WPCME_SUPPORT', 'https://wpclever.net/support?utm_source=support&utm_medium=wpcme&utm_campaign=wporg' );
! defined( 'WPCME_REVIEWS' ) && define( 'WPCME_REVIEWS', 'https://wordpress.org/support/plugin/wpc-multiple-external-product-urls/reviews/?filter=5' );
! defined( 'WPCME_CHANGELOG' ) && define( 'WPCME_CHANGELOG', 'https://wordpress.org/plugins/wpc-multiple-external-product-urls/#developers' );
! defined( 'WPCME_DISCUSSION' ) && define( 'WPCME_DISCUSSION', 'https://wordpress.org/support/plugin/wpc-multiple-external-product-urls' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WPCME_URI );

include 'includes/dashboard/wpc-dashboard.php';
include 'includes/kit/wpc-kit.php';
include 'includes/hpos.php';

if ( ! function_exists( 'wpcme_init' ) ) {
	add_action( 'plugins_loaded', 'wpcme_init', 11 );

	function wpcme_init() {
		load_plugin_textdomain( 'wpc-multiple-external-product-urls', false, basename( __DIR__ ) . '/languages/' );

		if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0', '>=' ) ) {
			add_action( 'admin_notices', 'wpcme_notice_wc' );

			return null;
		}

		if ( ! class_exists( 'Wpcme' ) && class_exists( 'WC_Product' ) ) {
			class Wpcme {
				public function __construct() {
					require_once trailingslashit( WPCME_DIR ) . 'includes/class-helper.php';
					require_once trailingslashit( WPCME_DIR ) . 'includes/class-backend.php';
					require_once trailingslashit( WPCME_DIR ) . 'includes/class-frontend.php';
				}
			}

			new Wpcme();
		}
	}
}

if ( ! function_exists( 'wpcme_notice_wc' ) ) {
	function wpcme_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Multiple External Product URLs</strong> requires WooCommerce version 3.0 or greater.</p>
        </div>
		<?php
	}
}
