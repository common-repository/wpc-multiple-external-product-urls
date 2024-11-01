<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Wpcme_Frontend' ) ) {
	class Wpcme_Frontend {
		protected static $instance = null;

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

			// Shortcode
			add_shortcode( 'wpcme', [ $this, 'shortcode' ] );

			// Purchasable
			add_filter( 'woocommerce_is_purchasable', [ $this, 'is_purchasable' ], 10, 2 );
			add_filter( 'woocommerce_variation_is_purchasable', [ $this, 'is_purchasable' ], 10, 2 );

			// Product archive
			switch ( Wpcme_Helper()::get_setting( 'position_archive', 'no' ) ) {
				case 'above':
					add_action( 'woocommerce_after_shop_loop_item', [ $this, 'display_urls_archive' ], 9 );
					break;
				case 'under':
					add_action( 'woocommerce_after_shop_loop_item', [ $this, 'display_urls_archive' ], 11 );
					break;
				case 'under_title':
					add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'display_urls_archive' ], 4 );
					break;
				case 'under_price':
					add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'display_urls_archive' ], 11 );
					break;
			}

			// Single product
			switch ( Wpcme_Helper()::get_setting( 'position_single', 'under' ) ) {
				case 'above':
					add_action( 'woocommerce_single_product_summary', [ $this, 'display_urls_single' ], 29 );
					break;
				case 'under':
					add_action( 'woocommerce_single_product_summary', [ $this, 'display_urls_single' ], 31 );
					break;
				case 'under_title':
					add_action( 'woocommerce_single_product_summary', [ $this, 'display_urls_single' ], 6 );
					break;
				case 'under_price':
					add_action( 'woocommerce_single_product_summary', [ $this, 'display_urls_single' ], 11 );
					break;
				case 'under_excerpt':
					add_action( 'woocommerce_single_product_summary', [ $this, 'display_urls_single' ], 21 );
					break;
				case 'under_meta':
					add_action( 'woocommerce_single_product_summary', [ $this, 'display_urls_single' ], 41 );
					break;
			}

			add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'add_to_cart_button' ] );

			// Variation
			add_filter( 'woocommerce_available_variation', [ $this, 'available_variation' ], 99, 3 );
			add_action( 'woocommerce_before_variations_form', [ $this, 'before_variations_form' ] );

			// WPC Smart Messages
			add_filter( 'wpcsm_locations', [ $this, 'wpcsm_locations' ] );
		}

		public function enqueue_scripts() {
			wp_enqueue_script( 'wpcme-frontend', WPCME_URI . 'assets/js/frontend.js', [ 'jquery' ], WPCME_VERSION, true );
		}

		function available_variation( $available, $variable, $variation ) {
			$enable      = apply_filters( 'wpcme_product_enable', get_post_meta( $variation->get_id(), 'wpcme_enable', true ) ?: 'no', $variation );
			$purchasable = apply_filters( 'wpcme_product_purchasable', get_post_meta( $variation->get_id(), 'wpcme_purchasable', true ) ?: 'yes', $variation );

			$available['wpcme_enable']      = $enable;
			$available['wpcme_purchasable'] = $purchasable;

			if ( $enable === 'yes' ) {
				$available['wpcme_urls'] = htmlentities( self::get_urls( $variation ) );
			}

			return $available;
		}

		function before_variations_form() {
			global $product;

			echo '<span class="wpcme-variable wpcme-variable-' . esc_attr( $product->get_id() ) . '" data-wpcme="' . esc_attr( htmlentities( self::get_urls( $product ) ) ) . '" style="display: none"></span>';
		}

		public function get_urls( $product = null, $context = 'single' ) {
			$product_id = 0;

			if ( is_numeric( $product ) ) {
				$product_id = $product;
				$product    = wc_get_product( $product_id );
			} elseif ( is_a( $product, 'WC_Product' ) ) {
				$product_id = $product->get_id();
			}

			if ( ! $product_id ) {
				return '';
			}

			$enable  = apply_filters( 'wpcme_product_enable', get_post_meta( $product_id, 'wpcme_enable', true ) ?: 'no', $product, $context );
			$urls    = apply_filters( 'wpcme_product_urls', get_post_meta( $product_id, 'wpcme_urls', true ) ?: [], $product, $context );
			$new_tab = wc_string_to_bool( ! empty( $urls['new_tab'] ) ? $urls['new_tab'] : 'no' );

			if ( is_a( $product, 'WC_Product_Variation' ) ) {
				$wrap_id = $product->get_parent_id();
			} else {
				$wrap_id = $product_id;
			}

			$wrap_class = apply_filters( 'wpcme_wrap_class', 'wpcme-wrap wpcme-wrap-' . $context . ' wpcme-wrap-' . $wrap_id, $product, $context );

			ob_start();
			echo '<div class="' . esc_attr( $wrap_class ) . '" data-id="' . esc_attr( $wrap_id ) . '">';
			// always render the wrapper to use it for variable product

			if ( ( $enable === 'yes' ) && ! empty( $urls['urls'] ) && is_array( $urls['urls'] ) ) {
				do_action( 'wpcme_urls_above', $product, $context );

				echo '<div class="wpcme-urls">';

				do_action( 'wpcme_urls_before', $product, $context );

				foreach ( $urls['urls'] as $url ) {
					$global_text  = Wpcme_Helper()::get_setting( 'button_text' );
					$button_text  = ! empty( $url['text'] ) ? $url['text'] : ( $global_text ?: esc_html__( 'Buy product', 'wpc-multiple-external-product-urls' ) );
					$button_text  = str_replace( '[n]', $product->get_name(), $button_text );
					$button_link  = ! empty( $url['url'] ) ? $url['url'] : '#';
					$global_class = Wpcme_Helper()::get_setting( 'button_class' );
					$button_class = apply_filters( 'wpcme_url_class', 'wpcme-url wpcme-btn ' . ( $global_class ?: 'single_add_to_cart_button button alt' ), $url, $product, $context );

					if ( $new_tab ) {
						echo '<a class="' . esc_attr( $button_class ) . '" href="' . esc_url( $button_link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $button_text ) . '</a>';
					} else {
						echo '<a class="' . esc_attr( $button_class ) . '" href="' . esc_url( $button_link ) . '">' . esc_html( $button_text ) . '</a>';
					}
				}

				do_action( 'wpcme_urls_after', $product, $context );

				echo '</div><!-- /wpcme-urls -->';

				do_action( 'wpcme_urls_under', $product, $context );
			}

			echo '</div><!-- /wpcme-wrap -->';

			return apply_filters( 'wpcme_get_urls', ob_get_clean(), $product, $context );
		}

		function shortcode( $attrs ) {
			$attrs = shortcode_atts( [ 'id' => null, 'context' => 'shortcode' ], $attrs, 'wpcme' );

			if ( ! $attrs['id'] ) {
				global $product;
			} else {
				$product = wc_get_product( $attrs['id'] );
			}

			if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
				return null;
			}

			return self::get_urls( $product, $attrs['context'] );
		}

		function add_to_cart_button() {
			global $product;

			echo '<span class="' . esc_attr( 'wpcme-id wpcme-id-' . $product->get_id() ) . '" data-id="' . esc_attr( $product->get_id() ) . '"></span>';
		}

		function is_purchasable( $purchasable, $product ) {
			$enable      = apply_filters( 'wpcme_product_enable', get_post_meta( $product->get_id(), 'wpcme_enable', true ) ?: 'no', $product );
			$purchasable = apply_filters( 'wpcme_product_purchasable', get_post_meta( $product->get_id(), 'wpcme_purchasable', true ) ?: 'yes', $product );

			if ( $enable === 'yes' && $purchasable === 'no' ) {
				return false;
			}

			return $purchasable;
		}

		public function display_urls_archive() {
			global $product;

			if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
				return;
			}

			echo self::get_urls( $product, 'archive' );
		}

		public function display_urls_variation() {
			global $product;

			if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
				return;
			}

			echo self::get_urls( $product, 'variation' );
		}

		public function display_urls_single() {
			global $product;

			if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
				return;
			}

			echo self::get_urls( $product, 'single' );
		}

		function wpcsm_locations( $locations ) {
			$locations['WPC Multiple External Product URLs'] = [
				'wpcme_urls_above' => esc_html__( 'Before URLs', 'wpc-multiple-external-product-urls' ),
				'wpcme_urls_under' => esc_html__( 'After URLs', 'wpc-multiple-external-product-urls' ),
			];

			return $locations;
		}
	}

	function Wpcme_Frontend() {
		return Wpcme_Frontend::instance();
	}

	Wpcme_Frontend();
}