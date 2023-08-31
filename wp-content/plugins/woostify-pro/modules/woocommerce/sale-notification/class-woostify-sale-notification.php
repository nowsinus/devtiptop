<?php
/**
 * Woostify Sale Notification Class
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_Sale_Notification' ) ) {
	/**
	 * Woostify Sale Notification Class
	 */
	class Woostify_Sale_Notification {

		/**
		 * Instance Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 *  Initiator
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->define_constants();
			$woocommerce_helper = Woostify_Woocommerce_Helper::init();

			add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ), 10 );

			// Save settings.
			add_action( 'wp_ajax_woostify_save_sale_notification_options', array( $woocommerce_helper, 'save_options' ) );

			// Select data.
			add_action( 'wp_ajax_woostify_sale_notification_select_categories', array( $woocommerce_helper, 'select_categories' ) );
			add_action( 'wp_ajax_woostify_sale_notification_select_products', array( $woocommerce_helper, 'select_products' ) );
			add_action( 'wp_ajax_woostify_sale_notification_exclude_products', array( $woocommerce_helper, 'exclude_products' ) );

			// Add Setting url.
			add_action( 'admin_menu', array( $this, 'add_setting_url' ) );

			// Print notification on frontend.
			add_action( 'woostify_footer_action', array( $this, 'print_footer_template' ) );

			add_action( 'init', array( $this, 'support_wpml_for_sale_notification' ) );
		}

		/**
		 * Update text domain
		 */
		public function support_wpml_for_sale_notification() {
			$mess    = get_option( 'woostify_sale_notification_message[]' );
			$message = explode( '@_sn', $mess );
			if ( ! empty( $message ) ) {
				foreach ( $message as $k ) {
					do_action( 'wpml_register_single_string', 'woostify-pro', 'Sale notification message', $k );
				}
			}
		}

		/**
		 * Define constant
		 */
		public function define_constants() {
			if ( ! defined( 'WOOSTIFY_PRO_SALE_NOTIFICATION' ) ) {
				define( 'WOOSTIFY_PRO_SALE_NOTIFICATION', WOOSTIFY_PRO_VERSION );
			}
		}

		/**
		 * Script and style file.
		 */
		public function scripts() {
			$options     = $this->get_options();
			$product_ids = $this->get_product_ids();
			$output      = array();
			$products    = array();

			$output['messages']   = explode( '@_sn', $options['message'] );
			$output['min_number'] = intval( $options['min_number'] );
			$output['max_number'] = intval( $options['max_number'] );
			$output['first_name'] = 'virtual' === $options['customer_info'] ? $this->textarea_lines_to_array( $options['vertual_first_name'] ) : $this->get_data_from_billing( 'name' );
			$output['city']       = 'virtual' === $options['customer_info'] ? $this->textarea_lines_to_array( $options['vertual_city'] ) : $this->get_data_from_billing( 'city' );
			$output['state']      = 'virtual' === $options['customer_info'] ? $this->textarea_lines_to_array( $options['vertual_state'] ) : $this->get_data_from_billing( 'state' );
			$output['country']    = 'virtual' === $options['customer_info'] ? $this->textarea_lines_to_array( $options['vertual_country'] ) : $this->get_data_from_billing( 'country' );
			$output['time']       = 'virtual' === $options['customer_info'] ? intval( $options['virtual_time'] ) : $this->get_data_from_billing( 'time' );
			$output['ago']        = esc_html__( 'ago', 'woostify-pro' );
			$output['hour']       = esc_html__( 'hour', 'woostify-pro' );
			$output['hours']      = esc_html__( 'hours', 'woostify-pro' );
			$output['minute']     = esc_html__( 'minute', 'woostify-pro' );
			$output['minutes']    = esc_html__( 'minutes', 'woostify-pro' );
			$output['second']     = esc_html__( 'second', 'woostify-pro' );
			$output['seconds']    = esc_html__( 'seconds', 'woostify-pro' );

			if ( ! empty( $product_ids ) ) {
				foreach ( $product_ids as $v ) {
					$img_src = has_post_thumbnail( $v ) ? get_the_post_thumbnail_url( $v, 'woocommerce_gallery_thumbnail' ) : false;
					$img_id  = get_post_thumbnail_id( $v );
					$img_alt = woostify_image_alt( $img_id, get_the_title( $v ) );

					$products[] = array(
						'title' => get_the_title( $v ),
						'link'  => get_permalink( $v ),
						'src'   => $img_src,
						'alt'   => $img_alt,
					);
				}

				$output['products'] = $products;
			}

			// Script.
			wp_enqueue_script(
				'woostify-sale-notification',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/sale-notification/js/script' . woostify_suffix() . '.js',
				array(),
				WOOSTIFY_PRO_VERSION,
				true
			);

			wp_localize_script(
				'woostify-sale-notification',
				'woostify_sale_notification',
				array(
					'ajax_url'          => admin_url( 'admin-ajax.php' ),
					'ajax_nonce'        => wp_create_nonce( 'woostify_sale_notification' ),
					'loop'              => $options['loop'],
					'initial_display'   => $options['initial_display'],
					'display_time'      => $options['display_time'],
					'next_time_display' => $options['next_time_display'],
					'data'              => $output,
				)
			);

			// Style.
			wp_enqueue_style(
				'woostify-sale-notification',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/sale-notification/css/style.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);
		}

		/**
		 * Add submenu
		 *
		 * @see  add_submenu_page()
		 */
		public function add_setting_url() {
			$position = 9;
			$sub_menu = add_submenu_page( 'woostify-welcome', 'Settings', __( 'Sale Notification', 'woostify-pro' ), 'manage_options', 'sale-notification-settings', array( $this, 'add_settings_page' ), $position );
		}

		/**
		 * Gets the options.
		 */
		public function get_options() {
			$options = array();
			// General.
			$options['position'] = get_option( 'woostify_sale_notification_position', 'bottom-left' );
			$options['mobile']   = get_option( 'woostify_sale_notification_mobile', '1' );
			// Message.
			$options['message']            = get_option( 'woostify_sale_notification_message[]', '{number} people seeing this product right now.' );
			$options['min_number']         = get_option( 'woostify_sale_notification_min_number', '100' );
			$options['max_number']         = get_option( 'woostify_sale_notification_max_number', '200' );
			$options['customer_info']      = get_option( 'woostify_sale_notification_customer_info', 'virtual' );
			$options['virtual_time']       = get_option( 'woostify_sale_notification_virtual_time', '10' );
			$options['vertual_first_name'] = get_option( 'woostify_sale_notification_vertual_first_name', "Halley\nFermi" );
			$options['vertual_city']       = get_option( 'woostify_sale_notification_vertual_city', "Houston\nLos Angeles" );
			$options['vertual_state']      = get_option( 'woostify_sale_notification_vertual_state', "Michigan\nNew York" );
			$options['vertual_country']    = get_option( 'woostify_sale_notification_vertual_country', "USA\nAlbania" );
			// Products.
			$options['show_products']       = get_option( 'woostify_sale_notification_show_products', 'newest-products' );
			$options['selected_categories'] = get_option( 'woostify_sale_notification_categories_selected', '' );
			$options['exclude_products']    = get_option( 'woostify_sale_notification_products_exclude', '' );
			$options['selected_products']   = get_option( 'woostify_sale_notification_products_selected', '' );
			// Time.
			$options['loop']              = get_option( 'woostify_sale_notification_loop', '1' );
			$options['initial_display']   = get_option( 'woostify_sale_notification_initial_display', '3' );
			$options['display_time']      = get_option( 'woostify_sale_notification_time_display', '3' );
			$options['next_time_display'] = get_option( 'woostify_sale_notification_next_time_display', '5' );

			return $options;
		}

		/**
		 * Create Settings page
		 */
		public function add_settings_page() {
			$woocommerce_helper = Woostify_Woocommerce_Helper::init();
			$options            = $this->get_options();
			$message            = explode( '@_sn', $options['message'] );
			?>
			<div class="woostify-options-wrap woostify-featured-setting woostify-sale-notification-setting" data-id="sale-notification" data-nonce="<?php echo esc_attr( wp_create_nonce( 'woostify-sale-notification-setting-nonce' ) ); ?>">
				
				<?php Woostify_Admin::get_instance()->woostify_save_option_messages(); ?>

				<?php Woostify_Admin::get_instance()->woostify_welcome_screen_header(); ?>

				<div class="wrap woostify-settings-box">
					<div class="woostify-welcome-container">
						<div class="woostify-notices-wrap">
							<h2 class="notices" style="display:none;"></h2>
						</div>
						<div class="woostify-settings-content">
							<h4 class="woostify-settings-section-title"><?php esc_html_e( 'Sale Notification', 'woostify-pro' ); ?></h4>

							<div class="woostify-settings-section-content woostify-settings-section-tab">
								<div class="woostify-setting-tab-head">
									<a href="#general" class="tab-head-button"><?php esc_html_e( 'General', 'woostify-pro' ); ?></a>
									<a href="#messages" class="tab-head-button"><?php esc_html_e( 'Messages', 'woostify-pro' ); ?></a>
									<a href="#products" class="tab-head-button"><?php esc_html_e( 'Products', 'woostify-pro' ); ?></a>
									<a href="#time" class="tab-head-button"><?php esc_html_e( 'Time', 'woostify-pro' ); ?></a>
								</div>

								<div class="woostify-setting-tab-content-wrapper">
									<?php // General. ?>
									<table class="form-table woostify-setting-tab-content" data-tab="general">
										<tr>
											<th scope="row"><?php esc_html_e( 'Position', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_sale_notification_position">
													<option value="bottom-left" <?php selected( $options['position'], 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'woostify-pro' ); ?></option>
													<option value="bottom-right" <?php selected( $options['position'], 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Mobile', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_sale_notification_mobile">
													<input name="woostify_sale_notification_mobile" type="checkbox" id="woostify_sale_notification_mobile" <?php checked( $options['mobile'], '1' ); ?> value="<?php echo esc_attr( $options['mobile'] ); ?>">
													<?php esc_html_e( 'Enable sale notification on mobile', 'woostify-pro' ); ?>
												</label>
											</td>
										</tr>
									</table>

									<?php // Messages. ?>
									<table class="form-table woostify-setting-tab-content" data-tab="messages">
										<tr>
											<th scope="row"><?php esc_html_e( 'Message', 'woostify-pro' ); ?>:</th>
											<td>
												<div class="woostify-sale-notification-box-message">
													<?php
													$attr = 1 === count( $message ) ? 'disabled="disabled"' : '';
													foreach ( $message as $k ) {
														?>
														<div class="woostify-sale-notification-message-inner">
															<textarea name="woostify_sale_notification_message[]" required="required"><?php echo esc_html( $k ); ?></textarea>

															<span class="woostify-sale-notification-remove-message button" <?php echo wp_kses_post( $attr ); ?> ><?php esc_html_e( 'Remove', 'woostify-pro' ); ?></span>
														</div>
													<?php } ?>
												</div>
												<span class="woostify-sale-notification-add-message button"><?php esc_html_e( 'Add New', 'woostify-pro' ); ?></span>

												<ul class="woostify-sale-notification-message-info">
													<li>{first_name} - <?php esc_html_e( "Customer's first name", 'woostify-pro' ); ?></li>
													<li>{state} - <?php esc_html_e( "Customer's state", 'woostify-pro' ); ?></li>
													<li>{city} - <?php esc_html_e( "Customer's city", 'woostify-pro' ); ?></li>
													<li>{country} - <?php esc_html_e( "Customer's country", 'woostify-pro' ); ?></li>
													<li>{product_title} - <?php esc_html_e( 'Product title', 'woostify-pro' ); ?></li>
													<li>{product_title_with_link} - <?php esc_html_e( 'Product title with link', 'woostify-pro' ); ?></li>
													<li>{time_ago} - <?php esc_html_e( 'The time later from now', 'woostify-pro' ); ?></li>
													<li>{number} - <?php esc_html_e( 'Number will random from Min number to Max number', 'woostify-pro' ); ?></li>
												</ul>
											</td>
										</tr>

										<?php // Min number. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Min Number', 'woostify-pro' ); ?>:</th>
											<td>
												<input type="number" name="woostify_sale_notification_min_number" value="<?php echo esc_attr( $options['min_number'] ); ?>">
											</td>
										</tr>

										<?php // Max number. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Max Number', 'woostify-pro' ); ?>:</th>
											<td>
												<input type="number" name="woostify_sale_notification_max_number" value="<?php echo esc_attr( $options['max_number'] ); ?>">
											</td>
										</tr>

										<?php // Customer information. ?>
										<tr class="woostify-filter-item">
											<th scope="row"><?php esc_html_e( 'Customer Information', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_sale_notification_customer_info" class="woostify-filter-value">
													<option value="virtual" <?php selected( $options['customer_info'], 'virtual' ); ?>><?php esc_html_e( 'Virtual', 'woostify-pro' ); ?></option>
													<option value="from-billing" <?php selected( $options['customer_info'], 'from-billing' ); ?>><?php esc_html_e( 'From Billing', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>

										<?php // Virtual time. ?>
										<tr class="woostify-filter-item <?php echo 'virtual' === $options['customer_info'] ? '' : 'hidden'; ?>" data-type="virtual">
											<th scope="row"><?php esc_html_e( 'Virtual Time', 'woostify-pro' ); ?>:</th>
											<td>
												<label>
													<input type="number" name="woostify_sale_notification_virtual_time" value="<?php echo esc_attr( $options['virtual_time'] ); ?>"> <?php esc_html_e( 'hours', 'woostify-pro' ); ?>
												</label>

												<p class="woostify-setting-description"><?php esc_html_e( 'Time will auto get random in this time threshold ago.', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<?php // First name. ?>
										<tr class="woostify-filter-item <?php echo 'virtual' === $options['customer_info'] ? '' : 'hidden'; ?>" data-type="virtual">
											<th scope="row"><?php esc_html_e( 'Virtual First Name', 'woostify-pro' ); ?>:</th>
											<td>
												<textarea rows="6" name="woostify_sale_notification_vertual_first_name" required="required"><?php echo esc_html( $options['vertual_first_name'] ); ?></textarea>

												<p class="woostify-setting-description"><?php esc_html_e( 'Virtual first name what will show on notification. Each first name on a line.', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<?php // Customer city. ?>
										<tr class="woostify-filter-item <?php echo 'virtual' === $options['customer_info'] ? '' : 'hidden'; ?>" data-type="virtual">
											<th scope="row"><?php esc_html_e( 'Virtual City', 'woostify-pro' ); ?>:</th>
											<td>
												<textarea rows="6" name="woostify_sale_notification_vertual_city" required="required"><?php echo esc_html( $options['vertual_city'] ); ?></textarea>

												<p class="woostify-setting-description"><?php esc_html_e( 'Virtual city what will show on notification. Each city on a line.', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<?php // Customer state. ?>
										<tr class="woostify-filter-item <?php echo 'virtual' === $options['customer_info'] ? '' : 'hidden'; ?>" data-type="virtual">
											<th scope="row"><?php esc_html_e( 'Virtual State', 'woostify-pro' ); ?>:</th>
											<td>
												<textarea rows="6" name="woostify_sale_notification_vertual_state" required="required"><?php echo esc_html( $options['vertual_state'] ); ?></textarea>

												<p class="woostify-setting-description"><?php esc_html_e( 'Virtual state what will show on notification. Each state on a line.', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<?php // Customer country. ?>
										<tr class="woostify-filter-item <?php echo 'virtual' === $options['customer_info'] ? '' : 'hidden'; ?>" data-type="virtual">
											<th scope="row"><?php esc_html_e( 'Virtual Country', 'woostify-pro' ); ?>:</th>
											<td>
												<textarea rows="6" name="woostify_sale_notification_vertual_country" required="required"><?php echo esc_html( $options['vertual_country'] ); ?></textarea>

												<p class="woostify-setting-description"><?php esc_html_e( 'Virtual country what will show on notification. Each country on a line.', 'woostify-pro' ); ?></p>
											</td>
										</tr>
									</table>

									<?php // Products. ?>
									<table class="form-table woostify-setting-tab-content" data-tab="products">
										<tr class="woostify-filter-item">
											<th scope="row"><?php esc_html_e( 'Source', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_sale_notification_show_products" class="woostify-filter-value">
													<option value="newest-products" <?php selected( $options['show_products'], 'newest-products' ); ?>><?php esc_html_e( 'Newest Products', 'woostify-pro' ); ?></option>
													<option value="recent-viewed-products" <?php selected( $options['show_products'], 'recent-viewed-products' ); ?>><?php esc_html_e( 'Recent Viewed Products', 'woostify-pro' ); ?></option>
													<option value="get-from-billing" <?php selected( $options['show_products'], 'get-from-billing' ); ?>><?php esc_html_e( 'Get From Billing', 'woostify-pro' ); ?></option>
													<option value="select-categories" <?php selected( $options['show_products'], 'select-categories' ); ?>><?php esc_html_e( 'Select Categories', 'woostify-pro' ); ?></option>
													<option value="select-products" <?php selected( $options['show_products'], 'select-products' ); ?>><?php esc_html_e( 'Select Products', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>

										<tr class="woostify-filter-item <?php echo 'select-categories' === $options['show_products'] ? '' : 'hidden'; ?>" data-type="select-categories">
											<th scope="row"><?php esc_html_e( 'Select Categories', 'woostify-pro' ); ?>:</th>
											<td>
												<div class="woostify-multi-selection">
													<input class="woostify-multi-select-value" name="woostify_sale_notification_categories_selected" type="hidden" value="<?php echo esc_attr( $options['selected_categories'] ); ?>">

													<div class="woostify-multi-select-selection">
														<div class="woostify-multi-selection-inner">
															<?php $woocommerce_helper->render_selection( $options['selected_categories'] ); ?>
														</div>

														<input type="text" class="woostify-multi-select-search" placeholder="<?php esc_attr_e( 'Please enter 1 or more characters', 'woostify-pro' ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'woostify-select-categories' ) ); ?>" name="woostify_sale_notification_select_categories">
													</div>

													<div class="woostify-multi-select-dropdown"></div>
												</div>

												<p class="woostify-setting-description"><?php esc_html_e( 'Type \'all\' to select all categories.', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr class="woostify-filter-item <?php echo ! in_array( $options['show_products'], array( 'select-categories', 'select-products' ), true ) ? '' : 'hidden'; ?>" data-type="select-categories|get-from-billing|newest-products|recent-viewed-products">
											<th scope="row"><?php esc_html_e( 'Exclude Products', 'woostify-pro' ); ?>:</th>
											<td>
												<div class="woostify-multi-selection">
													<input class="woostify-multi-select-value" name="woostify_sale_notification_products_exclude" type="hidden" value="<?php echo esc_attr( $options['exclude_products'] ); ?>">

													<div class="woostify-multi-select-selection">
														<div class="woostify-multi-selection-inner">
															<?php $woocommerce_helper->render_selection( $options['exclude_products'], false ); ?>
														</div>

														<input type="text" class="woostify-multi-select-search" placeholder="<?php esc_attr_e( 'Please enter 1 or more characters', 'woostify-pro' ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'woostify-exclude-products' ) ); ?>" name="woostify_sale_notification_exclude_products">
													</div>

													<div class="woostify-multi-select-dropdown"></div>
												</div>
											</td>
										</tr>

										<tr class="woostify-filter-item <?php echo 'select-products' === $options['show_products'] ? '' : 'hidden'; ?>" data-type="select-products">
											<th scope="row"><?php esc_html_e( 'Select Products', 'woostify-pro' ); ?>:</th>
											<td>
												<div class="woostify-multi-selection">
													<input class="woostify-multi-select-value" name="woostify_sale_notification_products_selected" type="hidden" value="<?php echo esc_attr( $options['selected_products'] ); ?>">

													<div class="woostify-multi-select-selection">
														<div class="woostify-multi-selection-inner">
															<?php $woocommerce_helper->render_selection( $options['selected_products'], false ); ?>
														</div>

														<input type="text" class="woostify-multi-select-search" placeholder="<?php esc_attr_e( 'Please enter 1 or more characters', 'woostify-pro' ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'woostify-select-products' ) ); ?>" name="woostify_sale_notification_select_products">
													</div>

													<div class="woostify-multi-select-dropdown"></div>
												</div>

												<p class="woostify-setting-description"><?php esc_html_e( 'Type \'all\' to select all products.', 'woostify-pro' ); ?></p>
											</td>
										</tr>
									</table>

									<?php // Time. ?>
									<table class="form-table woostify-setting-tab-content" data-tab="time">
										<?php // Loop. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Loop', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_sale_notification_loop">
													<input name="woostify_sale_notification_loop" type="checkbox" id="woostify_sale_notification_loop" <?php checked( $options['loop'], '1' ); ?> value="<?php echo esc_attr( $options['loop'] ); ?>">
													<?php esc_html_e( 'Enable sale notification loop', 'woostify-pro' ); ?>
												</label>
											</td>
										</tr>

										<?php // Initial display. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Initial Display', 'woostify-pro' ); ?>:</th>
											<td>
												<label>
													<input type="number" name="woostify_sale_notification_initial_display" value="<?php echo esc_attr( $options['initial_display'] ); ?>"> <?php esc_html_e( 'seconds', 'woostify-pro' ); ?>
												</label>

												<p class="woostify-setting-description"><?php esc_html_e( 'When your site loaded, notifications will show after this amount time.', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<?php // Time display. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Time Display', 'woostify-pro' ); ?>:</th>
											<td>
												<label>
													<input type="number" name="woostify_sale_notification_time_display" value="<?php echo esc_attr( $options['display_time'] ); ?>"> <?php esc_html_e( 'seconds', 'woostify-pro' ); ?>
												</label>

												<p class="woostify-setting-description"><?php esc_html_e( 'Time your notification display.', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<?php // Next time display. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Next Time Display', 'woostify-pro' ); ?>:</th>
											<td>
												<label>
													<input type="number" name="woostify_sale_notification_next_time_display" value="<?php echo esc_attr( $options['next_time_display'] ); ?>"> <?php esc_html_e( 'seconds', 'woostify-pro' ); ?>
												</label>

												<p class="woostify-setting-description"><?php esc_html_e( 'Set time to show next notification, adjective when the previous notification is hidden.', 'woostify-pro' ); ?></p>
											</td>
										</tr>
									</table>
								</div>
							</div>

							<div class="woostify-settings-section-footer">
								<span class="save-options button button-primary"><?php esc_html_e( 'Save', 'woostify-pro' ); ?></span>
								<span class="spinner"></span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Get random value
		 *
		 * @param array      $array The array.
		 * @param bolean|int $item  The array item.
		 */
		public function get_random_value( $array, $item = false ) {
			if ( false !== $item ) {
				$array[ $item ];
			}

			if ( empty( $array ) ) {
				return '';
			}

			$random = array_rand( $array );
			return $array[ $random ];
		}

		/**
		 * Get time
		 */
		public function get_time() {
			$options  = $this->get_options();
			$text     = '';
			$time     = absint( $options['virtual_time'] ) * 3600000;
			$time     = wp_rand( 10000, $time );
			$one_day  = 24 * 60 * 60 * 1000;
			$one_hour = 60 * 60 * 1000;
			$days     = intval( $time / $one_day );
			$hours    = intval( ( $time - $days * $one_day ) / $one_hour );
			$minutes  = intval( ( $time - $days * $one_day - $hours * $one_hour ) / 60000 );
			$seconds  = intval( ( $time % 60000 ) / 1000 );

			if ( $hours >= 1 ) {
				/* translators: 1: The time: hours */
				$text = sprintf( _n( '%s hour', '%s hours', $hours, 'woostify-pro' ), $hours );
			} elseif ( $minutes > 0 ) {
				/* translators: 1: The time: minutes */
				$text = sprintf( _n( '%s minute', '%s minutes', $minutes, 'woostify-pro' ), $minutes );
			} elseif ( $seconds > 0 ) {
				/* translators: 1: The time: seconds */
				$text = sprintf( _n( '%s second', '%s seconds', $seconds, 'woostify-pro' ), $seconds );
			}

			return $text;
		}

		/**
		 * Gets the data from billing.
		 *
		 * @param string $data_type The data type.
		 */
		public function get_data_from_billing( $data_type = 'ids' ) {
			$args = array(
				'limit'  => 99,
				'status' => 'completed',
			);

			$orders = wc_get_orders( $args );
			$output = array();

			if ( empty( $orders ) ) {
				return array();
			}

			foreach ( $orders as $k ) {
				switch ( $data_type ) {
					case 'name':
						if ( method_exists( $k, 'get_billing_first_name' ) ) {
							array_push( $output, $k->get_billing_first_name() );
						}
						break;
					case 'city':
						if ( method_exists( $k, 'get_billing_city' ) ) {
							array_push( $output, $k->get_billing_city() );
						}
						break;
					case 'state':
						if ( method_exists( $k, 'get_billing_state' ) ) {
							array_push( $output, $k->get_billing_state() );
						}
						break;
					case 'country':
						if ( method_exists( $k, 'get_billing_country' ) ) {
							array_push( $output, $k->get_billing_country() );
						}
						break;
					case 'time':
						if ( method_exists( $k, 'get_date_completed' ) ) {
							$timestamp   = time() + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
							$time_format = get_date_from_gmt( $k->get_date_completed(), 'Y-m-d H:i:s' );

							$time = human_time_diff( strtotime( $time_format ), $timestamp );

							array_push( $output, $time );
						}
						break;
					case 'ids':
					default:
						$items = $k->get_items();
						if ( ! empty( $items ) ) {
							foreach ( $items as $v ) {
								array_push( $output, $v->get_product_id() );
							}
						}
						break;
				}
			}

			return array_values( array_unique( $output ) );
		}

		/**
		 * Convert textarea lines to array.
		 *
		 * @param string $text_area The textarea input.
		 */
		public function textarea_lines_to_array( $text_area = '' ) {
			if ( empty( $text_area ) ) {
				return array();
			}

			return explode( "\n", str_replace( "\r", '', $text_area ) );
		}

		/**
		 * Get message
		 *
		 * @param int $product_id The product id.
		 */
		public function get_message( $product_id ) {
			$options = $this->get_options();
			$output  = array();
			if ( empty( $options['message'] ) ) {
				return $output;
			}

			// Messages.
			$message = explode( '@_sn', $options['message'] );
			// Number.
			$min_number = absint( $options['min_number'] );
			$max_number = absint( $options['max_number'] );

			if ( 'virtual' === $options['customer_info'] ) {
				// First name.
				$customer_name = $this->textarea_lines_to_array( $options['vertual_first_name'] );
				// City.
				$address = $this->textarea_lines_to_array( $options['vertual_city'] );
				// State.
				$state = $this->textarea_lines_to_array( $options['vertual_state'] );
				// Country.
				$country = $this->textarea_lines_to_array( $options['vertual_country'] );
			} else {
				// First name.
				$customer_name = $this->get_data_from_billing( 'name' );
				// City.
				$address = $this->get_data_from_billing( 'city' );
				// State.
				$state = $this->get_data_from_billing( 'state' );
				// Country.
				$country = $this->get_data_from_billing( 'country' );
			}

			foreach ( $message as $k => $v ) {
				$item = 'virtual' === $options['customer_info'] ? false : $k;
				// Time.
				$time = 'virtual' === $options['customer_info'] ? $this->get_time() : $this->get_random_value( $this->get_data_from_billing( 'time' ), $item );
				$text = str_replace( '{time_ago}', /* translators: A long time ago */ sprintf( __( '%s ago', 'woostify-pro' ), $time ), $v );

				// Number.
				$text = str_replace( '{number}', wp_rand( $min_number, $max_number ), $text );

				// First name.
				if ( ! empty( $customer_name ) ) {
					$text = str_replace( '{first_name}', $this->get_random_value( $customer_name, $item ), $text );
				}

				// City.
				if ( ! empty( $address ) ) {
					$text = str_replace( '{city}', $this->get_random_value( $address, $item ), $text );
				}

				// State.
				if ( ! empty( $state ) ) {
					$text = str_replace( '{state}', $this->get_random_value( $state, $item ), $text );
				}

				// Country.
				if ( ! empty( $country ) ) {
					$text = str_replace( '{country}', $this->get_random_value( $country, $item ), $text );
				}

				// Product.
				if ( ! empty( $product_id ) ) {
					$product_title = get_the_title( $product_id );
					$product_link  = get_permalink( $product_id );
					$text          = str_replace( '{product_title}', $product_title, $text );
					$text          = str_replace( '{product_title_with_link}', '<a class="sale-notification-product-link" href="' . esc_url( $product_link ) . '">' . esc_html( $product_title ) . '</a>', $text );
				}

				$output[ $k ] = $text;
			}

			return $output;
		}

		/**
		 * Gets the product ids.
		 *
		 * @return     array The product ids.
		 */
		public function get_product_ids() {
			$options  = $this->get_options();
			$is_empty = false;
			$args     = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);
			$all = false;
			switch ( $options['show_products'] ) {
				case 'newest-products':
				default:
					$args['order']   = 'DESC';
					$args['orderby'] = 'date';
					break;
				case 'recent-viewed-products':
					$cookies = isset( $_COOKIE['woostify_product_recently_viewed'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['woostify_product_recently_viewed'] ) ) : false;
					if ( empty( $cookies ) ) {
						$is_empty = true;
						break;
					}
					$args['post__in'] = array_unique( explode( '|', $cookies ) );
					break;
				case 'select-categories':
					if ( empty( $options['selected_categories'] ) ) {
						$is_empty = true;
						break;
					}
					$all = false !== strpos( $options['selected_categories'], 'all' );

					if ( ! $all ) {
						$args['tax_query'] = array( // phpcs:ignore
							array(
								'taxonomy' => 'product_cat',
								'field'    => 'term_id',
								'terms'    => explode( '|', $options['selected_categories'] ),
							),
						);
					}
					break;
				case 'select-products':
					if ( empty( $options['selected_products'] ) ) {
						$is_empty = true;
						break;
					}

					$all = false !== strpos( $options['selected_products'], 'all' );

					if ( ! $all ) {
						$args['post__in'] = explode( '|', $options['selected_products'] );
					}
					break;
				case 'get-from-billing':
					$args['post__in'] = $this->get_data_from_billing();
					break;
			}

			// Exclude products.
			if ( 'select-products' !== $options['show_products'] && $options['exclude_products'] ) {
				$exclude_products = explode( '|', $options['exclude_products'] );

				if ( empty( $args['post__in'] ) ) {
					$args['post__not_in'] = $exclude_products;
				} else {
					$args['post__in'] = array_diff( $args['post__in'], $exclude_products );
				}
			}

			// Is empty.
			if ( $is_empty || ( empty( $args['post__in'] ) && in_array( $options['show_products'], array( 'get-from-billing', 'select-products', 'recent-viewed-products' ), true ) && $all == false ) ) {
				return array();
			}

			return get_posts( $args );
		}

		/**
		 * Print sale notification on frontend.
		 */
		public function print_footer_template() {
			if ( is_checkout() ) {
				return;
			}

			$options   = $this->get_options();
			$classes[] = 'woostify-sale-notification-box';
			$classes[] = $options['position'];
			$classes[] = '1' === $options['mobile'] ? 'display-on-mobile' : '';
			?>

			<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
				<span class="sale-notification-close-button">
				<?php echo woostify_fetch_svg_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>

				<div class="sale-notification-inner">
				</div>
			</div>
			<?php
		}
	}

	Woostify_Sale_Notification::get_instance();
}

