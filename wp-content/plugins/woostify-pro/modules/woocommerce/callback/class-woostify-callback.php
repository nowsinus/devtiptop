<?php
/**
 * Woostify Callback
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_CallBack' ) ) :

	/**
	 * Woostify Callback
	 */
	class Woostify_CallBack {
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
			add_action( 'wp_ajax_woostify_save_callback_options', array( $woocommerce_helper, 'save_options' ) );

			// Select data.
			add_action( 'wp_ajax_woostify_callback_select_categories', array( $woocommerce_helper, 'select_categories' ) );

			// Add Setting url.
			add_action( 'admin_menu', array( $this, 'add_setting_url' ) );
			// Register settings.
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			
			add_action( 'in_admin_header', array( $this, 'woostify_hide_all_noticee_page_setting' ) );
		}

		/**
		 * Define constant
		 */
		public function define_constants() {
			if ( ! defined( 'WOOSTIFY_PRO_CALLBACK' ) ) {
				define( 'WOOSTIFY_PRO_CALLBACK', WOOSTIFY_PRO_VERSION );
			}
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function includes() {
			require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/callback/inc/class-woostify-callback-frontend.php';
			require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/callback/inc/class-woostify-callback-helper.php';
			require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/callback/inc/class-woostify-callback-mailer.php';
			if ( is_admin() ) {
				require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/callback/inc/class-woostify-post-type-callback.php';
				require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/callback/inc/class-woostify-export-import.php';
			}
		}

		/**
		 * Add admin setting hide all notice
		 */
		public function woostify_hide_all_noticee_page_setting() {
			$screen = get_current_screen();
			if ( $screen->id == 'woostify_page_callback-settings' ) {
				remove_all_actions( 'user_admin_notices' );
				remove_all_actions( 'admin_notices' );
			}				
		}

		/**
		 * Add submenu
		 *
		 * @see  add_submenu_page()
		 */
		public function add_setting_url() {
			$text        = '<svg width="1em" height="1em" viewBox="0 0 16 16" class="woostify-admin-sub-menu-icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M1.5 1.5A.5.5 0 0 0 1 2v4.8a2.5 2.5 0 0 0 2.5 2.5h9.793l-3.347 3.346a.5.5 0 0 0 .708.708l4.2-4.2a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 8.3H3.5A1.5 1.5 0 0 1 2 6.8V2a.5.5 0 0 0-.5-.5z"/></svg>';
			$text       .= '<span class="woostify-admin-sub-menu-text">';
			$text       .= esc_html__( 'Settings', 'woostify-pro' );
			$text       .= '</span>';
			$count_posts = wp_count_posts( 'callback' )->cb_subscribed;
			$sub_menu    = add_submenu_page( 'woostify-welcome', esc_html__( 'Call back', 'woostify-pro' ), /* translators: %s: call back */ sprintf( esc_html__( 'Call back %s', 'woostify-pro' ), '<span class="update-plugins">' . esc_attr( $count_posts ) . '</span>' ), 'manage_options', 'edit.php?post_type=callback' );
			$sub_menu    = add_submenu_page( 'woostify-welcome', esc_html__( 'Settings', 'woostify-pro' ), $text, 'manage_options', 'callback-settings', array( $this, 'add_settings_page' ) );
			
			global $submenu;
			if ( $submenu['woostify-welcome'] ) {
				foreach ( $submenu['woostify-welcome'] as $key => $value ) {
					if ( $value[2] == 'edit.php?post_type=callback' ) {
						$submenu['woostify-welcome'][$key][4] = "callback setting";
					}
				}
			}
		}

		/**
		 * Register settings
		 */
		public function register_settings() {
			register_setting( 'callback-settings', 'woostify_callback_form_display_type' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_title' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_ph_name' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_ph_email' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_ph_phone' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_label' );
			register_setting( 'callback-settings', 'woostify_callback_sitekeyrecaptcha' );
			register_setting( 'callback-settings', 'woostify_callback_secretkeyrecaptcha' );
			register_setting( 'callback-settings', 'woostify_callback_hide_name' );
			register_setting( 'callback-settings', 'woostify_callback_show_phone' );
			register_setting( 'callback-settings', 'woostify_callback_phone_field_optional' );
			register_setting( 'callback-settings', 'woostify_callback_show_agree' );
			register_setting( 'callback-settings', 'woostify_callback_show_agree_text' );

			// Messages.
			register_setting( 'callback-settings', 'woostify_callback_form_sc_m_yourname' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_m_yourmail' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_m_notemail' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_m_already_sub' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_m_yourphone' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_m_success' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_m_enable_recaptcha' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_m_recaptcha' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_m_validaterecaptcha' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_m_agree' );

			// Style.
			register_setting( 'callback-settings', 'woostify_callback_form_sc_border_radius' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_border_color' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_title_color' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_title_bg_color' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_title_font_size' );
			register_setting( 'callback-settings', 'woostify_callback_form_sc_title_font_weight' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_color' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_color_hover' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_bg_color' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_bg_color_hover' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_border_color' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_border_color_hover' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_width' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_height' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_pu_color' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_pu_color_hover' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_pu_bg_color' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_pu_bg_color_hover' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_pu_border_color' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_pu_border_color_hover' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_pu_width' );
			register_setting( 'callback-settings', 'woostify_callback_form_btn_pu_height' );

			// Source.
			register_setting( 'callback-settings', 'woostify_callback_option_query' );

			// Mail.
			register_setting( 'callback-settings', 'woostify_callback_mail_subject' );
			register_setting( 'callback-settings', 'woostify_callback_mail_message' );
			register_setting( 'callback-settings', 'woostify_callback_mail_subject_instock' );
			register_setting( 'callback-settings', 'woostify_callback_mail_message_instock' );
			register_setting( 'callback-settings', 'woostify_callback_subsciption_mail_send' );
			register_setting( 'callback-settings', 'woostify_callback_instock_mail_send' );
		}

		/**
		 * Get options
		 */
		public function get_options() {
			$options                         = array();
			$options['form_display_type']    = get_option( 'woostify_callback_form_display_type', '1' );
			$options['form_sc_title']        = get_option( 'woostify_callback_form_sc_title', 'Notify me when product is available' );
			$options['form_sc_ph_name']      = get_option( 'woostify_callback_form_sc_ph_name', 'Your Name' );
			$options['form_sc_ph_email']     = get_option( 'woostify_callback_form_sc_ph_email', 'Your Email Address' );
			$options['form_sc_ph_phone']     = get_option( 'woostify_callback_form_sc_ph_phone', 'Your Phone' );
			$options['form_btn_label']       = get_option( 'woostify_callback_form_btn_label', 'Subscribe Now' );
			$options['sitekeyrecaptcha']     = get_option( 'woostify_callback_sitekeyrecaptcha', '' );
			$options['secretkeyrecaptcha']   = get_option( 'woostify_callback_secretkeyrecaptcha', '' );
			$options['hide_name']            = get_option( 'woostify_callback_hide_name', '0' );
			$options['show_phone']           = get_option( 'woostify_callback_show_phone', '0' );
			$options['phone_field_optional'] = get_option( 'woostify_callback_phone_field_optional', '0' );
			$options['show_agree']           = get_option( 'woostify_callback_show_agree', '0' );
			$options['show_agree_text']      = get_option( 'woostify_callback_show_agree_text', 'I Agree to the {a href="#" target="_blank"}terms{/a} and {a href="#" target="_blank"}privacy policy{/a}' );

			// Messages.
			$options['form_sc_m_yourname']          = get_option( 'woostify_callback_form_sc_m_yourname', 'Enter your name' );
			$options['form_sc_m_yourmail']          = get_option( 'woostify_callback_form_sc_m_yourmail', 'Enter your email' );
			$options['form_sc_m_notemail']          = get_option( 'woostify_callback_form_sc_m_notemail', 'You entered is not email' );
			$options['form_sc_m_already_sub']       = get_option( 'woostify_callback_form_sc_m_already_sub', 'Seems like you have already subscribed to this product' );
			$options['form_sc_m_yourphone']         = get_option( 'woostify_callback_form_sc_m_yourphone', 'Enter your phone' );
			$options['form_sc_m_success']           = get_option( 'woostify_callback_form_sc_m_success', 'You have successfully subscribed, we will inform you when this product back in stock' );
			$options['form_sc_m_recaptcha']         = get_option( 'woostify_callback_form_sc_m_recaptcha', 'You can\'t leave Captcha Code empty' );
			$options['form_sc_m_validaterecaptcha'] = get_option( 'woostify_callback_form_sc_m_validaterecaptcha', 'Site key or Secret key for reCAPTCHA Invalid' );
			$options['form_sc_m_enable_recaptcha']  = get_option( 'woostify_callback_form_sc_m_enable_recaptcha', '0' );
			$options['form_sc_m_agree']             = get_option( 'woostify_callback_form_sc_m_agree', 'Please accept our terms and privacy policy' );

			// Style.
			$options['form_sc_border_radius']          = get_option( 'woostify_callback_form_sc_border_radius', '3' );
			$options['form_sc_border_color']           = get_option( 'woostify_callback_form_sc_border_color', '#000000' );
			$options['form_sc_title_color']            = get_option( 'woostify_callback_form_sc_title_color', '#ffffff' );
			$options['form_sc_title_bg_color']         = get_option( 'woostify_callback_form_sc_title_bg_color', '#000000' );
			$options['form_sc_title_font_size']        = get_option( 'woostify_callback_form_sc_title_font_size', '20' );
			$options['form_sc_title_font_weight']      = get_option( 'woostify_callback_form_sc_title_font_weight', '600' );
			$options['form_btn_color']                 = get_option( 'woostify_callback_form_btn_color', '' );
			$options['form_btn_color_hover']           = get_option( 'woostify_callback_form_btn_color_hover', '' );
			$options['form_btn_bg_color']              = get_option( 'woostify_callback_form_btn_bg_color', '' );
			$options['form_btn_bg_color_hover']        = get_option( 'woostify_callback_form_btn_bg_color_hover', '' );
			$options['form_btn_border_color']          = get_option( 'woostify_callback_form_btn_border_color', '' );
			$options['form_btn_border_color_hover']    = get_option( 'woostify_callback_form_btn_border_color_hover', '' );
			$options['form_btn_width']                 = get_option( 'woostify_callback_form_btn_width', '' );
			$options['form_btn_height']                = get_option( 'woostify_callback_form_btn_height', '' );
			$options['form_btn_pu_color']              = get_option( 'woostify_callback_form_btn_pu_color', '' );
			$options['form_btn_pu_color_hover']        = get_option( 'woostify_callback_form_btn_pu_color_hover', '' );
			$options['form_btn_pu_bg_color']           = get_option( 'woostify_callback_form_btn_pu_bg_color', '' );
			$options['form_btn_pu_bg_color_hover']     = get_option( 'woostify_callback_form_btn_pu_bg_color_hover', '' );
			$options['form_btn_pu_border_color']       = get_option( 'woostify_callback_form_btn_pu_border_color', '' );
			$options['form_btn_pu_border_color_hover'] = get_option( 'woostify_callback_form_btn_pu_border_color_hover', '' );
			$options['form_btn_pu_width']              = get_option( 'woostify_callback_form_btn_pu_width', '' );
			$options['form_btn_pu_height']             = get_option( 'woostify_callback_form_btn_pu_height', '' );

			// Source.
			$options['woostify_callback_query'] = get_option( 'woostify_callback_option_query', 'out-of-stock-products' );
			$options['selected_categories']     = get_option( 'woostify_callback_categories_selected', '' );

			// Mail.
			$options['callback_mail_subject']         = get_option( 'woostify_callback_mail_subject', 'You subscribed to {product_name} at {shopname}' );
			$options['callback_mail_message']         = get_option( 'woostify_callback_mail_message', 'Dear {subscriber_name}, <br />Thank you for subscribing to the #{product_name}. We will email you once product back in stock' );
			$options['callback_mail_subject_instock'] = get_option( 'woostify_callback_mail_subject_instock', 'Product {product_name} has back in stock' );
			$options['callback_mail_message_instock'] = get_option( 'woostify_callback_mail_message_instock', 'Hello {subscriber_name}, <br />Thanks for your patience and finally the wait is over! <br /> Your Subscribed Product {product_name} is now back in stock! We only have a limited amount of stock, and this email is not a guarantee you\'ll get one, so hurry to be one of the lucky shoppers who do <br /> Add this product {product_name} directly to your cart <a href="{cart_link}">{cart_link}</a>' );
			$options['subsciption_mail_send']         = get_option( 'woostify_callback_subsciption_mail_send', '1' );
			$options['instock_mail_send']             = get_option( 'woostify_callback_instock_mail_send', '1' );

			return $options;
		}

		/**
		 * Create Settings page
		 */
		public function add_settings_page() {
			$options            = $this->get_options();
			$count_posts        = wp_count_posts( 'callback' )->cb_subscribed;
			$woocommerce_helper = Woostify_Woocommerce_Helper::init();
			?>
			<div class="woostify-options-wrap woostify-featured-setting woostify-callback-product-setting" data-id="callback" data-nonce="<?php echo esc_attr( wp_create_nonce( 'woostify-callback-setting-nonce' ) ); ?>">

				<?php Woostify_Admin::get_instance()->woostify_save_option_messages(); ?>

				<?php Woostify_Admin::get_instance()->woostify_welcome_screen_header(); ?>

				<div class="wrap woostify-settings-box">
					<div class="woostify-welcome-container">
						<div class="woostify-notices-wrap">
							<h2 class="notices" style="display:none;"></h2>
						</div>
						<div class="woostify-settings-content">
							<h4 class="woostify-settings-section-title">
								<?php esc_html_e( 'Callback', 'woostify-pro' ); ?>
								<a class="woostify-settings-section-callback-link" href="<?php echo esc_url( get_admin_url() . 'edit.php?post_type=callback' ); ?>">									
									<?php
									if ( $count_posts < 2 ) {
										/* translators: %s: view subscriber */
										printf( esc_html__( 'View Subscriber (%s)', 'woostify-pro' ), esc_attr( $count_posts ) );
									} else {
										/* translators: %s: view subscribers */
										printf( esc_html__( 'View Subscribers (%s)', 'woostify-pro' ), esc_attr( $count_posts ) );
									}
									?>
								</a>
							</h4>

							<div class="woostify-settings-section-content woostify-settings-section-tab">
								<div class="woostify-setting-tab-head">
									<a href="#general" class="tab-head-button"><?php esc_html_e( 'Frontend Form', 'woostify-pro' ); ?></a>
									<a href="#messages" class="tab-head-button"><?php esc_html_e( 'Messages', 'woostify-pro' ); ?></a>
									<a href="#style" class="tab-head-button"><?php esc_html_e( 'Style', 'woostify-pro' ); ?></a>
									<a href="#products" class="tab-head-button"><?php esc_html_e( 'Products', 'woostify-pro' ); ?></a>
									<a href="#mail" class="tab-head-button"><?php esc_html_e( 'Mail', 'woostify-pro' ); ?></a>
								</div>
								<div class="woostify-setting-tab-content-wrapper">
									<table class="form-table woostify-setting-tab-content" data-tab="general">
										<tr>
											<th scope="row"><?php esc_html_e( 'Enable I Agree in Subscribe Form:', 'woostify-pro' ); ?></th>
											<td>
												<label for="woostify_callback_show_agree">
													<input name="woostify_callback_show_agree" type="checkbox" id="woostify_callback_show_agree" <?php checked( $options['show_agree'], '1' ); ?> value="<?php echo esc_attr( $options['show_agree'] ); ?>">
													<?php esc_html_e( 'Select this option to enable I Agree Checkbox in Subscribe Form(Ex: I Agree to the terms and privacy policy)', 'woostify-pro' ); ?>
												</label>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Text for I Agree - this will appear next to the checkbox frontend:', 'woostify-pro' ); ?></th>
											<td>
												<label for="woostify_callback_show_agree_text">
													<textarea name="woostify_callback_show_agree_text" id="woostify_callback_show_agree_text" cols="50" rows="5"><?php echo esc_html( $options['show_agree_text'] ); ?></textarea>
												</label>
												<p class="woostify-setting-description"><?php esc_html_e( 'Tag embedding structure instead of "<" enter "{" and ">" enter "}" ex: {a href="#" target="_blank"} text {/a}', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Hide Name', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_hide_name">
													<input name="woostify_callback_hide_name" type="checkbox" id="woostify_callback_hide_name" <?php checked( $options['hide_name'], '1' ); ?> value="<?php echo esc_attr( $options['hide_name'] ); ?>">
													<?php esc_html_e( 'Hide name field in Subscribe Form', 'woostify-pro' ); ?>
												</label>
											</td>
										</tr>

										<tr class="woostify-filter-item">
											<th scope="row"><?php esc_html_e( 'Show Phone', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_show_phone">
													<input class="woostify-filter-value" name="woostify_callback_show_phone" type="checkbox" id="woostify_callback_show_phone" <?php checked( $options['show_phone'], '1' ); ?> value="<?php echo esc_attr( $options['show_phone'] ); ?>">
													<?php esc_html_e( 'Show phone field in Subscribe Form', 'woostify-pro' ); ?>
												</label>
											</td>
										</tr>

										<tr class="woostify-filter-item <?php echo '1' === $options['show_phone'] ? '' : 'hidden'; ?>" data-type="0">
											<th scope="row"><?php esc_html_e( 'Phone field optional', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_phone_field_optional">
													<input name="woostify_callback_phone_field_optional" type="checkbox" id="woostify_callback_phone_field_optional" <?php checked( $options['phone_field_optional'], '1' ); ?> value="<?php echo esc_attr( $options['phone_field_optional'] ); ?>">
													<?php esc_html_e( 'Enable this option to make phone field as optional', 'woostify-pro' ); ?>
												</label>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Subscribe Form Display Type', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_callback_form_display_type">
													<option value="1" <?php selected( $options['form_display_type'], '1' ); ?>><?php esc_html_e( 'Inline Subscribe Form', 'woostify-pro' ); ?></option>
													<option value="2" <?php selected( $options['form_display_type'], '2' ); ?>><?php esc_html_e( 'Pop-Up Subscribe Form', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Title for Subscribe Form', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_form_sc_title">
													<input name="woostify_callback_form_sc_title" type="text" id="woostify_callback_form_sc_title" value="<?php echo esc_attr( $options['form_sc_title'] ); ?>">
												</label>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Placeholder for Name Field', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_form_sc_ph_name">
													<input name="woostify_callback_form_sc_ph_name" type="text" id="woostify_callback_form_sc_ph_name" value="<?php echo esc_attr( $options['form_sc_ph_name'] ); ?>">
												</label>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Placeholder for Email Field', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_form_sc_ph_email">
													<input name="woostify_callback_form_sc_ph_email" type="text" id="woostify_callback_form_sc_ph_email" value="<?php echo esc_attr( $options['form_sc_ph_email'] ); ?>">
												</label>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Placeholder for Phone Field', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_form_sc_ph_phone">
													<input name="woostify_callback_form_sc_ph_phone" type="text" id="woostify_callback_form_sc_ph_phone" value="<?php echo esc_attr( $options['form_sc_ph_phone'] ); ?>">
												</label>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Button Label', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_form_btn_label">
													<input name="woostify_callback_form_btn_label" type="text" id="woostify_callback_form_btn_label" value="<?php echo esc_attr( $options['form_btn_label'] ); ?>">
												</label>
											</td>
										</tr>

										<tr class="woostify-filter-item">
											<th scope="row"><?php esc_html_e( 'Enable reCAPTCHA', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_form_sc_m_enable_recaptcha">
													<input class="woostify-filter-value" name="woostify_callback_form_sc_m_enable_recaptcha" type="checkbox" id="woostify_callback_form_sc_m_enable_recaptcha" <?php checked( $options['form_sc_m_enable_recaptcha'], '1' ); ?> value="<?php echo esc_attr( $options['form_sc_m_enable_recaptcha'] ); ?>">
												</label>
											</td>
										</tr>

										<tr class="woostify-filter-item <?php echo '1' === $options['form_sc_m_enable_recaptcha'] ? '' : 'hidden'; ?>" data-type="0">
											<th scope="row"><?php esc_html_e( 'Your Site Key', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_sitekeyrecaptcha">
													<input name="woostify_callback_sitekeyrecaptcha" type="text" id="woostify_callback_sitekeyrecaptcha" value="<?php echo esc_attr( $options['sitekeyrecaptcha'] ); ?>">
												</label>
											</td>
										</tr>

										<tr class="woostify-filter-item <?php echo '1' === $options['form_sc_m_enable_recaptcha'] ? '' : 'hidden'; ?>" data-type="0">
											<th scope="row"><?php esc_html_e( 'Your Secret Key', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_secretkeyrecaptcha">
													<input name="woostify_callback_secretkeyrecaptcha" type="text" id="woostify_callback_secretkeyrecaptcha" value="<?php echo esc_attr( $options['secretkeyrecaptcha'] ); ?>">
												</label>
												<p class="woostify-setting-description"><a href="https://www.google.com/recaptcha/about/" target="_blank"><?php esc_html_e( 'Link create site key and secret key reCAPTCHA ', 'woostify-pro' ); ?></a></p>
											</td>
										</tr>
									</table>

									<?php // Messages. ?>
									<table class="form-table woostify-setting-tab-content" data-tab="messages">
										<tr>
											<th scope="row"><?php esc_html_e( 'I Agree Error Message', 'woostify-pro' ); ?>:</th>
											<td>												
												<textarea rows="1" name="woostify_callback_form_sc_m_agree" required="required"><?php echo esc_html( $options['form_sc_m_agree'] ); ?></textarea>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Messages validate field name', 'woostify-pro' ); ?>:</th>
											<td>												
												<textarea rows="1" name="woostify_callback_form_sc_m_yourname" required="required"><?php echo esc_html( $options['form_sc_m_yourname'] ); ?></textarea>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Messages validate field email', 'woostify-pro' ); ?>:</th>
											<td>												
												<textarea rows="1" name="woostify_callback_form_sc_m_yourmail" required="required"><?php echo esc_html( $options['form_sc_m_yourmail'] ); ?></textarea>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Messages validate is not email', 'woostify-pro' ); ?>:</th>
											<td>												
												<textarea rows="1" name="woostify_callback_form_sc_m_notemail" required="required"><?php echo esc_html( $options['form_sc_m_notemail'] ); ?></textarea>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Messages validate already subscribed', 'woostify-pro' ); ?>:</th>
											<td>												
												<textarea rows="1" name="woostify_callback_form_sc_m_already_sub" required="required"><?php echo esc_html( $options['form_sc_m_already_sub'] ); ?></textarea>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Messages validate field phone', 'woostify-pro' ); ?>:</th>
											<td>												
												<textarea rows="1" name="woostify_callback_form_sc_m_yourphone" required="required"><?php echo esc_html( $options['form_sc_m_yourphone'] ); ?></textarea>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Messages success', 'woostify-pro' ); ?>:</th>
											<td>												
												<textarea rows="1" name="woostify_callback_form_sc_m_success" required="required"><?php echo esc_html( $options['form_sc_m_success'] ); ?></textarea>
											</td>
										</tr>

										<!-- reCAPTCHA -->
										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'reCAPTCHA', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Messages reCAPTCHA', 'woostify-pro' ); ?>:</th>
											<td>												
												<textarea rows="1" name="woostify_callback_form_sc_m_recaptcha" required="required"><?php echo esc_html( $options['form_sc_m_recaptcha'] ); ?></textarea>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Messages Validate reCAPTCHA', 'woostify-pro' ); ?>:</th>
											<td>												
												<textarea rows="1" name="woostify_callback_form_sc_m_validaterecaptcha" required="required"><?php echo esc_html( $options['form_sc_m_validaterecaptcha'] ); ?></textarea>
											</td>
										</tr>
									</table>

									<?php // Style. ?>
									<table class="form-table woostify-setting-tab-content" data-tab="style">
										<!-- Form -->
										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Form', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Border Radius', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_form_sc_border_radius">
													<input name="woostify_callback_form_sc_border_radius" type="number" id="woostify_callback_form_sc_border_radius" value="<?php echo esc_attr( $options['form_sc_border_radius'] ); ?>">
													<code>px</code>
												</label>
												<p class="woostify-setting-description"><?php esc_html_e( 'Border Radius of Label. Unit pixel.', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Border Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="#000000" name="woostify_callback_form_sc_border_color" type="text" id="woostify_callback_form_sc_border_color" value="<?php echo esc_attr( $options['form_sc_border_color'] ); ?>">
											</td>
										</tr>

										<!-- Form Title -->
										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Form Title', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="#ffffff" name="woostify_callback_form_sc_title_color" type="text" id="woostify_callback_form_sc_title_color" value="<?php echo esc_attr( $options['form_sc_title_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Background Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="#000000" name="woostify_callback_form_sc_title_bg_color" type="text" id="woostify_callback_form_sc_title_bg_color" value="<?php echo esc_attr( $options['form_sc_title_bg_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Font Size', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_form_sc_title_font_size">
													<input name="woostify_callback_form_sc_title_font_size" type="number" id="woostify_callback_form_sc_title_font_size" value="<?php echo esc_attr( $options['form_sc_title_font_size'] ); ?>">
													<code>px</code>
												</label>
												<p class="woostify-setting-description"><?php esc_html_e( 'Font Size of Label. Unit pixel.', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Font Weight', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_callback_form_sc_title_font_weight">
													<option value="400" <?php selected( $options['form_sc_title_font_weight'], '400' ); ?>><?php esc_html_e( '400', 'woostify-pro' ); ?></option>
													<option value="500" <?php selected( $options['form_sc_title_font_weight'], '500' ); ?>><?php esc_html_e( '500', 'woostify-pro' ); ?></option>
													<option value="600" <?php selected( $options['form_sc_title_font_weight'], '600' ); ?>><?php esc_html_e( '600', 'woostify-pro' ); ?></option>
													<option value="700" <?php selected( $options['form_sc_title_font_weight'], '700' ); ?>><?php esc_html_e( '700', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>

										<!-- Form Button -->
										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Form Button Submit', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="" name="woostify_callback_form_btn_color" type="text" id="woostify_callback_form_btn_color" value="<?php echo esc_attr( $options['form_btn_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Color Hover', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="" name="woostify_callback_form_btn_color_hover" type="text" id="woostify_callback_form_btn_color_hover" value="<?php echo esc_attr( $options['form_btn_color_hover'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Background Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="" name="woostify_callback_form_btn_bg_color" type="text" id="woostify_callback_form_btn_bg_color" value="<?php echo esc_attr( $options['form_btn_bg_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Background Color Hover', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="" name="woostify_callback_form_btn_bg_color_hover" type="text" id="woostify_callback_form_btn_bg_color_hover" value="<?php echo esc_attr( $options['form_btn_bg_color_hover'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Border Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="" name="woostify_callback_form_btn_border_color" type="text" id="woostify_callback_form_btn_border_color" value="<?php echo esc_attr( $options['form_btn_border_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Border Color Hover', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="" name="woostify_callback_form_btn_border_color_hover" type="text" id="woostify_callback_form_btn_border_color_hover" value="<?php echo esc_attr( $options['form_btn_border_color_hover'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Width', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_form_btn_width">
													<input name="woostify_callback_form_btn_width" type="text" id="woostify_callback_form_btn_width" value="<?php echo esc_attr( $options['form_btn_width'] ); ?>">
												</label>
												<p class="woostify-setting-description"><?php esc_html_e( 'Width of Button. ex: 100% or 300px', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Height', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_form_btn_height">
													<input name="woostify_callback_form_btn_height" type="number" id="woostify_callback_form_btn_height" value="<?php echo esc_attr( $options['form_btn_height'] ); ?>">
													<code>px</code>
												</label>
												<p class="woostify-setting-description"><?php esc_html_e( 'Height of Button. Unit pixel.', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<!-- Form Button Popup -->
										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Button Popup Form', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="" name="woostify_callback_form_btn_pu_color" type="text" id="woostify_callback_form_btn_pu_color" value="<?php echo esc_attr( $options['form_btn_pu_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Color Hover', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="" name="woostify_callback_form_btn_pu_color_hover" type="text" id="woostify_callback_form_btn_pu_color_hover" value="<?php echo esc_attr( $options['form_btn_pu_color_hover'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Background Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="" name="woostify_callback_form_btn_pu_bg_color" type="text" id="woostify_callback_form_btn_pu_bg_color" value="<?php echo esc_attr( $options['form_btn_pu_bg_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Background Color Hover', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="" name="woostify_callback_form_btn_pu_bg_color_hover" type="text" id="woostify_callback_form_btn_pu_bg_color_hover" value="<?php echo esc_attr( $options['form_btn_pu_bg_color_hover'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Border Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="" name="woostify_callback_form_btn_pu_border_color" type="text" id="woostify_callback_form_btn_pu_border_color" value="<?php echo esc_attr( $options['form_btn_pu_border_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Border Color Hover', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker default" data-colordefault="" name="woostify_callback_form_btn_pu_border_color_hover" type="text" id="woostify_callback_form_btn_pu_border_color_hover" value="<?php echo esc_attr( $options['form_btn_pu_border_color_hover'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Width', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_form_btn_pu_width">
													<input name="woostify_callback_form_btn_pu_width" type="text" id="woostify_callback_form_btn_pu_width" value="<?php echo esc_attr( $options['form_btn_pu_width'] ); ?>">
												</label>
												<p class="woostify-setting-description"><?php esc_html_e( 'Width of Button. ex: 100% or 300px', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Height', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_form_btn_pu_height">
													<input name="woostify_callback_form_btn_pu_height" type="number" id="woostify_callback_form_btn_pu_height" value="<?php echo esc_attr( $options['form_btn_pu_height'] ); ?>">
													<code>px</code>
												</label>
												<p class="woostify-setting-description"><?php esc_html_e( 'Height of Button. Unit pixel.', 'woostify-pro' ); ?></p>
											</td>
										</tr>
									</table>

									<?php // Products. ?>
									<table class="form-table woostify-setting-tab-content" data-tab="products">
										<tr class="woostify-filter-item">
											<th scope="row"><?php esc_html_e( 'Source', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_callback_option_query" class="woostify-filter-value">
													<option value="all-products" <?php selected( $options['woostify_callback_query'], 'all-products' ); ?>><?php esc_html_e( 'All products', 'woostify-pro' ); ?></option>
													<option value="out-of-stock-products" <?php selected( $options['woostify_callback_query'], 'out-of-stock-products' ); ?>><?php esc_html_e( 'Out of stock products', 'woostify-pro' ); ?></option>
													<option value="select-categories" <?php selected( $options['woostify_callback_query'], 'select-categories' ); ?>><?php esc_html_e( 'Select Categories', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>

										<tr class="woostify-filter-item <?php echo 'select-categories' === $options['woostify_callback_query'] ? '' : 'hidden'; ?>" data-type="select-categories">
											<th scope="row"><?php esc_html_e( 'Select Categories', 'woostify-pro' ); ?>:</th>
											<td>
												<div class="woostify-multi-selection">
													<input class="woostify-multi-select-value" name="woostify_callback_categories_selected" type="hidden" value="<?php echo esc_attr( $options['selected_categories'] ); ?>">

													<div class="woostify-multi-select-selection">
														<div class="woostify-multi-selection-inner">
															<?php $woocommerce_helper->render_selection( $options['selected_categories'] ); ?>
														</div>

														<input type="text" class="woostify-multi-select-search" placeholder="<?php esc_attr_e( 'Please enter 1 or more characters', 'woostify-pro' ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'woostify-select-categories' ) ); ?>" name="woostify_callback_select_categories">
													</div>

													<div class="woostify-multi-select-dropdown"></div>
												</div>

												<p class="woostify-setting-description"><?php esc_html_e( 'Type \'all\' to select all categories.', 'woostify-pro' ); ?></p>
											</td>
										</tr>
									</table>

									<?php // Mail. ?>
									<table class="form-table woostify-setting-tab-content" data-tab="mail">
										<tr>
											<th scope="row"><?php esc_html_e( 'All Shortcodes', 'woostify-pro' ); ?></th>
											<td>												
												<p class="woostify-setting-description"><?php esc_html_e( '{product_name} , {product_id} , {product_link} , {shopname} , {subscriber_email} , {subscriber_name} , {subscriber_phone} , {cart_link}', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Subscription Mail Send', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Subscription Mail Subject', 'woostify-pro' ); ?>:</th>
											<td>												
												<textarea rows="1" name="woostify_callback_mail_subject" required="required"><?php echo esc_html( $options['callback_mail_subject'] ); ?></textarea>
												<p class="woostify-setting-description"><?php esc_html_e( '{product_name} , {shopname}', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Subscription Mail Message', 'woostify-pro' ); ?>:</th>
											<td>												
												<textarea rows="4" name="woostify_callback_mail_message" required="required"><?php echo esc_html( $options['callback_mail_message'] ); ?></textarea>
												<p class="woostify-setting-description"><?php esc_html_e( '{subscriber_name} , {product_name}', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Subsciption Mail Send', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_subsciption_mail_send">
													<input class="woostify-filter-value" name="woostify_callback_subsciption_mail_send" type="checkbox" id="woostify_callback_subsciption_mail_send" <?php checked( $options['subsciption_mail_send'], '1' ); ?> value="<?php echo esc_attr( $options['subsciption_mail_send'] ); ?>">
												</label>
											</td>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Instock Mail Send', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Instock Mail Subject', 'woostify-pro' ); ?>:</th>
											<td>												
												<textarea rows="1" name="woostify_callback_mail_subject_instock" required="required"><?php echo esc_html( $options['callback_mail_subject_instock'] ); ?></textarea>
												<p class="woostify-setting-description"><?php esc_html_e( '{product_name}', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Instock Mail Message', 'woostify-pro' ); ?>:</th>
											<td>												
												<textarea rows="4" name="woostify_callback_mail_message_instock" required="required"><?php echo esc_html( $options['callback_mail_message_instock'] ); ?></textarea>
												<p class="woostify-setting-description"><?php esc_html_e( '{subscriber_name} , {product_name} , {cart_link}', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Instock Mail Send', 'woostify-pro' ); ?>:</th>
											<td>
												<label for="woostify_callback_instock_mail_send">
													<input class="woostify-filter-value" name="woostify_callback_instock_mail_send" type="checkbox" id="woostify_callback_instock_mail_send" <?php checked( $options['instock_mail_send'], '1' ); ?> value="<?php echo esc_attr( $options['instock_mail_send'] ); ?>">
												</label>
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
		 * Gets the product ids.
		 *
		 * @return     array The product ids.
		 */
		public function get_product_ids() {
			$options  = $this->get_options();
			$is_empty = false;
			$args     = array(
				'post_type'      => array( 'product', 'product_variation' ),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);

			switch ( $options['woostify_callback_query'] ) {
				case 'all-products':
				default:
					$args['order']   = 'DESC';
					$args['orderby'] = 'date';
					break;
				case 'out-of-stock-products':
					$args['meta_query'] = array( // phpcs:ignore
						array(
							'key'   => '_stock_status',
							'value' => 'outofstock',
						),
					);
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
			}

			return get_posts( $args );
		}

	}

	Woostify_CallBack::get_instance();
endif;
