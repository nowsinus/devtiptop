<?php
/**
 * Woostify Pre Order
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_Pre_Order' ) ) :

	/**
	 * Woostify Pre Order
	 */
	class Woostify_Pre_Order {
		/**
		 * Instance Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 * Extra attribute types
		 *
		 * @var array
		 */
		public $types = array();

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

			$this->includes();

			// Save settings.
			$woocommerce_helper = Woostify_Woocommerce_Helper::init();
			add_action( 'wp_ajax_woostify_save_pre_order_options', array( $woocommerce_helper, 'save_options' ) );

			// Add Setting url.
			add_action( 'admin_menu', array( $this, 'add_setting_url' ) );
			// Register settings.
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}

		/**
		 * Define constant
		 */
		public function define_constants() {
			if ( ! defined( 'WOOSTIFY_PRO_PRE_ORDER' ) ) {
				define( 'WOOSTIFY_PRO_PRE_ORDER', WOOSTIFY_PRO_VERSION );
			}
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function includes() {
			require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/pre-order/inc/class-woostify-pre-order-frontend.php';

			if ( is_admin() ) {
				require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/pre-order/inc/class-woostify-pre-order-admin.php';
			}
		}

		/**
		 * Add submenu
		 *
		 * @see  add_submenu_page()
		 */
		public function add_setting_url() {
			$position = 8;
			$sub_menu = add_submenu_page( 'woostify-welcome', 'Settings', __( 'Pre Order', 'woostify-pro' ), 'manage_options', 'pre-order-settings', array( $this, 'add_settings_page' ), $position );
		}

		/**
		 * Register settings
		 */
		public function register_settings() {
			register_setting( 'pre-order-settings', 'woostify_pre_order_label' );
			register_setting( 'pre-order-settings', 'woostify_pre_order_closed_label' );
			register_setting( 'pre-order-settings', 'woostify_pre_order_message' );
			register_setting( 'pre-order-settings', 'woostify_pre_order_countdown' );
			register_setting( 'pre-order-settings', 'woostify_pre_order_countdown_label' );
			register_setting( 'pre-order-settings', 'woostify_pre_order_label_position' );
			register_setting( 'pre-order-settings', 'woostify_pre_order_label_text_color' );
			register_setting( 'pre-order-settings', 'woostify_pre_order_label_background_color' );
			register_setting( 'pre-order-settings', 'woostify_pre_order_label_border_radius' );
			register_setting( 'pre-order-settings', 'woostify_pre_order_label_font_size' );
			register_setting( 'pre-order-settings', 'woostify_pre_order_messages_color' );
			register_setting( 'pre-order-settings', 'woostify_pre_order_messages_font_size' );
			register_setting( 'pre-order-settings', 'woostify_pre_order_messages_font_weight' );

			register_setting( 'countdown-urgency-settings', 'woostify_pre_order_days_label' );
			register_setting( 'countdown-urgency-settings', 'woostify_pre_order_hours_label' );
			register_setting( 'countdown-urgency-settings', 'woostify_pre_order_minutes_label' );
			register_setting( 'countdown-urgency-settings', 'woostify_pre_order_seconds_label' );
			register_setting( 'countdown-urgency-settings', 'woostify_pre_order_hide_after_time_up' );
		}

		/**
		 * Get options
		 */
		public function get_options() {
			$options                           = array();
			$options['label']                  = get_option( 'woostify_pre_order_label', 'Pre-Order' );
			$options['closed_label']           = get_option( 'woostify_pre_order_closed_label', 'Pre-Order Closed!' );
			$options['message']                = get_option( 'woostify_pre_order_message', 'Availabel for Pre-Order This item will be availabel on {preorder_date}' );
			$options['countdown']              = get_option( 'woostify_pre_order_countdown', '1' );
			$options['countdown_label']        = get_option( 'woostify_pre_order_countdown_label', 'Pre-Order Countdown' );
			$options['label_position']         = get_option( 'woostify_pre_order_label_position', 'left' );
			$options['label_text_color']       = get_option( 'woostify_pre_order_label_text_color', '#ffffff' );
			$options['label_background_color'] = get_option( 'woostify_pre_order_label_background_color', '#1346af' );
			$options['label_border_radius']    = get_option( 'woostify_pre_order_label_border_radius', '0' );
			$options['label_font_size']        = get_option( 'woostify_pre_order_label_font_size', '12' );
			$options['messages_color']         = get_option( 'woostify_pre_order_messages_color', '#000000' );
			$options['messages_font_size']     = get_option( 'woostify_pre_order_messages_font_size', '15' );
			$options['messages_font_weight']   = get_option( 'woostify_pre_order_messages_font_weight', '400' );

			$options['days_label']    = get_option( 'woostify_pre_order_days_label', __( 'DAYS', 'woostify-pro' ) );
			$options['hours_label']   = get_option( 'woostify_pre_order_hours_label', __( 'HOURS', 'woostify-pro' ) );
			$options['minutes_label'] = get_option( 'woostify_pre_order_minutes_label', __( 'MINS', 'woostify-pro' ) );
			$options['seconds_label'] = get_option( 'woostify_pre_order_seconds_label', __( 'SECS', 'woostify-pro' ) );

			return $options;
		}

		/**
		 * Get message
		 */
		public function get_message() {
			$product    = wc_get_product();
			$product_id = $product->get_id();
			$options    = $this->get_options();
			$output     = '';
			if ( empty( $options['message'] ) ) {
				return $output;
			}
			$onpreorder_date_to = get_post_meta( $product_id, '_onpreorder_date_to', true );
			$date               = gmdate( get_option( 'date_format' ), strtotime( $onpreorder_date_to ) );
			$text               = str_replace( '{preorder_date}', $date, $options['message'] );
			$output             = nl2br( $text );
			return $output;
		}

		/**
		 * Create Settings page
		 */
		public function add_settings_page() {
			$options = $this->get_options();
			?>
			<div class="woostify-options-wrap woostify-featured-setting woostify-pre-order-product-setting" data-id="pre-order" data-nonce="<?php echo esc_attr( wp_create_nonce( 'woostify-pre-order-setting-nonce' ) ); ?>">

				<?php Woostify_Admin::get_instance()->woostify_save_option_messages(); ?>

				<?php Woostify_Admin::get_instance()->woostify_welcome_screen_header(); ?>

				<div class="wrap woostify-settings-box">
					<div class="woostify-welcome-container">
						<div class="woostify-notices-wrap">
							<h2 class="notices" style="display:none;"></h2>
						</div>
						<div class="woostify-settings-content">
							<h4 class="woostify-settings-section-title"><?php esc_html_e( 'Pre Order', 'woostify-pro' ); ?></h4>

							<div class="woostify-settings-section-content woostify-settings-section-tab">
								<div class="woostify-setting-tab-head">
									<a href="#general" class="tab-head-button"><?php esc_html_e( 'General', 'woostify-pro' ); ?></a>
									<a href="#messages" class="tab-head-button"><?php esc_html_e( 'Messages', 'woostify-pro' ); ?></a>
									<a href="#style" class="tab-head-button"><?php esc_html_e( 'Style', 'woostify-pro' ); ?></a>
								</div>
								<div class="woostify-setting-tab-content-wrapper">
									<?php // General. ?>
									<table class="form-table woostify-setting-tab-content" data-tab="general">
										<tr>
											<th scope="row"><?php esc_html_e( 'Pre-Order Label', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_pre_order_label">
													<input name="woostify_pre_order_label" type="text" id="woostify_pre_order_label" value="<?php echo esc_attr( $options['label'] ); ?>">
												</label>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Pre-Order Closed', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_pre_order_closed_label">
													<input name="woostify_pre_order_closed_label" type="text" id="woostify_pre_order_closed_label" value="<?php echo esc_attr( $options['closed_label'] ); ?>">
												</label>
											</td>
										</tr>

										<tr class="woostify-filter-item">
											<th scope="row"><?php esc_html_e( 'Countdown', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_pre_order_countdown">
													<input class="woostify-filter-value" name="woostify_pre_order_countdown" type="checkbox" id="woostify_pre_order_countdown" value="<?php echo esc_attr( $options['countdown'] ); ?>"  <?php checked( $options['countdown'], '1' ); ?> >
													<?php esc_html_e( 'Display Countdown.', 'woostify-pro' ); ?>
												</label>
											</td>
										</tr>

										<tr class="woostify-filter-item <?php echo esc_attr( '1' === $options['countdown'] ? '' : 'hidden' ); ?>" data-type="0">
											<th scope="row"><?php esc_html_e( 'Countdown Label', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_pre_order_countdown_label">
													<input name="woostify_pre_order_countdown_label" type="text" id="woostify_pre_order_countdown_label" value="<?php echo esc_attr( $options['countdown_label'] ); ?>">
												</label>
											</td>
										</tr>

										<tr class="woostify-filter-item <?php echo esc_attr( '1' === $options['countdown'] ? '' : 'hidden' ); ?>" data-type="0">
											<th scope="row"><?php esc_html_e( 'Label', 'woostify-pro' ); ?>:</th>
											<td>
												<div class="pre-order-label">
													<label>
														<span><?php esc_html_e( 'Days', 'woostify-pro' ); ?>:</span>
														<input name="woostify_pre_order_days_label" type="text" placeholder="<?php esc_attr_e( 'DAYS', 'woostify-pro' ); ?>" value="<?php echo esc_attr( $options['days_label'] ); ?>">
													</label>
													<label>
														<span><?php esc_html_e( 'Hours', 'woostify-pro' ); ?>:</span>
														<input name="woostify_pre_order_hours_label" type="text" placeholder="<?php esc_attr_e( 'HOURS', 'woostify-pro' ); ?>" value="<?php echo esc_attr( $options['hours_label'] ); ?>">
													</label>
													<label>
														<span><?php esc_html_e( 'Minutes', 'woostify-pro' ); ?>:</span>
														<input name="woostify_pre_order_minutes_label" type="text" placeholder="<?php esc_attr_e( 'MINS', 'woostify-pro' ); ?>" value="<?php echo esc_attr( $options['minutes_label'] ); ?>">
													</label>
													<label>
														<span><?php esc_html_e( 'Seconds', 'woostify-pro' ); ?>:</span>
														<input name="woostify_pre_order_seconds_label" type="text" placeholder="<?php esc_attr_e( 'SECS', 'woostify-pro' ); ?>" value="<?php echo esc_attr( $options['seconds_label'] ); ?>">
													</label>
												</div>
											</td>
										</tr>
									</table>

									<?php // Messages. ?>
									<table class="form-table woostify-setting-tab-content" data-tab="messages">
										<tr>
											<th scope="row"><?php esc_html_e( 'Pre-Order Message', 'woostify-pro' ); ?>:</th>
											<td>
												<div>
													<label for="woostify_pre_order_message">
														<textarea name="woostify_pre_order_message" required="required" id="woostify_pre_order_message"><?php echo esc_attr( $options['message'] ); ?></textarea>
													</label>
												</div>
												<ul class="woostify-pre-order-message-info">
													<li><?php esc_html_e( 'Availabel for Pre-Order', 'woostify-pro' ); ?></li>
													<li><?php esc_html_e( 'This item will be availabel on {preorder_date}', 'woostify-pro' ); ?></li>
												</ul>
											</td>
										</tr>
									</table>

									<?php // Style. ?>
									<table class="form-table woostify-setting-tab-content" data-tab="style">
										<!-- Label -->
										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Label', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Position', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_pre_order_label_position">
													<option value="none" <?php selected( $options['label_position'], 'none' ); ?>><?php esc_html_e( 'None', 'woostify-pro' ); ?></option>
													<option value="left" <?php selected( $options['label_position'], 'left' ); ?>><?php esc_html_e( 'Left', 'woostify-pro' ); ?></option>
													<option value="right" <?php selected( $options['label_position'], 'right' ); ?>><?php esc_html_e( 'Right', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_pre_order_label_text_color" type="text" id="woostify_pre_order_label_text_color" value="<?php echo esc_attr( $options['label_text_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Background Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_pre_order_label_background_color" type="text" id="woostify_pre_order_label_background_color" value="<?php echo esc_attr( $options['label_background_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Border Radius', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_pre_order_label_border_radius">
													<input name="woostify_pre_order_label_border_radius" type="number" id="woostify_pre_order_label_border_radius" value="<?php echo esc_attr( $options['label_border_radius'] ); ?>">
													<code>px</code>
												</label>
												<p class="woostify-setting-description"><?php esc_html_e( 'Border Radius of Label. Unit pixel.', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Font Size', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_pre_order_label_font_size">
													<input name="woostify_pre_order_label_font_size" type="number" id="woostify_pre_order_label_font_size" value="<?php echo esc_attr( $options['label_font_size'] ); ?>">
													<code>px</code>
												</label>
												<p class="woostify-setting-description"><?php esc_html_e( 'Font Size of Label. Unit pixel.', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<!-- Messages -->
										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Messages', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_pre_order_messages_color" type="text" id="woostify_pre_order_messages_color" value="<?php echo esc_attr( $options['messages_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Font Size', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_pre_order_messages_font_size">
													<input name="woostify_pre_order_messages_font_size" type="number" id="woostify_pre_order_messages_font_size" value="<?php echo esc_attr( $options['messages_font_size'] ); ?>">
													<code>px</code>
												</label>
												<p class="woostify-setting-description"><?php esc_html_e( 'Font Size of Label. Unit pixel.', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Font Weight', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_pre_order_messages_font_weight">
													<option value="400" <?php selected( $options['messages_font_weight'], '400' ); ?>><?php esc_html_e( '400', 'woostify-pro' ); ?></option>
													<option value="500" <?php selected( $options['messages_font_weight'], '500' ); ?>><?php esc_html_e( '500', 'woostify-pro' ); ?></option>
													<option value="600" <?php selected( $options['messages_font_weight'], '600' ); ?>><?php esc_html_e( '600', 'woostify-pro' ); ?></option>
													<option value="700" <?php selected( $options['messages_font_weight'], '700' ); ?>><?php esc_html_e( '700', 'woostify-pro' ); ?></option>
												</select>
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

	}

	Woostify_Pre_Order::get_instance();
endif;
