<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Wpcme_Helper' ) ) {
	class Wpcme_Helper {
		protected static $settings = [];
		protected static $instance = null;

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		function __construct() {
			self::$settings = (array) get_option( 'wpcme_settings', [] );
		}

		public static function get_settings() {
			return apply_filters( 'wpcme_get_settings', self::$settings );
		}

		public static function get_setting( $name, $default = false ) {
			if ( ! empty( self::$settings ) && isset( self::$settings[ $name ] ) ) {
				$setting = self::$settings[ $name ];
			} else {
				$setting = get_option( 'wpcme_' . $name, $default );
			}

			return apply_filters( 'wpcme_get_setting', $setting, $name, $default );
		}

		public static function sanitize_array( $arr ) {
			foreach ( (array) $arr as $k => $v ) {
				if ( is_array( $v ) ) {
					$arr[ $k ] = self::sanitize_array( $v );
				} else {
					$arr[ $k ] = sanitize_post_field( 'post_content', $v, 0, 'display' );
				}
			}

			return $arr;
		}
	}

	function Wpcme_Helper() {
		return Wpcme_Helper::instance();
	}

	Wpcme_Helper();
}