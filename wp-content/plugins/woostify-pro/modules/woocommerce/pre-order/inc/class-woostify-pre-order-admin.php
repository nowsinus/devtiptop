<?php
/**
 * Woostify Pre-Order Admin
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_Pre_Order_Admin' ) ) {

	/**
	 * Class Woostify Pre-Order Admin
	 */
	class Woostify_Pre_Order_Admin {
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
			add_action( 'admin_init', array( $this, 'includes' ) );
			add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Include any classes we need within admin.
		 */
		public function includes() {
			require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/pre-order/inc/class-woostify-pre-order-admin-product.php';
		}

		/**
		 * Load stylesheet and scripts in edit product attribute screen
		 */
		public function enqueue_scripts() {
			// For product edit.
			$screen = get_current_screen();
			if ( ! $screen || false !== strpos( $screen->id, 'pre-order-settings' ) || false === strpos( $screen->id, 'product' ) ) {
				return;
			}

			wp_enqueue_media();

			// Date picker lib.
			wp_enqueue_style(
				'tiny-datepicker',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/product-filter/assets/css/tiny-date-picker.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);

			wp_enqueue_script(
				'tiny-datepicker',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/product-filter/assets/js/lib/tiny-date-picker' . woostify_suffix() . '.js',
				array(),
				WOOSTIFY_PRO_VERSION,
				true
			);

			$days = array(
				esc_html_x( 'Sun', 'Day of the week', 'woostify-pro' ),
				esc_html_x( 'Mon', 'Day of the week', 'woostify-pro' ),
				esc_html_x( 'Tue', 'Day of the week', 'woostify-pro' ),
				esc_html_x( 'Web', 'Day of the week', 'woostify-pro' ),
				esc_html_x( 'Thu', 'Day of the week', 'woostify-pro' ),
				esc_html_x( 'Fri', 'Day of the week', 'woostify-pro' ),
				esc_html_x( 'Sat', 'Day of the week', 'woostify-pro' ),
			);

			$months = array(
				esc_html_x( 'January', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'February', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'March', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'April', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'May', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'June', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'July', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'August', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'September', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'October', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'November', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'December', 'Month of the year', 'woostify-pro' ),
			);

			wp_localize_script(
				'tiny-datepicker',
				'woostify_datepicker_data',
				array(
					'today'  => __( 'Today', 'woostify-pro' ),
					'clear'  => __( 'Clear', 'woostify-pro' ),
					'close'  => __( 'Close', 'woostify-pro' ),
					'days'   => $days,
					'months' => $months,
				)
			);

			// General style.
			wp_enqueue_style(
				'woostify-pre-order-admin',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/pre-order/css/admin.css',
				array( 'wp-color-picker' ),
				WOOSTIFY_PRO_VERSION
			);

			// General script.
			wp_enqueue_script(
				'woostify-pre-order-admin',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/pre-order/js/admin' . woostify_suffix() . '.js',
				array( 'jquery', 'wp-color-picker', 'wp-util' ),
				WOOSTIFY_PRO_VERSION,
				true
			);

			wp_localize_script(
				'woostify-pre-order-admin',
				'woostify_pre_order_admin',
				array(
					'i18n'        => array(
						'mediaTitle'  => esc_html__( 'Choose an image', 'woostify-pro' ),
						'mediaButton' => esc_html__( 'Use image', 'woostify-pro' ),
					),
					'placeholder' => WC()->plugin_url() . '/assets/images/placeholder.png',
				)
			);
		}
	}

	Woostify_Pre_Order_Admin::get_instance();
}
