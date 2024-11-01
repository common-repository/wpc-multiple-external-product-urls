<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Wpcme_Backend' ) ) {
	class Wpcme_Backend {
		protected static $instance = null;

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

			// Settings
			add_action( 'admin_init', [ $this, 'register_settings' ] );
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );

			// Links
			add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
			add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

			// Single Product
			add_filter( 'woocommerce_product_data_tabs', [ $this, 'product_data_tabs' ] );
			add_action( 'woocommerce_product_data_panels', [ $this, 'product_data_panels' ] );
			add_action( 'woocommerce_process_product_meta', [ $this, 'process_product_meta' ] );

			// Variation
			add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'variation_settings' ], 99, 3 );
			add_action( 'woocommerce_save_product_variation', [ $this, 'save_variation_settings' ], 99, 2 );

			// Product columns
			add_filter( 'manage_edit-product_columns', [ $this, 'product_columns' ], 10 );
			add_action( 'manage_product_posts_custom_column', [ $this, 'custom_column' ], 10, 2 );

			// WPC Variation Duplicator
			add_action( 'wpcvd_duplicated', [ $this, 'duplicate_variation' ], 99, 2 );

			// WPC Variation Bulk Editor
			add_action( 'wpcvb_bulk_update_variation', [ $this, 'bulk_update_variation' ], 99, 2 );
		}

		public function product_data_tabs( $tabs ) {
			$tabs['wpcme'] = [
				'label'  => esc_html__( 'External URLs', 'wpc-multiple-external-product-urls' ),
				'target' => 'wpcme_settings'
			];

			return $tabs;
		}

		public function product_data_panels() {
			global $post, $thepostid, $product_object;

			if ( $product_object instanceof WC_Product ) {
				$product_id = $product_object->get_id();
			} elseif ( is_numeric( $thepostid ) ) {
				$product_id = $thepostid;
			} elseif ( $post instanceof WP_Post ) {
				$product_id = $post->ID;
			} else {
				$product_id = 0;
			}

			if ( ! $product_id ) {
				?>
                <div id='wpcme_settings' class='panel woocommerce_options_panel wpcme_settings'>
                    <p style="padding: 0 12px; color: #c9356e"><?php esc_html_e( 'Product wasn\'t returned.', 'wpc-multiple-external-product-urls' ); ?></p>
                </div>
				<?php
				return;
			}

			self::product_settings( $product_id );
		}

		function variation_settings( $loop, $variation_data, $variation ) {
			$variation_id = absint( $variation->ID );
			?>
            <div class="form-row form-row-full wpcme-variation-settings">
                <label><?php esc_html_e( 'WPC Multiple External Product URLs', 'wpc-multiple-external-product-urls' ); ?></label>
                <div class="wpcme-variation-wrap wpcme-variation-wrap-<?php echo esc_attr( $variation_id ); ?>">
					<?php self::product_settings( $variation_id, true ); ?>
                </div>
            </div>
			<?php
		}

		function product_settings( $product_id, $is_variation = false ) {
			$enable      = get_post_meta( $product_id, 'wpcme_enable', true ) ?: 'no';
			$purchasable = get_post_meta( $product_id, 'wpcme_purchasable', true ) ?: 'yes';
			$urls        = get_post_meta( $product_id, 'wpcme_urls', true ) ?: [];
			$new_tab     = ! empty( $urls['new_tab'] ) ? $urls['new_tab'] : 'no';

			$name  = '';
			$id    = 'wpcme_settings';
			$class = 'panel woocommerce_options_panel wpcme_settings';

			if ( $is_variation ) {
				$name  = '_v[' . $product_id . ']';
				$id    = '';
				$class = 'wpcme_settings';
			}
			?>
            <div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $class ); ?>">
				<?php do_action( 'wpcme_product_settings_before', $product_id ); ?>
                <div class="wpcme_settings_toggle">
                    <div class="wpcme_settings_toggle_inner">
                        <span><?php esc_html_e( 'External URLs', 'wpc-multiple-external-product-urls' ); ?></span>
                        <label>
                            <select name="<?php echo esc_attr( 'wpcme_enable' . $name ); ?>" class="wpcme_enable">
                                <option value="no" <?php selected( $enable, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-multiple-external-product-urls' ); ?></option>
                                <option value="yes" <?php selected( $enable, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-multiple-external-product-urls' ); ?></option>
                            </select> </label>
                    </div>
                </div>
                <div class="wpcme_settings_enable"
                     style="display: <?php echo esc_attr( $enable === 'yes' ? 'block' : 'none' ); ?>;">
					<?php do_action( 'wpcme_product_settings_enable_before', $product_id ); ?>
                    <div class="wpcme-items-wrapper">
                        <div class="wpcme-items">
                            <div class="wpcme-item active">
                                <div class="wpcme-item-content">
                                    <div class="wpcme-item-line">
                                        <label><?php esc_html_e( 'Purchasable', 'wpc-multiple-external-product-urls' ); ?></label>
                                        <div>
                                            <span class="hint--right"
                                                  aria-label="<?php esc_attr_e( 'Allows to purchase it on this store?', 'wpc-multiple-external-product-urls' ); ?>">
                                                <label><select class="wpcme_method"
                                                               name="<?php echo esc_attr( 'wpcme_purchasable' . $name ); ?>">
                                                    <option value="no" <?php selected( $purchasable, 'no' ); ?>><?php esc_attr_e( 'No', 'wpc-multiple-external-product-urls' ); ?></option>
                                                    <option value="yes" <?php selected( $purchasable, 'yes' ); ?>><?php esc_attr_e( 'Yes', 'wpc-multiple-external-product-urls' ); ?></option>
                                                </select></label>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="wpcme-item-line">
                                        <label><?php esc_html_e( 'Open in new tab', 'wpc-multiple-external-product-urls' ); ?></label>
                                        <div>
                                            <span class="hint--right"
                                                  aria-label="<?php esc_attr_e( 'Open external / affiliate URLs in new tab?', 'wpc-multiple-external-product-urls' ); ?>">
                                                <label><select class="wpcme_method"
                                                               name="<?php echo esc_attr( 'wpcme_urls' . $name . '[new_tab]' ); ?>">
                                                    <option value="no" <?php selected( $new_tab, 'no' ); ?>><?php esc_attr_e( 'No', 'wpc-multiple-external-product-urls' ); ?></option>
                                                    <option value="yes" <?php selected( $new_tab, 'yes' ); ?>><?php esc_attr_e( 'Yes', 'wpc-multiple-external-product-urls' ); ?></option>
                                                </select></label>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="wpcme-item-line">
                                        <label><?php esc_html_e( 'URLs', 'wpc-multiple-external-product-urls' ); ?></label>
										<?php
										$count = 0;

										if ( ! empty( $urls['urls'] ) && is_array( $urls['urls'] ) ) {
											foreach ( $urls['urls'] as $url ) {
												$url = array_merge( [ 'text' => '', 'url' => '' ], $url );
												?>
                                                <div class="input-panel wpcme-url">
                                                    <span class="wpcme-text-wrapper hint--top"
                                                          aria-label="<?php esc_attr_e( 'Button text', 'wpc-multiple-external-product-urls' ); ?>">
                                                        <label><input type="text"
                                                                      value="<?php echo esc_attr( $url['text'] ); ?>"
                                                                      class="wpcme-url-qty"
                                                                      placeholder="<?php esc_attr_e( 'Buy product', 'wpc-multiple-external-product-urls' ); ?>"
                                                                      name="<?php echo esc_attr( 'wpcme_urls' . $name . '[urls][' . $count . '][text]' ); ?>"/></label>
                                                    </span>
                                                    <span class="wpcme-url-wrapper hint--top"
                                                          aria-label="<?php esc_attr_e( 'Product URL', 'wpc-multiple-external-product-urls' ); ?>">
                                                        <label><input type="url"
                                                                      value="<?php echo esc_attr( $url['url'] ); ?>"
                                                                      placeholder="<?php esc_attr_e( 'https://', 'wpc-multiple-external-product-urls' ); ?>"
                                                                      class="wpcme-url-text"
                                                                      name="<?php echo esc_attr( 'wpcme_urls' . $name . '[urls][' . $count . '][url]' ); ?>"/></label>
                                                    </span>
                                                    <span class="wpcme-remove hint--top"
                                                          aria-label="<?php esc_attr_e( 'remove', 'wpc-multiple-external-product-urls' ); ?>">&times;</span>
                                                </div>
												<?php
												$count ++;
											}
										} ?>
                                        <button class="button wpcme-add-url" type="button"
                                                data-id="<?php echo esc_attr( $is_variation ? $product_id : 0 ); ?>"
                                                data-count="<?php echo esc_attr( is_array( $urls['urls'] ) && ! empty( $urls['urls'] ) ? count( $urls['urls'] ) : 0 ); ?>">
											<?php esc_html_e( '+ New URL', 'wpc-multiple-external-product-urls' ); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
					<?php do_action( 'wpcme_product_settings_enable_after', $product_id ); ?>
                </div>
				<?php do_action( 'wpcme_product_settings_after', $product_id ); ?>
            </div>
			<?php
		}

		public function process_product_meta( $post_id ) {
			if ( isset( $_POST['wpcme_enable'] ) ) {
				update_post_meta( $post_id, 'wpcme_enable', sanitize_text_field( $_POST['wpcme_enable'] ) );
			}

			if ( isset( $_POST['wpcme_purchasable'] ) ) {
				update_post_meta( $post_id, 'wpcme_purchasable', sanitize_text_field( $_POST['wpcme_purchasable'] ) );
			}

			if ( isset( $_POST['wpcme_urls'] ) ) {
				update_post_meta( $post_id, 'wpcme_urls', Wpcme_Helper()::sanitize_array( $_POST['wpcme_urls'] ) );
			}
		}

		function save_variation_settings( $post_id ) {
			if ( isset( $_POST['wpcme_enable_v'][ $post_id ] ) ) {
				update_post_meta( $post_id, 'wpcme_enable', sanitize_text_field( $_POST['wpcme_enable_v'][ $post_id ] ) );
			}

			if ( isset( $_POST['wpcme_purchasable_v'][ $post_id ] ) ) {
				update_post_meta( $post_id, 'wpcme_purchasable', sanitize_text_field( $_POST['wpcme_purchasable_v'][ $post_id ] ) );
			}

			if ( isset( $_POST['wpcme_urls_v'][ $post_id ] ) ) {
				update_post_meta( $post_id, 'wpcme_urls', Wpcme_Helper()::sanitize_array( $_POST['wpcme_urls_v'][ $post_id ] ) );
			}
		}

		function product_columns( $columns ) {
			$columns['wpcme'] = esc_html__( 'External URLs', 'wpc-multiple-external-product-urls' );

			return $columns;
		}

		function custom_column( $column, $postid ) {
			if ( $column === 'wpcme' ) {
				$enable = get_post_meta( $postid, 'wpcme_enable', true );
				$urls   = get_post_meta( $postid, 'wpcme_urls', true );

				if ( ( $enable === 'yes' ) && ! empty( $urls['urls'] ) && is_array( $urls['urls'] ) ) {
					echo '<ul class="wpcme-urls">';

					foreach ( $urls['urls'] as $url ) {
						$global_text = Wpcme_Helper()::get_setting( 'button_text' );
						$button_text = ! empty( $url['text'] ) ? $url['text'] : ( $global_text ?: esc_html__( 'Buy product', 'wpc-multiple-external-product-urls' ) );
						$button_link = ! empty( $url['url'] ) ? $url['url'] : '#';

						echo '<li><a href="' . esc_url( $button_link ) . '" target="_blank">' . esc_html( $button_text ) . '</a></li>';
					}

					echo '</ul>';
				}
			}
		}

		function duplicate_variation( $old_variation_id, $new_variation_id ) {
			if ( $enable = get_post_meta( $old_variation_id, 'wpcme_enable', true ) ) {
				update_post_meta( $new_variation_id, 'wpcme_enable', $enable );
			}

			if ( $purchasable = get_post_meta( $old_variation_id, 'wpcme_purchasable', true ) ) {
				update_post_meta( $new_variation_id, 'wpcme_purchasable', $purchasable );
			}

			if ( $urls = get_post_meta( $old_variation_id, 'wpcme_urls', true ) ) {
				update_post_meta( $new_variation_id, 'wpcme_urls', $urls );
			}
		}

		function bulk_update_variation( $variation_id, $fields ) {
			if ( ! empty( $fields['wpcme_enable_v'] ) && ( $fields['wpcme_enable_v'] !== 'wpcvb_no_change' ) ) {
				update_post_meta( $variation_id, 'wpcme_enable', sanitize_text_field( $fields['wpcme_enable_v'] ) );
			}

			if ( ! empty( $fields['wpcme_purchasable_v'] ) && ( $fields['wpcme_purchasable_v'] !== 'wpcvb_no_change' ) ) {
				update_post_meta( $variation_id, 'wpcme_purchasable', sanitize_text_field( $fields['wpcme_purchasable_v'] ) );
			}

			if ( ! empty( $fields['wpcme_enable_v'] ) && ( $fields['wpcme_enable_v'] === 'yes' ) && ! empty( $fields['wpcme_urls_v'] ) ) {
				update_post_meta( $variation_id, 'wpcme_urls', Wpcme_Helper()::sanitize_array( $fields['wpcme_urls_v'] ) );
			}
		}

		function register_settings() {
			// settings
			register_setting( 'wpcme_settings', 'wpcme_settings' );
		}

		public function admin_menu() {
			add_submenu_page( 'wpclever', esc_html__( 'WPC Multiple External Product URLs', 'wpc-multiple-external-product-urls' ), esc_html__( 'External URLs', 'wpc-multiple-external-product-urls' ), 'manage_options', 'wpclever-wpcme', [
				$this,
				'admin_menu_content'
			] );
		}

		public function admin_menu_content() {
			$active_tab = sanitize_key( $_GET['tab'] ?? 'settings' );
			?>
            <div class="wpclever_settings_page wrap">
                <h1 class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Multiple External Product URLs', 'wpc-multiple-external-product-urls' ) . ' ' . esc_html( WPCME_VERSION ) . ' ' . ( defined( 'WPCME_PREMIUM' ) ? '<span class="premium" style="display: none">' . esc_html__( 'Premium', 'wpc-multiple-external-product-urls' ) . '</span>' : '' ); ?></h1>
                <div class="wpclever_settings_page_desc about-text">
                    <p>
						<?php printf( /* translators: stars */ esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'wpc-multiple-external-product-urls' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                        <br/>
                        <a href="<?php echo esc_url( WPCME_REVIEWS ); ?>"
                           target="_blank"><?php esc_html_e( 'Reviews', 'wpc-multiple-external-product-urls' ); ?></a> |
                        <a href="<?php echo esc_url( WPCME_CHANGELOG ); ?>"
                           target="_blank"><?php esc_html_e( 'Changelog', 'wpc-multiple-external-product-urls' ); ?></a>
                        |
                        <a href="<?php echo esc_url( WPCME_DISCUSSION ); ?>"
                           target="_blank"><?php esc_html_e( 'Discussion', 'wpc-multiple-external-product-urls' ); ?></a>
                    </p>
                </div>
				<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php esc_html_e( 'Settings updated.', 'wpc-multiple-external-product-urls' ); ?></p>
                    </div>
				<?php } ?>
                <div class="wpclever_settings_page_nav">
                    <h2 class="nav-tab-wrapper">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpcme&tab=settings' ) ); ?>"
                           class="<?php echo esc_attr( $active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
							<?php esc_html_e( 'Settings', 'wpc-multiple-external-product-urls' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-kit' ) ); ?>" class="nav-tab">
							<?php esc_html_e( 'Essential Kit', 'wpc-multiple-external-product-urls' ); ?>
                        </a>
                    </h2>
                </div>
                <div class="wpclever_settings_page_content">
					<?php if ( $active_tab === 'settings' ) {
						$position_archive = Wpcme_Helper()::get_setting( 'position_archive', 'no' );
						$position_single  = Wpcme_Helper()::get_setting( 'position_single', 'under' );
						?>
                        <form method="post" action="options.php">
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'Position on product archive', 'wpc-multiple-external-product-urls' ); ?></th>
                                    <td>
                                        <label> <select name="wpcme_settings[position_archive]">
                                                <option value="above" <?php selected( $position_archive, 'above' ); ?>><?php esc_html_e( 'Above the add to cart button', 'wpc-multiple-external-product-urls' ); ?></option>
                                                <option value="under" <?php selected( $position_archive, 'under' ); ?>><?php esc_html_e( 'Under the add to cart button', 'wpc-multiple-external-product-urls' ); ?></option>
                                                <option value="under_title" <?php selected( $position_archive, 'under_title' ); ?>><?php esc_html_e( 'Under the title', 'wpc-multiple-external-product-urls' ); ?></option>
                                                <option value="under_price" <?php selected( $position_archive, 'under_price' ); ?>><?php esc_html_e( 'Under the price', 'wpc-multiple-external-product-urls' ); ?></option>
                                                <option value="no" <?php selected( $position_archive, 'no' ); ?>><?php esc_html_e( 'No (hide it)', 'wpc-multiple-external-product-urls' ); ?></option>
                                            </select> </label>
                                        <p class="description"><?php printf( /* translators: shortcode */ esc_html__( 'Choose where to display external product buttons on product archive page; use %s shortcode to place it anywhere.', 'wpc-multiple-external-product-urls' ), '<code>[wpcme]</code>' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Position on single product', 'wpc-multiple-external-product-urls' ); ?></th>
                                    <td>
                                        <label> <select name="wpcme_settings[position_single]">
                                                <option value="above" <?php selected( $position_single, 'above' ); ?>><?php esc_html_e( 'Above the add to cart button', 'wpc-multiple-external-product-urls' ); ?></option>
                                                <option value="under" <?php selected( $position_single, 'under' ); ?>><?php esc_html_e( 'Under the add to cart button', 'wpc-multiple-external-product-urls' ); ?></option>
                                                <option value="under_title" <?php selected( $position_single, 'under_title' ); ?>><?php esc_html_e( 'Under the title', 'wpc-multiple-external-product-urls' ); ?></option>
                                                <option value="under_price" <?php selected( $position_single, 'under_price' ); ?>><?php esc_html_e( 'Under the price', 'wpc-multiple-external-product-urls' ); ?></option>
                                                <option value="under_excerpt" <?php selected( $position_single, 'under_excerpt' ); ?>><?php esc_html_e( 'Under the excerpt', 'wpc-multiple-external-product-urls' ); ?></option>
                                                <option value="under_meta" <?php selected( $position_single, 'under_meta' ); ?>><?php esc_html_e( 'Under the meta', 'wpc-multiple-external-product-urls' ); ?></option>
                                                <option value="no" <?php selected( $position_single, 'no' ); ?>><?php esc_html_e( 'No (hide it)', 'wpc-multiple-external-product-urls' ); ?></option>
                                            </select> </label>
                                        <p class="description"><?php printf( /* translators: shortcode */ esc_html__( 'Choose where to display external product buttons on a single product page; use %s shortcode to place it anywhere.', 'wpc-multiple-external-product-urls' ), '<code>[wpcme]</code>' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Button CSS class', 'wpc-multiple-external-product-urls' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" class="text large-text"
                                                   name="wpcme_settings[button_class]"
                                                   placeholder="single_add_to_cart_button button alt"
                                                   value="<?php echo esc_attr( Wpcme_Helper()::get_setting( 'button_class' ) ); ?>"/>
                                        </label>
                                        <p class="description"><?php printf( /* translators: css class */ esc_html__( 'Add CSS class for external product button, split by one space. Default: %s', 'wpc-multiple-external-product-urls' ), '<code>single_add_to_cart_button button alt</code>' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Default button text', 'wpc-multiple-external-product-urls' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="wpcme_settings[button_text]"
                                                   placeholder="<?php esc_attr_e( 'Buy product', 'wpc-multiple-external-product-urls' ); ?>"
                                                   value="<?php echo esc_attr( Wpcme_Helper()::get_setting( 'button_text' ) ); ?>"/>
                                        </label>
                                        <span class="description"><?php esc_html_e( 'Use [n] for product name.', 'wpc-multiple-external-product-urls' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="submit">
                                    <th colspan="2">
										<?php settings_fields( 'wpcme_settings' ); ?><?php submit_button(); ?>
                                    </th>
                                </tr>
                            </table>
                        </form>
					<?php } ?>
                </div><!-- /.wpclever_settings_page_content -->
                <div class="wpclever_settings_page_suggestion">
                    <div class="wpclever_settings_page_suggestion_label">
                        <span class="dashicons dashicons-yes-alt"></span> Suggestion
                    </div>
                    <div class="wpclever_settings_page_suggestion_content">
                        <div>
                            To display custom engaging real-time messages on any wished positions, please install
                            <a href="https://wordpress.org/plugins/wpc-smart-messages/" target="_blank">WPC Smart
                                Messages</a> plugin. It's free!
                        </div>
                        <div>
                            Wanna save your precious time working on variations? Try our brand-new free plugin
                            <a href="https://wordpress.org/plugins/wpc-variation-bulk-editor/" target="_blank">WPC
                                Variation Bulk Editor</a> and
                            <a href="https://wordpress.org/plugins/wpc-variation-duplicator/" target="_blank">WPC
                                Variation Duplicator</a>.
                        </div>
                    </div>
                </div>
            </div>
			<?php
		}

		function action_links( $links, $file ) {
			static $plugin;

			if ( ! isset( $plugin ) ) {
				$plugin = plugin_basename( WPCME_FILE );
			}

			if ( $plugin === $file ) {
				$settings = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-wpcme&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'wpc-multiple-external-product-urls' ) . '</a>';
				array_unshift( $links, $settings );
			}

			return (array) $links;
		}

		function row_meta( $links, $file ) {
			static $plugin;

			if ( ! isset( $plugin ) ) {
				$plugin = plugin_basename( WPCME_FILE );
			}

			if ( $plugin === $file ) {
				$row_meta = [
					'support' => '<a href="' . esc_url( WPCME_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'wpc-multiple-external-product-urls' ) . '</a>',
				];

				return array_merge( $links, $row_meta );
			}

			return (array) $links;
		}

		public function enqueue_scripts() {
			wp_enqueue_style( 'hint', WPCME_URI . 'assets/css/hint.css' );
			wp_enqueue_style( 'wpcme-backend', WPCME_URI . 'assets/css/backend.css', [ 'woocommerce_admin_styles' ], WPCME_VERSION );
			wp_enqueue_script( 'wpcme-backend', WPCME_URI . 'assets/js/backend.js', [ 'jquery' ], WPCME_VERSION, true );
			wp_localize_script( 'wpcme-backend', 'wpcme_vars', [
				'nonce'       => wp_create_nonce( 'wpcme-security' ),
				'hint_text'   => esc_attr__( 'Button text', 'wpc-multiple-external-product-urls' ),
				'hint_url'    => esc_attr__( 'Product URL', 'wpc-multiple-external-product-urls' ),
				'hint_remove' => esc_attr__( 'remove', 'wpc-multiple-external-product-urls' ),
			] );
		}
	}

	function Wpcme_Backend() {
		return Wpcme_Backend::instance();
	}

	Wpcme_Backend();
}