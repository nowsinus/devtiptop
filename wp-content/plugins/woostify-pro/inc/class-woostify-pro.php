<?php
/**
 * Main Woostify Class
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_Pro' ) ) {
	/**
	 * Main Woostify Pro Class
	 */
	class Woostify_Pro {

		/**
		 * Instance
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
		 * Woostify Pro Constructor.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'setup' ) );
			add_action( 'admin_init', array( $this, 'woostify_pro_updater' ), 0 );
			add_action( 'after_setup_theme', array( $this, 'module_list' ) );
			add_action( 'admin_menu', array( $this, 'add_new_admin_menu' ), 5 );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'plugin_action_links_' . WOOSTIFY_PRO_PLUGIN_BASE, array( $this, 'action_links' ) );
			add_action( 'customize_register', array( $this, 'register_customizer' ), 99 );
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_static' ), 100 );
			add_filter( 'woostify_options_admin_menu', '__return_true' );
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'customizer_control_scripts' ) );
			add_action( 'customize_preview_init', array( $this, 'customizer_preview_scripts' ), 11 );
			add_action( 'woostify_pro_panel_column', array( $this, 'woostify_extract_modules' ), 5 );
			add_action( 'woostify_pro_panel_addons', array( $this, 'woostify_extract_modules_addons' ) );
			add_action( 'woostify_pro_panel_changelog', array( $this, 'woostify_extract_modules_changelog' ) );
			add_action( 'wp_ajax_woostify_pro_check_licenses', array( $this, 'woostify_process_license_key' ) );
			add_action( 'wp_ajax_module_action', array( $this, 'woostify_ajax_module_action' ) );
			add_action( 'wp_ajax_module_action_all', array( $this, 'woostify_ajax_module_action_all' ) );
			add_action( 'wp_ajax_all_feature_activated', array( $this, 'woostify_ajax_all_feature_activated' ) );
			add_action( 'wp_ajax_changelog_pagination', array( $this, 'woostify_ajax_changelog_pagination' ) );
			add_action( 'woostify_pro_panel_sidebar', array( $this, 'woostify_activation_section' ), 5 );
			add_action( 'admin_notices', array( $this, 'woostify_pro_print_notices' ) );
			do_action( 'woostify_pro_loaded' );
		}

		/**
		 * Woostify Pro Packages.
		 */
		public function woostify_pro_packages() {
			$names = array( 'Woostify Pro – Lifetime', 'Woostify Pro – Professional', 'Woostify Pro – Agency', 'Woostify Pro – Personal', 'Woostify Pro - AppSumo lifetime deal', 'Woostify Pro – Stack 1 AppSumo LTD', 'Woostify Pro – Stack 2 AppSumo LTD' );
			return $names;
		}

		/**
		 * Sets up.
		 */
		public function setup() {
			if ( ! defined( 'WOOSTIFY_VERSION' ) ) {
				return;
			}

			// Woostify helper functions.
			require_once WOOSTIFY_PRO_PATH . 'inc/woostify-pro-functions.php';

			// Remove when start supporting WP 5.0 or later.
			$locale = function_exists( 'determine_locale' ) ? determine_locale() : ( is_admin() ? get_user_locale() : get_locale() );
			$locale = apply_filters( 'woostify_pro_plugin_locale', $locale, 'woostify-pro' );

			// Load text-domain.
			load_textdomain( 'woostify-pro', WP_LANG_DIR . '/woostify-pro/woostify-pro-' . $locale . '.mo' );
			load_textdomain( 'woostify-pro', WOOSTIFY_PRO_PATH . 'languages/woostify-pro-' . $locale . '.mo' );
			load_plugin_textdomain( 'woostify-pro', false, WOOSTIFY_PRO_PATH . 'languages/' );

			// Sticky module.
			$this->load_sticky_module();
		}

		/**
		 * Load sticky module
		 */
		public function load_sticky_module() {
			if ( defined( 'WOOSTIFY_PRO_HEADER_FOOTER_BUILDER' ) ) {
				require_once WOOSTIFY_PRO_MODULES_PATH . 'sticky/class-woostify-sticky.php';
				$elementor = \Elementor\Plugin::$instance;

				/* Add element category in panel */
				$elementor->elements_manager->add_category(
					'woostify-sticky',
					array(
						'title' => __( 'Sticky', 'woostify-pro' ),
						'icon'  => 'font',
					),
					1
				);

                do_action('elementor_controls/init'); // phpcs:ignore
			}
		}

		/**
		 * Add new admin menu
		 */
		public function add_new_admin_menu() {
			if ( ! defined( 'WOOSTIFY_VERSION' ) ) {
				return;
			}

			$woostify_admin = Woostify_Admin::get_instance();
			$page           = add_menu_page( 'Woostify Theme Options', __( 'Woostify', 'woostify-pro' ), 'manage_options', 'woostify-welcome', array( $woostify_admin, 'woostify_welcome_screen' ), 'none', 60 );

			add_submenu_page( 'woostify-welcome', 'Woostify Theme Options', __( 'Dashboard', 'woostify-pro' ), 'manage_options', 'woostify-welcome' );
			add_action( 'admin_print_styles-' . $page, array( $woostify_admin, 'woostify_welcome_static' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'woostify_pro_dashboard_static' ) );
		}

		/**
		 * Show action links on the plugin screen.
		 *
		 * @param array $links The links.
		 *
		 * @return     array
		 */
		public function action_links( $links = array() ) {
			if ( ! defined( 'WOOSTIFY_VERSION' ) ) {
				return $links;
			}

			$action_links = array(
				'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=woostify-welcome' ) ) . '" aria-label="' . esc_attr__( 'View Woostify Pro settings', 'woostify-pro' ) . '">' . esc_html__( 'Settings', 'woostify-pro' ) . '</a>',
			);

			return array_merge( $action_links, $links );
		}

		/**
		 * Register customizer
		 *
		 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
		 */
		public function register_customizer( $wp_customize ) {
			$customizer_dir = glob( WOOSTIFY_PRO_PATH . 'inc/customizer/*.php' );

			foreach ( $customizer_dir as $file ) {
				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}
		}

		/**
		 * Script and style file for frontend.
		 */
		public function frontend_static() {
			if ( ! defined( 'WOOSTIFY_VERSION' ) ) {
				return;
			}

			// General script.
			wp_enqueue_script(
				'woostify-pro-general',
				WOOSTIFY_PRO_URI . 'assets/js/frontend' . woostify_suffix() . '.js',
				array( 'jquery' ),
				WOOSTIFY_PRO_VERSION,
				true
			);

			// General style.
			wp_enqueue_style(
				'woostify-pro-general',
				WOOSTIFY_PRO_URI . 'assets/css/frontend.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);

			// RTL.
			if ( is_rtl() ) {
				wp_enqueue_style(
					'woostify-pro-rtl',
					WOOSTIFY_PRO_URI . 'assets/css/rtl.css',
					array(),
					WOOSTIFY_PRO_VERSION
				);
			}

			// Elementor kit.
			$elementor_kit = get_option( 'elementor_active_kit' );
			if ( $elementor_kit ) {
				$css_file = null;

				if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
					$css_file = new \Elementor\Core\Files\CSS\Post( $elementor_kit );
				} elseif ( class_exists( '\Elementor\Post_CSS_File' ) ) {
					$css_file = new \Elementor\Post_CSS_File( $elementor_kit );
				}

				if ( $css_file ) {
					$css_file->enqueue();
				}
			}
		}

		/**
		 * Woostify admin scripts.
		 */
		public function admin_scripts() {
			if ( ! defined( 'WOOSTIFY_VERSION' ) ) {
				return;
			}

			// General style for Admin.
			wp_enqueue_style(
				'woostify-pro-backend',
				WOOSTIFY_PRO_URI . 'assets/css/backend.min.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);

			// Icon font for Admin.
			wp_enqueue_style(
				'woostify-pro-ionicon',
				WOOSTIFY_PRO_URI . 'assets/css/ionicons.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);

			// For color picker.
			wp_enqueue_style( 'wp-color-picker' );
			wp_register_script(
				'woostify-admin-color-picker',
				'',
				array( 'jquery', 'wp-color-picker' ),
				WOOSTIFY_PRO_VERSION,
				true
			);
			wp_enqueue_script( 'woostify-admin-color-picker' );
			wp_add_inline_script(
				'woostify-admin-color-picker',
				"( function() {
					jQuery( '.woostify-admin-color-picker.default' ).each( function ( index ) {						
						var default_color = jQuery(this)[0].getAttribute('data-colordefault');
						jQuery(this).wpColorPicker( { defaultColor: default_color } );
					});

					jQuery( '.woostify-admin-color-picker' ).wpColorPicker();
				} )( jQuery );"
			);
		}

		/**
		 * Woostify dashboard script
		 */
		public function woostify_pro_dashboard_static() {
			// For Edit post type screen.
			$screen                  = get_current_screen();
			$is_welcome              = false !== strpos( $screen->id, 'woostify-welcome' );
			$is_countdown_urgency    = false !== strpos( $screen->id, 'countdown-urgency-settings' );
			$is_swatches             = false !== strpos( $screen->id, 'variation-swatches-settings' );
			$is_sale_notification    = false !== strpos( $screen->id, 'sale-notification-settings' );
			$is_ajax_search_product  = false !== strpos( $screen->id, 'ajax-search-product-settings' );
			$is_smart_product_filter = false !== strpos( $screen->id, 'smart-product-filter-settings' );
			$is_callback             = false !== strpos( $screen->id, 'callback-settings' );
			$is_pre_order            = false !== strpos( $screen->id, 'pre-order-settings' );
			$is_white_label          = false !== strpos( $screen->id, 'white-label-settings' );

			// Script for edit post screen || some page setting.
			if ( $screen->post_type || $is_countdown_urgency || $is_swatches || $is_sale_notification || $is_ajax_search_product || $is_smart_product_filter || $is_welcome || $is_pre_order || $is_callback || $is_white_label ) {
				wp_enqueue_script(
					'woostify-edit-screen',
					WOOSTIFY_PRO_URI . 'assets/js/edit-screen' . woostify_suffix() . '.js',
					array(),
					WOOSTIFY_PRO_VERSION,
					true
				);

				$data = array(
					'save'          => __( 'Save', 'woostify-pro' ),
					'saving'        => __( 'Saving', 'woostify-pro' ),
					'saved'         => __( 'Saved', 'woostify-pro' ),
					'saved_success' => __( 'Saved successfully', 'woostify-pro' ),
				);

				wp_localize_script(
					'woostify-edit-screen',
					'woostify_edit_screen',
					$data
				);
			}

			// For Woostify Dashboard page.
			if ( ! $is_welcome ) {
				return;
			}

			// STYLE.
			// Get current color scheme.
			global $_wp_admin_css_colors;
			$colors = $_wp_admin_css_colors[ get_user_option( 'admin_color' ) ]->colors;

			wp_enqueue_style(
				'woostify-pro-dashboard',
				WOOSTIFY_PRO_URI . 'assets/css/dashboard.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);

			wp_add_inline_style(
				'woostify-pro-dashboard',
				".woostify-pro-module input[type=checkbox],
				.woostify-pro-module .active-all-item .module-name select {
					border-color: $colors[3];
				}
				.woostify-pro-module .module-item:hover .module-name label {
					color: $colors[3];
				}"
			);

			wp_enqueue_script(
				'woostify-pro-dashboard',
				WOOSTIFY_PRO_URI . 'assets/js/dashboard' . woostify_suffix() . '.js',
				array(),
				WOOSTIFY_PRO_VERSION,
				true
			);

			// SCRIPT.
			wp_localize_script(
				'woostify-pro-dashboard',
				'woostify_pro_dashboard',
				array(
					'ajax_nonce'                 => wp_create_nonce( 'dashboard_ajax_nonce' ),
					// Modules.
					'activate'                   => __( 'Activate', 'woostify-pro' ),
					'activating'                 => __( 'Activating...', 'woostify-pro' ),
					'deactivate'                 => __( 'Deactivate', 'woostify-pro' ),
					'deactivating'               => __( 'Deactivating...', 'woostify-pro' ),
					// License.
					'head'                       => get_option( 'woostify_pro_license_key', '' ), // License key.
					'receiving'                  => __( 'Receiving updates', 'woostify-pro' ),
					'not_receiving'              => __( 'Not receiving updates', 'woostify-pro' ),
					'license_empty'              => __( 'Please enter your license.', 'woostify-pro' ),
					'activate_success_message'   => __( 'Your license has been activated successfully!.', 'woostify-pro' ),
					'deactivate_success_message' => __( 'Your license has been deactivated.', 'woostify-pro' ),
					'failure_message'            => __( 'We are sorry, an error has occurred - Invalid License.', 'woostify-pro' ),
					'activate_label'             => __( 'Activate', 'woostify-pro' ),
					'deactivate_label'           => __( 'Deactivate', 'woostify-pro' ),
				)
			);
		}

		/**
		 * Add script for customizer controls
		 */
		public function customizer_control_scripts() {
			if ( ! defined( 'WOOSTIFY_VERSION' ) ) {
				return;
			}

			wp_enqueue_script(
				'woostify-pro-customizer-controls',
				WOOSTIFY_PRO_URI . 'assets/js/customizer-controls' . woostify_suffix() . '.js',
				array( 'jquery' ),
				WOOSTIFY_PRO_VERSION,
				true
			);

			wp_localize_script(
				'woostify-pro-customizer-controls',
				'woostify_pro_customizer',
				array(
					'hfb_count'  => $this->count_header_template(),
					'hfb_active' => $this->module_active( 'woostify_header_footer_builder', 'WOOSTIFY_PRO_HEADER_FOOTER_BUILDER' ),
				)
			);
		}

		/**
		 * Count header template
		 *
		 * @return int
		 */
		public function count_header_template() {
			$args = array(
				'post_type'           => 'hf_builder',
				'orderby'             => 'id',
				'order'               => 'DESC',
				'post_status'         => 'publish',
				'posts_per_page'      => 1,
				'ignore_sticky_posts' => 1,
				'meta_query'          => array(//phpcs:ignore
					array(
						'key'     => 'woostify-header-footer-builder-template',
						'compare' => 'LIKE',
						'value'   => 'header',
					),
				),
			);

			$header = new WP_Query( $args );

			return $header->post_count;
		}

		/**
		 * Add script for customizer preview
		 */
		public function customizer_preview_scripts() {
			if ( ! defined( 'WOOSTIFY_VERSION' ) ) {
				return;
			}

			wp_enqueue_script(
				'woostify-pro-customizer-preview',
				WOOSTIFY_PRO_URI . 'assets/js/customizer-preview' . woostify_suffix() . '.js',
				array( 'jquery' ),
				WOOSTIFY_PRO_VERSION,
				true
			);
		}

		/**
		 * Check to see if a module is active
		 *
		 * @param string $module The module.
		 * @param string $definition The definition.
		 *
		 * @return     boolean
		 */
		public function module_active( $module, $definition ) {
			// If we don't have the module or definition, bail.
			if ( ! $module && ! $definition ) {
				return false;
			}

			// If our module is active, return true.
			if ( 'activated' === get_option( $module ) || defined( $definition ) ) {
				return true;
			}

			// Not active? Return false.
			return false;
		}

		/**
		 * Default Options Value
		 */
		public function default_options_value() {
			$args = array(
				// HEADER.
				// Layout 1.
				'header_full_width'                  => false,
				// Layout 3.
				'header_left_content'                => '',
				// Layout 5.
				'header_center_content'              => '',
				// Layout 6.
				'header_right_content'               => '[header_content_block]',
				'header_content_bottom_background'   => '#212121',
				// Layout 7.
				'header_sidebar_content_bottom'      => '',
				// Layout 8.
				'header_8_search_bar_background'     => '#fcb702',
				'header_8_button_background'         => '#ffffff',
				'header_8_button_color'              => '#333333',
				'header_8_button_hover_background'   => '#333333',
				'header_8_button_hover_color'        => '#ffffff',
				'header_8_icon_color'                => '#000000',
				'header_8_icon_hover_color'          => '#cccccc',
				'header_8_button_text'               => __( 'Shop By Categories', 'woostify-pro' ),
				'header_8_right_content'             => '[header_single_block icon="headphone-alt" icon_color="" heading="(+245)-1802-2019" href="megashop@info.com"]',
				'header_8_content_right_text_color'  => '#333333',

				// AJAX PRODUCT SEARCH.
				'ajax_product_search'                => true,

				// STICKY HEADER.
				'sticky_header_display'              => false,
				'sticky_header_disable_archive'      => true,
				'sticky_header_disable_index'        => false,
				'sticky_header_disable_page'         => false,
				'sticky_header_disable_post'         => false,
				'sticky_header_disable_shop'         => false,
				'sticky_header_background_color'     => '#ffffff',
				'sticky_header_enable_on'            => 'all-devices',
				'sticky_header_border_color'         => '#eaeaea',
				'sticky_header_border_width'         => 1,

				// SHOP PAGE.
				'shop_page_quick_view_position'      => 'top-right',
				'shop_product_quick_view_icon'       => true,
				'shop_product_quick_view_background' => '',
				'shop_product_quick_view_color'      => '',
				'shop_product_quick_view_bg_hover'   => '',
				'shop_product_quick_view_c_hover'    => '',
				'shop_product_quick_view_radius'     => '',
				// Product Loop Style.
				'product_loop_icon_position'         => 'bottom-right',
				'product_loop_icon_direction'        => 'horizontal',
				'product_loop_icon_color'            => '#b7b7b7',
				'product_loop_icon_background_color' => '#ffffff',

				// SHOP SINGLE.
				// Buy Now Hover.
				'shop_single_background_hover'       => '',
				'shop_single_color_hover'            => '',
				'shop_single_buy_now_button'         => '',
				'shop_single_background_buynow'      => '',
				'shop_single_color_button_buynow'    => '',
				'shop_single_border_radius_buynow'   => '',

				// Sticky button.
				'sticky_single_add_to_cart_button'   => 'top',
				'sticky_atc_button_on'               => 'both',
			);

			return $args;
		}

		/**
		 * Woostify Pro Options Value
		 */
		public function woostify_pro_options() {
			$args = wp_parse_args(
				get_option( 'woostify_pro_options', array() ),
				self::default_options_value()
			);

			return $args;
		}

		/**
		 * Woostify pro modules
		 */
		public function woostify_pro_modules() {
			// Elementor.
			$elementor_condition = defined( 'ELEMENTOR_VERSION' );
			$elementor_error     = __( 'Elementor must be activated', 'woostify-pro' );

			// Woocommerce.
			$woocommerce_condition = defined( 'WC_PLUGIN_FILE' );
			$woocommerce_error     = __( 'WooCommerce must be activated', 'woostify-pro' );

			// Required Elementor and Woocommerce.
			$woo_elementor_condition = $elementor_condition && $woocommerce_condition;
			$woo_elementor_error     = __( 'WooCommerce and Elementor must be activeted', 'woostify-pro' );

			// Documention site
			$woostify_url = 'https://woostify.com';

			$modules = array(
				'woostify_multiphe_header'          => array(
					'title'       => __( 'Multiple Headers', 'woostify-pro' ),
					'description' => __( 'Offering you with a set of 8 stunning header layouts to apply and customize', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-multiple-header.png',
					'category'    => array( 'storebuilder' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/multiple-headers/',
					'setting_url' => false,
				),
				'woostify_sticky_header'            => array(
					'title'       => __( 'Sticky Header', 'woostify-pro' ),
					'description' => __( 'Creating a floating header that sticks at the top of your site when scrolling', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-sticky-header.png',
					'category'    => array( 'storebuilder' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/sticky-header/',
					'setting_url' => false,
				),
				'woostify_mega_menu'                => array(
					'title'       => __( 'Mega Menu', 'woostify-pro' ),
					'description' => __( 'Create a fully responsive mega menu with Elementor.', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-mega-menu.png',
					'category'    => array( 'storebuilder' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/elementor-mega-menu/',
					'setting_url' => get_admin_url() . 'edit.php?post_type=mega_menu',
				),
				'woostify_elementor_widgets'        => array(
					'title'       => __( 'Elementor Bundle', 'woostify-pro' ),
					'description' => __( 'Customize widget elementor', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-woobuilder.png',
					'category'    => array( 'storebuilder' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/elementor-addons/',
					'setting_url' => false,
					'condition'   => $elementor_condition,
					'error'       => $elementor_error,
				),
				'woostify_header_footer_builder'    => array(
					'title'       => __( 'Header Footer Builder', 'woostify-pro' ),
					'description' => __( 'Create your website header & footer using Elementor', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-header-footer-builder.png',
					'category'    => array( 'storebuilder' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/header-footer-builder/',
					'setting_url' => get_admin_url() . 'edit.php?post_type=hf_builder',
					'condition'   => $elementor_condition,
					'error'       => $elementor_error,
				),
				'woostify_woo_builder'              => array(
					'title'       => __( 'WooBuilder', 'woostify-pro' ),
					'description' => __( 'Customize shop page, product page, cart page, and checkout page as desired', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-woobuilder.png',
					'category'    => array( 'storebuilder' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/woobuider/',
					'setting_url' => get_admin_url() . 'edit.php?post_type=woo_builder',
					'condition'   => $woo_elementor_condition,
					'error'       => $woo_elementor_error,
				),
				'woostify_smart_product_filter'     => array(
					'title'       => __( 'Smart Product Filter ', 'woostify-pro' ),
					'description' => __( 'Filters by any criteria, attributes, taxonomies, prices, or other product data.', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-smart-product-filter.png',
					'category'    => array( 'ecommerce' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/smart-product-filter/',
					'setting_url' => get_admin_url() . 'admin.php?page=smart-product-filter-settings',
					'condition'   => $woocommerce_condition,
					'error'       => $woocommerce_error,
				),
				'woostify_wc_ajax_product_search'   => array(
					'title'       => __( 'Ajax Product Search', 'woostify-pro' ),
					'description' => __( 'Allow customers to get instant live search results as they type their query', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-ajax-search.png',
					'category'    => array( 'ecommerce' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/woocommerce-product-search/',
					'setting_url' => get_admin_url() . 'admin.php?page=ajax-search-product-settings',
					'condition'   => defined( 'WC_PLUGIN_FILE' ),
					'error'       => $woocommerce_error,
				),
				'woostify_size_guide'               => array(
					'title'       => __( 'Size Guide', 'woostify-pro' ),
					'description' => __( 'Assign ready-to-use default size chart templates to the product', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-size-guide.png',
					'category'    => array( 'ecommerce' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/size-guide/',
					'setting_url' => get_admin_url() . 'edit.php?post_type=size_guide',
					'condition'   => defined( 'WC_PLUGIN_FILE' ),
					'error'       => $woocommerce_error,
				),
				'woostify_wc_advanced_shop_widgets' => array(
					'title'       => __( 'Advanced Shop Widgets', 'woostify-pro' ),
					'description' => __( 'More Shop’s widgets including nested product categories and feature products', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-advanced-shop-widgets.png',
					'category'    => array( 'deprecated' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/advanced-widgets/',
					'setting_url' => false,
					'condition'   => $woocommerce_condition,
					'error'       => $woocommerce_error,
				),
				'woostify_wc_buy_now_button'        => array(
					'title'       => __( 'Buy Now Button', 'woostify-pro' ),
					'description' => __( 'Customers go to checkout page immediately if they click into Buy Now', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-buy-now.png',
					'category'    => array( 'conversion' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/buy-now-button/',
					'setting_url' => false,
					'condition'   => $woocommerce_condition,
					'error'       => $woocommerce_error,
				),
				'woostify_wc_sticky_button'         => array(
					'title'       => __( 'Sticky Single Add To Cart', 'woostify-pro' ),
					'description' => __( 'Add the  products to shopping cart immediately without scrolling up', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-sticky-add-to-cart.png',
					'category'    => array( 'conversion' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/sticky-add-to-cart-button/',
					'setting_url' => false,
					'condition'   => $woocommerce_condition,
					'error'       => $woocommerce_error,
				),
				'woostify_wc_quick_view'            => array(
					'title'       => __( 'Quick View', 'woostify-pro' ),
					'description' => __( 'Allow clients to have a quick view of your product details', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-quick-view.png',
					'category'    => array( 'ecommerce' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/quick-view/',
					'setting_url' => false,
					'condition'   => $woocommerce_condition,
					'error'       => $woocommerce_error,
				),
				'woostify_wc_countdown_urgency'     => array(
					'title'       => __( 'Countdown Urgency', 'woostify-pro' ),
					'description' => __( 'Countdown that motivates customers to buy product before time runs out', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-countdown-urgency.png',
					'category'    => array( 'conversion' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/countdown/',
					'setting_url' => get_admin_url() . 'admin.php?page=countdown-urgency-settings',
					'condition'   => $woocommerce_condition,
					'error'       => $woocommerce_error,
				),
				'woostify_wc_variation_swatches'    => array(
					'title'       => __( 'Variation Swatches', 'woostify-pro' ),
					'description' => __( 'Color, Image and Buttons Variation Swatches for product attributes', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-variation-swatches.png',
					'category'    => array( 'ecommerce' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/variation-swatches/',
					'setting_url' => get_admin_url() . 'admin.php?page=variation-swatches-settings',
					'condition'   => $woocommerce_condition,
					'error'       => $woocommerce_error,
				),
				'woostify_wc_callback'              => array(
					'title'       => __( 'Call Back', 'woostify-pro' ),
					'description' => __( 'Displays the email subscription form when the product is out of stock', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-callback.png',
					'category'    => array( 'ecommerce' ),
					'doc'         => esc_url( $woostify_url ),
					'setting_url' => get_admin_url() . 'admin.php?page=callback-settings',
					'condition'   => $woocommerce_condition,
					'error'       => $woocommerce_error,
				),
				'woostify_wc_pre_order'             => array(
					'title'       => __( 'Pre Order', 'woostify-pro' ),
					'description' => __( 'Allows customers to place an order for your upcoming products', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-pre-order.png',
					'category'    => array( 'ecommerce' ),
					'doc'         => esc_url( $woostify_url ),
					'setting_url' => get_admin_url() . 'admin.php?page=pre-order-settings',
					'condition'   => $woocommerce_condition,
					'error'       => $woocommerce_error,
				),
				'woostify_wc_sale_notification'     => array(
					'title'       => __( 'Sale Notification', 'woostify-pro' ),
					'description' => __( 'Create social proof about a busy store by displaying recent orders on a pop-up', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-sale-notification.png',
					'category'    => array( 'conversion' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/sale-notification/',
					'setting_url' => get_admin_url() . 'admin.php?page=sale-notification-settings',
					'condition'   => $woocommerce_condition,
					'error'       => $woocommerce_error,
				),
				'woostify_white_label'              => array(
					'title'       => __( 'White Label', 'woostify-pro' ),
					'description' => __( 'Change info theme and plugin', 'woostify-pro' ),
					'icon'        => WOOSTIFY_PRO_URI . 'assets/images/module-icon-white-label.png',
					'category'    => array( 'storebuilder' ),
					'doc'         => esc_url( $woostify_url ) . '/docs/pro-modules/white-label/',
					'setting_url' => get_admin_url() . 'admin.php?page=white-label-settings',
				),
			);
			if ( class_exists( 'Woostify_White_Label' ) && get_option( 'woostify_white_label_hide_branding', 0 ) ) {
				unset( $modules['woostify_white_label'] );
			}

			return $modules;
		}

		/**
		 * Module List
		 */
		public function module_list() {
			if ( ! defined( 'WOOSTIFY_VERSION' ) ) {
				return;
			}

			// Define modules dir.
			if ( ! defined( 'WOOSTIFY_PRO_MODULES_PATH' ) ) {
				define( 'WOOSTIFY_PRO_MODULES_PATH', WOOSTIFY_PRO_PATH . 'modules/' );
			}
			if ( ! defined( 'WOOSTIFY_PRO_MODULES_URI' ) ) {
				define( 'WOOSTIFY_PRO_MODULES_URI', WOOSTIFY_PRO_URI . 'modules/' );
			}

			// Multiple header.
			if ( $this->module_active( 'woostify_multiphe_header', 'WOOSTIFY_PRO_MULTIPLE_HEADER' ) ) {
				require_once WOOSTIFY_PRO_MODULES_PATH . 'multiple-header/class-woostify-multiple-header.php';
			}

			// Sticky header.
			if ( $this->module_active( 'woostify_sticky_header', 'WOOSTIFY_PRO_STICKY_HEADER' ) ) {
				require_once WOOSTIFY_PRO_MODULES_PATH . 'sticky-header/class-woostify-sticky-header.php';
			}

			// Mega menu.
			if ( $this->module_active( 'woostify_mega_menu', 'WOOSTIFY_PRO_MEGA_MENU' ) ) {
				require_once WOOSTIFY_PRO_MODULES_PATH . 'mega-menu/class-woostify-mega-menu.php';
			}

			// White Label.
			if ( $this->module_active( 'woostify_white_label', 'WOOSTIFY_PRO_WHITE_LABEL' ) ) {
				require_once WOOSTIFY_PRO_MODULES_PATH . 'white-label/class-woostify-white-label.php';
			}

			// Required Elementor and Woocommerce.
			if ( woostify_is_elementor_activated() && woostify_is_woocommerce_activated() ) {
				// Woocommerce Builder.
				if ( $this->module_active( 'woostify_woo_builder', 'WOOSTIFY_PRO_WOO_BUILDER' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woo-builder/class-woostify-woo-builder.php';
				}
			}

			/**
			 * Woocommerce Modules
			 */
			if ( woostify_is_woocommerce_activated() ) {
				// Woocommerce helper.
				require_once WOOSTIFY_PRO_PATH . 'inc/woocommerce/class-woostify-woocommerce-helper.php';

				// Ajax product tab. For Product Tab widget.
				require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/ajax-product-tabs/class-woostify-ajax-product-tab.php';

				// Size guide.
				if ( $this->module_active( 'woostify_size_guide', 'WOOSTIFY_PRO_SIZE_GUIDE' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/size-guide/class-woostify-size-guide.php';
				}

				// Ajax product search.
				if ( $this->module_active( 'woostify_wc_ajax_product_search', 'WOOSTIFY_PRO_AJAX_PRODUCT_SEARCH' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/ajax-product-search/includes/class-woostify-index-table.php';
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/ajax-product-search/class-woostify-ajax-product-search.php';
				}

				// Buy now button.
				if ( $this->module_active( 'woostify_wc_buy_now_button', 'WOOSTIFY_PRO_BUY_NOW_BUTTON' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/buy-now-button/class-woostify-buy-now-button.php';
				}

				// Sticky add to cart button on product page.
				if ( $this->module_active( 'woostify_wc_sticky_button', 'WOOSTIFY_PRO_STICKY_SINGLE_ADD_TO_CART' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/sticky-button/class-woostify-sticky-button.php';
				}

				// Advanced shop widgets.
				if ( $this->module_active( 'woostify_wc_advanced_shop_widgets', 'WOOSTIFY_PRO_ADVANCED_SHOP_WIDGETS' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/advanced-widgets/class-woostify-advanced-shop-widgets.php';
				}

				// Quick view popup.
				if ( $this->module_active( 'woostify_wc_quick_view', 'WOOSTIFY_PRO_QUICK_VIEW' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/quick-view/class-woostify-quick-view.php';
				}

				// Countdown urgency.
				if ( $this->module_active( 'woostify_wc_countdown_urgency', 'WOOSTIFY_PRO_COUNTDOWN_URGENCY' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/countdown-urgency/class-woostify-countdown-urgency.php';
				}

				// Variation swatches.
				if ( $this->module_active( 'woostify_wc_variation_swatches', 'WOOSTIFY_PRO_VARIATION_SWATCHES' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/variation-swatches/class-woostify-variation-swatches.php';
				}

				// Pre Order.
				if ( $this->module_active( 'woostify_wc_pre_order', 'WOOSTIFY_PRO_PRE_ORDER' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/pre-order/class-woostify-pre-order.php';
				}

				// Sale notification.
				if ( $this->module_active( 'woostify_wc_sale_notification', 'WOOSTIFY_PRO_SALE_NOTIFICATION' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/sale-notification/class-woostify-sale-notification.php';
				}

				// Call Back.
				if ( $this->module_active( 'woostify_wc_callback', 'WOOSTIFY_PRO_CALLBACK' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/callback/class-woostify-callback.php';
				}

				// Smart product filter.
				if ( $this->module_active( 'woostify_smart_product_filter', 'WOOSTIFY_PRO_PRODUCT_FILTER' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/product-filter/class-woostify-product-filter.php';
					require_once WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/product-filter/class-woostify-filter-render.php';
				}
			}

			/**
			 * Elementor Modules
			 */
			if ( woostify_is_elementor_activated() ) {
				// Elementor helper.
				require_once WOOSTIFY_PRO_PATH . 'inc/elementor/class-woostify-elementor-helper.php';

				// Header Footer Builder.
				if ( $this->module_active( 'woostify_header_footer_builder', 'WOOSTIFY_PRO_HEADER_FOOTER_BUILDER' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'header-footer-builder/class-woostify-header-footer-builder.php';
				}

				// Elementor Widgets.
				if ( $this->module_active( 'woostify_elementor_widgets', 'WOOSTIFY_PRO_ELEMENTOR_WIDGETS' ) ) {
					require_once WOOSTIFY_PRO_MODULES_PATH . 'elementor/class-woostify-elementor-widgets.php';
				}
			}
		}

		/**
		 * Set up the updater
		 **/
		public function woostify_pro_updater() {
			// Load EDD SL Plugin Updater.
			// Testing updater.
			if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
				require_once WOOSTIFY_PRO_PATH . 'inc/EDD_SL_Plugin_Updater.php';
			}

			// Retrieve our license key from the DB.
			$license_key = get_option( 'woostify_pro_license_key' );

			// License status.
			$license_status = get_option( 'woostify_pro_license_key_status', 'invalid' );

			// Item name.
			$item_name = get_option( 'woostify_pro_package_name' );

			// Item expires.
			$item_expires = get_option( 'woostify_pro_license_key_expires' );

			// Setup the updater.
			if ( $item_name && $license_key && $item_expires && 'valid' === $license_status ) {
				$edd_updater = new EDD_SL_Plugin_Updater(
					'https://woostify.com',
					WOOSTIFY_PRO_FILE,
					array(
						'version'   => WOOSTIFY_PRO_VERSION,
						'license'   => trim( $license_key ),
						'item_name' => rawurlencode( $item_name ),
						'author'    => 'Woostify',
						'url'       => home_url(),
						'beta'      => apply_filters( 'woostify_pro_beta_tester', false ),
					)
				);
			}
		}

		/**
		 * Build the area that allows us to activate and deactivate modules infomation.
		 */
		public function woostify_extract_modules() {
			// Get current color scheme.
			global $_wp_admin_css_colors;
			$colors = $_wp_admin_css_colors[ get_user_option( 'admin_color' ) ]->colors;

			$checked_status = false;
			$show_default   = '';

			foreach ( $this->woostify_pro_modules() as $k => $v ) {
				$status = get_option( $k );
				if ( $status == 'activated' ) {
					$checked_status = true;
				}
			}
			if ( ! $checked_status ) {
				$show_default = 'show-default';
			}
			?>
			<div class="woostify-pro-module-info">
				<div class="woostify-pro-module-info-header">
					<h2>
						<?php
						/* translators: Woostify Pro Version */
						echo esc_html( sprintf( __( 'Active Add-ons', 'woostify-pro' ), WOOSTIFY_PRO_VERSION ) );
						?>
					</h2>
					<a href="<?php echo esc_url( get_admin_url() ) . 'admin.php?page=woostify-welcome#add-ons'; ?>" class="activate-add-ons tab-head-button" data-tab="add-ons">
						<?php
						/* translators: Woostify Pro Version */
						echo esc_html( sprintf( __( 'View All Add-ons', 'woostify-pro' ), WOOSTIFY_PRO_VERSION ) );
						?>
					</a>
				</div>
				<div class="woostify-module-info-list <?php echo esc_attr( $show_default ); ?>">
					<?php
					foreach ( $this->woostify_pro_modules() as $k => $v ) {
						$status      = get_option( $k );
						$title       = $v['title'];
						$description = $v['description'];
						$icon        = $v['icon'];
						$doc         = $v['doc'];

						$disabled = '';
						if ( is_array( $v ) ) {
							if ( isset( $v['condition'] ) && ! $v['condition'] ) {
								$disabled = 'disabled';
							}
						}
						$category     = implode( ' ', $v['category'] );
						$setting_page = ( $v['setting_url'] != false ) ? substr( $v['setting_url'], strpos( $v['setting_url'], '=' ) + 1 ) : '';
						?>
						<div class="module-info-item <?php echo esc_attr( $category ); ?> <?php echo esc_attr( $k ); ?> <?php echo esc_attr( $status ); ?> <?php echo esc_attr( $disabled ); ?>">
								<?php
								if ( $setting_page == 'callback-settings' || $setting_page == 'smart-product-filter-settings' ) {
									?>
									<div class="w-icon-setting"><a class="module-setting-url" href="<?php echo esc_url( $v['setting_url'] ); ?>">
										<?php
										echo '<img class="icon-setting-img" src="'.WOOSTIFY_PRO_URI.'assets/images/setting-svgrepo-com.svg"/>'; //phpcs:ignore 
										echo '<img class="icon-setting-img-hover" src="'.WOOSTIFY_PRO_URI.'assets/images/setting-svgrepo-com-hover.svg"/>'; //phpcs:ignore 
										?>
									</a></div>
									<?php
								}
								?>
							<?php // endif; ?>
							<span class="module-info-item-pro-text"><?php esc_html_e( 'Pro', 'woostify' ); ?></span>
							<div class="module-info-item-icon">
								<img src="<?php echo esc_url( $icon ); ?>" alt="<?php echo esc_attr( 'Woostify' ); ?>">
							</div>
							<div class="module-info-item-content">
								<?php
								if ( $v['setting_url'] ) {
									?>
										<a href="<?php echo esc_url( $v['setting_url'] ); ?>" class="module-info-item-title"><?php echo esc_html( $title ); ?></a>
										<?php
								} else {
									?>
										<span class="module-info-item-title"><?php echo esc_html( $title ); ?></span>
										<?php
								}
								?>
								<div class="module-info-item-description"><?php echo esc_html( $description ); ?></div>
								<a href="<?php echo esc_url( $doc ); ?>" class="module-info-item-doc" target="_blank"><?php esc_html_e( 'Documention', 'woostify-pro' ); ?></a>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Build the area that allows us to activate and deactivate modules.
		 */
		public function woostify_extract_modules_addons() {
			// Get current color scheme.
			global $_wp_admin_css_colors;
			$colors = $_wp_admin_css_colors[ get_user_option( 'admin_color' ) ]->colors;

			?>
			<div class="woostify-pro-module">
				<div class="woostify-module-action-header">
					<div class="woostify-module-addons-tabs">
						<a href="#all" class="tab-module-button active" data-module-cat="all"><?php esc_html_e( 'All', 'woostify-pro' ); ?></a>
						<a href="#storebuilder" class="tab-module-button" data-module-cat="storebuilder"><?php esc_html_e( 'Store Builder', 'woostify-pro' ); ?></a>
						<a href="#ecommerce" class="tab-module-button" data-module-cat="ecommerce"><?php esc_html_e( 'Ecommerce', 'woostify-pro' ); ?></a>
						<a href="#conversion" class="tab-module-button" data-module-cat="conversion"><?php esc_html_e( 'Conversion', 'woostify-pro' ); ?></a>
						<a href="#deprecated" class="tab-module-button" data-module-cat="deprecated"><?php esc_html_e( 'Deprecated', 'woostify-pro' ); ?></a>
					</div>
					<div id="add-ons-active-all" class="active-all-item" data-multi-cat="all">
						<div class="module-name">
							<label for="woostify-select-module-activate-all" class="woostify-select-module-all-switch">
								<input type="checkbox" id="woostify-select-module-activate-all" class="woostify-select-module-all" 
									data-module-cat="all"
									data-module-action="activated">
								<span class="add-ons-icon icon-active">
									<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M1.05611 5.67438C1.2202 5.51035 1.44272 5.4182 1.67474 5.4182C1.90676 5.4182 2.12928 5.51035 2.29337 5.67438L5.38736 8.76838L11.5736 2.58126C11.6549 2.49996 11.7513 2.43546 11.8575 2.39144C11.9636 2.34743 12.0774 2.32475 12.1924 2.32471C12.3073 2.32467 12.4211 2.34726 12.5273 2.39121C12.6335 2.43515 12.73 2.49958 12.8113 2.58082C12.8926 2.66206 12.9571 2.75852 13.0011 2.86468C13.0451 2.97085 13.0678 3.08465 13.0679 3.19958C13.0679 3.31451 13.0453 3.42832 13.0014 3.53451C12.9574 3.64071 12.893 3.73721 12.8117 3.81851L5.38736 11.2429L1.05611 6.91163C0.892078 6.74755 0.799927 6.52503 0.799927 6.29301C0.799927 6.06099 0.892078 5.83847 1.05611 5.67438Z" fill="white"/>
									</svg>
								</span>
								<span class="add-ons-icon icon-deactivated">
									<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path fill-rule="evenodd" clip-rule="evenodd" d="M2.17863 1.2357L14.9063 13.9637C14.9688 14.0262 15.0039 14.111 15.0039 14.1994C15.0039 14.2878 14.9688 14.3725 14.9063 14.435L14.435 14.9064C14.3725 14.9689 14.2877 15.004 14.1993 15.004C14.1109 15.004 14.0261 14.9689 13.9636 14.9064L1.23563 2.17837C1.17314 2.11586 1.13803 2.03109 1.13803 1.9427C1.13803 1.85431 1.17314 1.76954 1.23563 1.70703L1.70696 1.2357C1.73792 1.20471 1.77468 1.18012 1.81515 1.16335C1.85561 1.14657 1.89899 1.13794 1.94279 1.13794C1.9866 1.13794 2.02997 1.14657 2.07044 1.16335C2.11091 1.18012 2.14767 1.20471 2.17863 1.2357ZM2.66529 5.02137L3.53663 5.8927C3.00896 6.47337 2.49663 7.17137 1.99996 7.99137C3.84029 10.961 5.83196 12.3334 7.98996 12.3334C8.56463 12.3334 9.12796 12.236 9.67996 12.0374L10.6826 13.0394C9.82563 13.4577 8.92829 13.6667 7.98929 13.6667C5.16563 13.6667 2.72496 11.778 0.666626 8.00137C1.29029 6.8247 1.95696 5.83137 2.66529 5.02137ZM7.98863 2.33337C10.8676 2.33337 13.3156 4.2227 15.3333 8.00137C14.6946 9.1647 14.0203 10.149 13.3106 10.9544L12.439 10.0824C12.9703 9.5027 13.4906 8.80737 14 7.99137C12.1946 5.03304 10.2006 3.6667 7.98996 3.6667C7.41329 3.6667 6.85263 3.75937 6.30629 3.94937L5.29929 2.9427C6.13838 2.5389 7.0581 2.33057 7.98929 2.33337H7.98863ZM5.34896 7.70637L8.29329 10.6507C7.8959 10.6948 7.49369 10.6489 7.11642 10.5165C6.73915 10.3841 6.39648 10.1686 6.11376 9.8859C5.83104 9.60318 5.61552 9.26051 5.48313 8.88324C5.35075 8.50597 5.30489 8.10376 5.34896 7.70637ZM7.99996 5.33337C8.37495 5.33334 8.74572 5.41239 9.08809 5.56538C9.43045 5.71836 9.7367 5.94182 9.98684 6.22119C10.237 6.50055 10.4254 6.82953 10.5398 7.18665C10.6541 7.54377 10.6919 7.92099 10.6506 8.2937L7.70629 5.34937C7.80296 5.3387 7.90063 5.33337 7.99996 5.33337Z" fill="black"/>
									</svg>
								</span>
								<span class="add-ons-active-text"><?php esc_html_e( 'Activate all', 'woostify-pro' ); ?></span>
							</label>
							<label for="woostify-select-module-deactivated-all" class="woostify-select-module-all-switch">
								<input type="checkbox" id="woostify-select-module-deactivated-all" class="woostify-select-module-all" 
									data-module-cat="all"
									data-module-action="deactivated">
								<span class="add-ons-icon icon-active">
									<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M1.05611 5.67438C1.2202 5.51035 1.44272 5.4182 1.67474 5.4182C1.90676 5.4182 2.12928 5.51035 2.29337 5.67438L5.38736 8.76838L11.5736 2.58126C11.6549 2.49996 11.7513 2.43546 11.8575 2.39144C11.9636 2.34743 12.0774 2.32475 12.1924 2.32471C12.3073 2.32467 12.4211 2.34726 12.5273 2.39121C12.6335 2.43515 12.73 2.49958 12.8113 2.58082C12.8926 2.66206 12.9571 2.75852 13.0011 2.86468C13.0451 2.97085 13.0678 3.08465 13.0679 3.19958C13.0679 3.31451 13.0453 3.42832 13.0014 3.53451C12.9574 3.64071 12.893 3.73721 12.8117 3.81851L5.38736 11.2429L1.05611 6.91163C0.892078 6.74755 0.799927 6.52503 0.799927 6.29301C0.799927 6.06099 0.892078 5.83847 1.05611 5.67438Z" fill="white"/>
									</svg>
								</span>
								<span class="add-ons-icon icon-deactivated">
									<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path fill-rule="evenodd" clip-rule="evenodd" d="M2.17863 1.2357L14.9063 13.9637C14.9688 14.0262 15.0039 14.111 15.0039 14.1994C15.0039 14.2878 14.9688 14.3725 14.9063 14.435L14.435 14.9064C14.3725 14.9689 14.2877 15.004 14.1993 15.004C14.1109 15.004 14.0261 14.9689 13.9636 14.9064L1.23563 2.17837C1.17314 2.11586 1.13803 2.03109 1.13803 1.9427C1.13803 1.85431 1.17314 1.76954 1.23563 1.70703L1.70696 1.2357C1.73792 1.20471 1.77468 1.18012 1.81515 1.16335C1.85561 1.14657 1.89899 1.13794 1.94279 1.13794C1.9866 1.13794 2.02997 1.14657 2.07044 1.16335C2.11091 1.18012 2.14767 1.20471 2.17863 1.2357ZM2.66529 5.02137L3.53663 5.8927C3.00896 6.47337 2.49663 7.17137 1.99996 7.99137C3.84029 10.961 5.83196 12.3334 7.98996 12.3334C8.56463 12.3334 9.12796 12.236 9.67996 12.0374L10.6826 13.0394C9.82563 13.4577 8.92829 13.6667 7.98929 13.6667C5.16563 13.6667 2.72496 11.778 0.666626 8.00137C1.29029 6.8247 1.95696 5.83137 2.66529 5.02137ZM7.98863 2.33337C10.8676 2.33337 13.3156 4.2227 15.3333 8.00137C14.6946 9.1647 14.0203 10.149 13.3106 10.9544L12.439 10.0824C12.9703 9.5027 13.4906 8.80737 14 7.99137C12.1946 5.03304 10.2006 3.6667 7.98996 3.6667C7.41329 3.6667 6.85263 3.75937 6.30629 3.94937L5.29929 2.9427C6.13838 2.5389 7.0581 2.33057 7.98929 2.33337H7.98863ZM5.34896 7.70637L8.29329 10.6507C7.8959 10.6948 7.49369 10.6489 7.11642 10.5165C6.73915 10.3841 6.39648 10.1686 6.11376 9.8859C5.83104 9.60318 5.61552 9.26051 5.48313 8.88324C5.35075 8.50597 5.30489 8.10376 5.34896 7.70637ZM7.99996 5.33337C8.37495 5.33334 8.74572 5.41239 9.08809 5.56538C9.43045 5.71836 9.7367 5.94182 9.98684 6.22119C10.237 6.50055 10.4254 6.82953 10.5398 7.18665C10.6541 7.54377 10.6919 7.92099 10.6506 8.2937L7.70629 5.34937C7.80296 5.3387 7.90063 5.33337 7.99996 5.33337Z" fill="black"/>
									</svg>
								</span>
								<span class="add-ons-active-text"><?php esc_html_e( 'Deactivate all', 'woostify-pro' ); ?></span>
							</label>
						</div>
					</div>
				</div>
				<div class="woostify-module-list">
					<?php
					foreach ( $this->woostify_pro_modules() as $k => $v ) {
						$key      = get_option( $k );
						$label    = 'activated' === $key ? 'deactivate' : 'activate';
						$title    = $v;
						$disabled = '';

						if ( is_array( $v ) ) {
							$title = $v['title'];

							if ( isset( $v['condition'] ) && ! $v['condition'] ) {
								$label    = $v['error'];
								$disabled = 'disabled';
							}
						}

						$category = implode( ' ', $v['category'] );
						$id       = 'module-id-' . $k;
						?>
						<div class="module-item <?php echo esc_attr( $category ); ?> <?php echo esc_attr( $key ); ?> <?php echo esc_attr( $disabled ); ?>" data-module-cat="<?php echo esc_attr( $category ); ?>">
							<?php if ( $title ) : ?>
							<div class="module-icon">
								<img src="<?php echo esc_url( $v['icon'] ); ?>" alt="<?php echo esc_attr( 'Woostify' ); ?>">
							</div>
							<?php endif; ?>
							<div class="module-name">
								<?php
								if ( $v['setting_url'] ) {
									?>
									<a href="<?php echo esc_url( $v['setting_url'] ); ?>" class="module-name-heading"><?php echo esc_html( $title ); ?></a>
									<?php
								} else {
									?>
									<h4 class="module-name-heading"><?php echo esc_html( $title ); ?></h4>
									<?php
								}
								?>
								<div class="module-name-description"><?php esc_html_e( $v['description'], 'woostify-pro' ); ?></div>
								<a href="<?php echo esc_url( $v['doc'] ); ?>" class="module-name-doc" target="_blank"><?php esc_html_e( 'Documention', 'woostify-pro' ); ?></a>
							</div>
							<?php if ( is_array( $v ) && $v['setting_url'] ) : ?>
								<div class="w-icon-setting"><a class="module-setting-url" href="<?php echo esc_url( $v['setting_url'] ); ?>">
									<?php
									echo '<img class="icon-setting-img" src="'.WOOSTIFY_PRO_URI.'assets/images/setting-svgrepo-com.svg"/>'; //phpcs:ignore 
									echo '<img class="icon-setting-img-hover" src="'.WOOSTIFY_PRO_URI.'assets/images/setting-svgrepo-com-hover.svg"/>'; //phpcs:ignore 
									?>
								</a></div>
							<?php endif; ?>
							<div class="module-action">
								<label for="<?php echo esc_attr( $id ); ?>" class="module-name-switch">
									<input type="checkbox" class="module-checkbox" name="woostify_module_checkbox[]"
									data-status="<?php echo esc_attr( $key ); ?>"
									value="<?php echo esc_attr( $k ); ?>" id="<?php echo esc_attr( $id ); //phpcs:ignore?>"/>
									<span class="module-name-slider"></span>								
								</label>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Show Changelog.
		 */
		public function woostify_extract_modules_changelog() {

			$request_changelog = wp_remote_get( 'https://woostify.com/wp-json/wp/v2/changelog?per_page=10&product=134' );

			if ( is_wp_error( $request_changelog ) ) {
				return false;
			}

			$changelog_totalpages = (int) $request_changelog['headers']['x-wp-totalpages'];

			$body = wp_remote_retrieve_body( $request_changelog );

			$data = json_decode( $body, true );

			?>
			<div class="changelog-woostify">
				<div class="changelog-woostify-header">
					<h2 class="changelog-woostify-title"><?php esc_html_e( 'Changelog woostify pro', 'woostify-pro' ); ?></h2>
					<div class="changelog-woostify-link"><?php esc_html_e( 'Woostify Pro', 'woostify-pro' ); ?></div>
				</div>
				<div class="changelog-woostify-content">
					<ul class="changelog-woostify-version">
					<?php foreach ( $data as $key => $value ) : ?>
						<?php
						$ver_title   = $value['title']['rendered'];
						$date        = date_create( $value['date'] );
						$ver_date    = date_format( $date, 'F d, Y' );
						$ver_content = $value['content']['rendered'];
						?>
						<li class="changelog-item">
							<div class="changelog-version-heading">
								<span><?php echo esc_html( $ver_title ); ?></span>
								<span class="changelog-version-date"><?php echo esc_html( $ver_date ); ?></span>
							</div>
							<div class="changelog-version-content">
								<?php echo( $ver_content ); ?>
							</div>
						</li>
					<?php endforeach; ?>
					</ul>
				</div>
				<div class="changelog-woostify-pagination ">
					<div class="page-numbers" data-total-pages="<?php echo $changelog_totalpages; ?>" data-per-page="10" data-changelog-product="134">
						<span class="page-pre disable">
							<svg width="6" height="12" viewBox="0 0 6 12" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M4.87226 11.25C4.64531 11.2508 4.43023 11.1487 4.28726 10.9725L0.664757 6.47248C0.437269 6.19573 0.437269 5.79673 0.664757 5.51998L4.41476 1.01998C4.67985 0.701035 5.15331 0.657383 5.47226 0.92248C5.7912 1.18758 5.83485 1.66104 5.56976 1.97998L2.21726 5.99998L5.45726 10.02C5.64453 10.2448 5.68398 10.5579 5.55832 10.8222C5.43265 11.0864 5.16481 11.2534 4.87226 11.25Z" fill="#212B36"/>
							</svg>
						</span>
						<?php
						for ( $page = 1; $page <= $changelog_totalpages; $page++ ) {
							$class_active = ( $page == 1 ) ? 'active' : '';
							if ( $page <= 5 ) {
								$class_active .= ' actived';
							}
							echo '<span class="page-number ' . $class_active . '" data-page-number="' . $page . '">' . $page . '</span>';
						}
						?>
						<span class="dots">...</span>
						<span class="page-next">
							<svg width="6" height="12" viewBox="0 0 6 12" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M1.0001 11.25C0.824863 11.2503 0.655036 11.1893 0.520102 11.0775C0.366721 10.9503 0.270241 10.7673 0.251949 10.5689C0.233657 10.3705 0.295057 10.173 0.422602 10.02L3.7826 5.99996L0.542602 1.97246C0.416774 1.81751 0.3579 1.6188 0.379015 1.42032C0.40013 1.22184 0.499492 1.03996 0.655102 0.914959C0.811977 0.776929 1.01932 0.710602 1.22718 0.731958C1.43504 0.753313 1.62457 0.860415 1.7501 1.02746L5.3726 5.52746C5.60009 5.80421 5.60009 6.20321 5.3726 6.47996L1.6226 10.98C1.47 11.164 1.23878 11.2643 1.0001 11.25Z" fill="#212B36"/>
							</svg>
						</span>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Activate or Deactivated module using ajax.
		 */
		public function woostify_ajax_module_action() {
			check_ajax_referer( 'dashboard_ajax_nonce', 'ajax_nonce' );

			if ( isset( $_POST['name'] ) && isset( $_POST['status'] ) ) {
				$response = array();
				$autoload = 'yes';
				$name     = sanitize_text_field( wp_unslash( $_POST['name'] ) );
				$status   = sanitize_text_field( wp_unslash( $_POST['status'] ) );
				$status   = 'activated' === $status ? 'deactivated' : 'activated';

				if ( ! update_option( $name, $status ) ) {
					global $wpdb;

					$wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $name, $status, $autoload ) ); // phpcs:ignore
					if ( ! wp_installing() ) {
						if ( 'yes' === $autoload ) {
							$alloptions          = wp_load_alloptions( true );
							$alloptions[ $name ] = $status;
							wp_cache_set( 'alloptions', $alloptions, 'options' );
						} else {
							wp_cache_set( $name, $status, 'options' );
						}
					}
				}

				$response['status'] = get_option( $name );

				wp_send_json_success( $response );
			}

			wp_send_json_error();
		}

		/**
		 * Activate or Deactivated all module using ajax.
		 */
		public function woostify_ajax_module_action_all() {
			check_ajax_referer( 'dashboard_ajax_nonce', 'ajax_nonce' );

			if ( isset( $_POST['module_actions'] ) ) {
				$module_actions = json_decode( sanitize_text_field( wp_unslash( $_POST['module_actions'] ) ), true );
				$response       = array();
				$module_names   = array();
				$data           = array();
				$autoload       = 'yes';

				foreach ( $module_actions as $key => $module ) {
					$name   = $module['name'];
					$status = 'activated' === $module['status'] ? 'deactivated' : 'activated';
					$item   = array(
						'name'     => $name,
						'status'   => $status,
						'autoload' => $autoload,
					);
					array_push( $module_names, $name );
					array_push( $data, $item );
				}

				if ( ! empty( $data ) ) {

					foreach ( $data as $key => $row ) {

						if ( ! get_option( $row['name'] ) ) {
							add_option( $row['name'], $row['status'], '', $row['autoload'] );
						}

						update_option( $row['name'], $row['status'] );
					}

					if ( ! wp_installing() ) {
						if ( 'yes' === $autoload ) {
							$alloptions = wp_load_alloptions( true );
							foreach ( $data as $k => $value ) {
								$alloptions[ $value['name'] ] = $value['status'];
							}
							wp_cache_set( 'alloptions', $alloptions, 'options' );
						} else {
							foreach ( $data as $k => $value ) {
								wp_cache_set( $value['name'], $value['status'], 'options' );
							}
						}
					}
				}

				foreach ( $module_names as $key => $name ) {
					$item = array(
						'name'   => $name,
						'status' => get_option( $name ),
					);
					array_push( $response, $item );
				}

				wp_send_json_success( $response );
			}

			wp_send_json_error();
		}

		public function woostify_ajax_changelog_pagination() {
			check_ajax_referer( 'dashboard_ajax_nonce', 'ajax_nonce' );

			if ( isset( $_POST['page'] ) && isset( $_POST['product_id'] ) ) {
				$product_id    = (int) $_POST['product_id'];
				$per_page      = (int) $_POST['per_page'];
				$page          = (int) $_POST['page'];
				$changelog_url = 'https://woostify.com/wp-json/wp/v2/changelog?page=' . $page . '&per_page=' . $per_page . '&product=' . $product_id;
				$request       = wp_remote_get( $changelog_url );

				$check = true;
				if ( is_wp_error( $request ) ) {
					$check = false;
				}

				if ( $check ) {

					$body = wp_remote_retrieve_body( $request );

					$data = json_decode( $body, true );

					wp_send_json_success( $data );

				} else {
					wp_send_json_error();
				}
			}

			wp_send_json_error();

		}

		/**
		 * Detect all featured area activated
		 */
		public function woostify_ajax_all_feature_activated() {
			/*Bail if the nonce doesn't check out*/
			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			$current = get_option( 'woostify_pro_fully_featured_activate' );

			/*Do another nonce check*/
			check_ajax_referer( 'dashboard_ajax_nonce', 'ajax_nonce' );
			$detect = isset( $_POST['detect'] ) ? sanitize_text_field( wp_unslash( $_POST['detect'] ) ) : '';

			if ( $detect !== $current ) {
				update_option( 'woostify_pro_fully_featured_activate', $detect );
			}

			wp_send_json_success();
		}

		/**
		 * Acrivation section
		 */
		public function woostify_activation_section() {
			$license_key  = get_option( 'woostify_pro_license_key', '' );
			$package_name = get_option( 'woostify_pro_package_name', '' );
			// Check again.
			if ( $license_key && $package_name ) {
				$api_params = array(
					'edd_action' => 'check_license',
					'license'    => $license_key,
					'item_name'  => rawurlencode( $package_name ),
					'url'        => home_url(),
				);

				// Connect.
				$connect = wp_remote_post(
					'https://woostify.com',
					array(
						'timeout'   => 60,
						'sslverify' => false,
						'body'      => $api_params,
					)
				);

				$body          = wp_remote_retrieve_body( $connect );
				$body_response = json_decode( $body );

				// Update license status.
				if ( $body_response->success && 'valid' === $body_response->license ) {
					update_option( 'woostify_pro_license_key_status', 'valid' );
				} else {
					update_option( 'woostify_pro_license_key_status', 'invalid' );
				}
			}

			$license_status = get_option( 'woostify_pro_license_key_status' );

			if ( 'valid' === $license_status ) {
				$message = sprintf( '<span class="license-key-message receiving-updates">%s</span>', __( 'Receiving updates', 'woostify-pro' ) );
			} else {
				$message = sprintf( '<span class="license-key-message not-receiving-updates">%s</span>', __( 'Not receiving updates', 'woostify-pro' ) );
			}

			// Hide license key.
			$license_key_lenth = strlen( $license_key );
			$license_key_value = $license_key_lenth > 0 ? str_repeat( '*', $license_key_lenth ) : '';
			?>

			<div class="woostify-enhance__column">
				<div id="woostify-license-keys">
					<h3 class="hndle">
						<?php esc_html_e( 'Your License Key', 'woostify-pro' ); ?>
						<svg width="22" height="24" viewBox="0 0 22 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M13.38 0.637695C17.6738 0.637695 21.155 4.2772 21.155 8.76644C21.155 13.2557 17.6738 16.8952 13.38 16.8952C11.6448 16.898 9.959 16.2913 8.59315 15.1724L6.29952 18.03L7.92035 19.4519C8.02551 19.5443 8.09129 19.6766 8.10325 19.8197C8.1152 19.9627 8.07235 20.1049 7.9841 20.215L7.31753 21.0457C7.22916 21.1557 7.10266 21.2244 6.96581 21.2369C6.82897 21.2494 6.69298 21.2046 6.58771 21.1124L4.96689 19.6904L2.34308 22.9592C2.29932 23.0137 2.24571 23.0587 2.18532 23.0916C2.12493 23.1244 2.05894 23.1445 1.99112 23.1507C1.9233 23.1569 1.85498 23.1491 1.79006 23.1276C1.72514 23.1062 1.66489 23.0716 1.61275 23.0259L0.818665 22.3295C0.766518 22.2838 0.723504 22.2277 0.692077 22.1646C0.660651 22.1014 0.641429 22.0324 0.635509 21.9615C0.629588 21.8906 0.637086 21.8192 0.657573 21.7513C0.67806 21.6835 0.711135 21.6205 0.75491 21.566L7.08532 13.6784C7.10294 13.6567 7.1216 13.6366 7.14181 13.6182C6.14147 12.2171 5.60226 10.5148 5.60495 8.76644C5.60495 4.2772 9.08608 0.637695 13.38 0.637695ZM13.38 2.80536C10.2311 2.80536 7.67829 5.4743 7.67829 8.76644C7.67829 12.0586 10.2311 14.7275 13.38 14.7275C16.5288 14.7275 19.0816 12.0586 19.0816 8.76644C19.0816 5.4743 16.5288 2.80536 13.38 2.80536Z" fill="black"/>
						</svg>
					</h3>

					<div class="wf-quick-setting-section">
						<div class="license-key-container">
							<form method="post" action="options.php" id="woostify_form_check_license">
								<p>
									<input class="widefat woostify-license-key-field" id="woostify_license_key_field" name="woostify_license_key_field" type="<?php echo esc_attr( apply_filters( 'woostify_pro_license_key_field', 'text' ) ); ?>" value="<?php echo esc_attr( $license_key_value ); ?>" placeholder="<?php esc_attr_e( 'Enter your license key here', 'woostify-pro' ); ?>" <?php echo esc_attr( 'valid' === $license_status ? 'disabled' : '' ); ?> />
								</p>
								<?php
								$button_label = 'valid' === $license_status ? __( 'Deactivate', 'woostify-pro' ) : __( 'Activate', 'woostify-pro' );
								?>
								<button type="submit" class="button" id="woostify_pro_license_key_submit" name="woostify_pro_license_key_submit"><?php echo esc_html( $button_label ); ?></button>
							</form>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Return EDD response
		 *
		 * @param string $param The EDD param.
		 */
		public function woostify_get_edd_response( $param = 'item_name' ) {
			$items_name  = $this->woostify_pro_packages();
			$license_key = get_option( 'woostify_pro_license_key', '' );
			$data        = false;

			foreach ( $items_name as $k ) {
				$api_params = array(
					'edd_action' => 'activate_license',
					'license'    => $license_key,
					'item_name'  => rawurlencode( $k ),
					'url'        => home_url(),
				);

				$license_response = wp_remote_post(
					'https://woostify.com',
					array(
						'timeout'   => 60,
						'sslverify' => false,
						'body'      => $api_params,
					)
				);

				$res = json_decode( wp_remote_retrieve_body( $license_response ) );

				if ( $res->success && 'valid' === $res->license ) {
					$data = $res->{$param};
				}
			}

			return $data;
		}

		/**
		 * Process our saved license key.
		 */
		public function woostify_process_license_key() {
			// Do another nonce check.
			check_ajax_referer( 'dashboard_ajax_nonce', 'ajax_nonce' );

			// Bail if the nonce doesn't check out.
			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			// Grab the value being saved.
			$new = isset( $_POST['woostify_license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['woostify_license_key'] ) ) : '';

			// Return if license is empty.
			if ( empty( $new ) ) {
				return;
			}

			// Get the previously saved value.
			$old = get_option( 'woostify_pro_license_key' );

			// Get license status.
			$license_status = get_option( 'woostify_pro_license_key_status' );

			// Items name.
			$items_name = $this->woostify_pro_packages();

			$response = array();
			$license  = 'invalid';
			$success  = false;

			foreach ( $items_name as $name ) {
				// Activate license key.
				$api_params = array(
					'edd_action' => 'activate_license',
					'license'    => $new,
					'item_name'  => rawurlencode( $name ),
					'url'        => home_url(),
				);

				// Deactivate license key.
				if ( 'valid' === $license_status ) {
					$api_params = array(
						'edd_action' => 'deactivate_license',
						'license'    => $old,
						'item_name'  => rawurlencode( $name ),
						'url'        => home_url(),
					);
				}

				// Connect.
				$connect = wp_remote_post(
					'https://woostify.com',
					array(
						'timeout'   => 60,
						'sslverify' => false,
						'body'      => $api_params,
					)
				);

				// Get response.
				$body          = wp_remote_retrieve_body( $connect );
				$response[]    = $body;
				$body_response = json_decode( $body );

				// License activate success.
				if ( $body_response->success && 'valid' === $body_response->license ) {
					$license = 'valid';
					$success = true;
					update_option( 'woostify_pro_package_name', $body_response->item_name );
					update_option( 'woostify_pro_license_key_expires', $body_response->expires );
				}
			}

			// License activate failure.
			if ( ! $success && 'invalid' === $license ) {
				update_option( 'woostify_pro_package_name', '' );
			}

			// Update new license key.
			update_option( 'woostify_pro_license_key', $new );

			// Update license key status.
			update_option( 'woostify_pro_license_key_status', $license );

			// Send json for ajax handle.
			wp_send_json( $response );
		}

		/**
		 * Print admin notices.
		 */
		public function woostify_pro_print_notices() {
			if ( ! defined( 'WOOSTIFY_VERSION' ) || ! current_user_can( 'update_plugins' ) ) {
				return;
			}
			// WOOSTIFY ADMIN NOTICE.
			// Warning if new version of Woostify Theme is available.
			$theme_min_ver = 'detect_new_woostify_version_' . WOOSTIFY_THEME_MIN_VERSION;
			if (
				is_admin() &&
				! get_user_meta( get_current_user_id(), $theme_min_ver ) &&
				version_compare( WOOSTIFY_THEME_MIN_VERSION, WOOSTIFY_VERSION, '>' )
			) {
				?>
				<div class="woostify-admin-notice notice notice-error is-dismissible"
					 data-notice="<?php echo esc_attr( $theme_min_ver ); //phpcs:ignore?>">
					<div class="woostify-notice-content">
						<div class="woostify-notice-text">
							<?php
							$theme_upgrade_link = get_admin_url() . 'themes.php';

							$theme_message  = '<p>' . __( 'A new version of Woostify Theme is available. For better performance and compatibility of Woostify Pro Plugin, we recommend updating to the latest version.', 'woostify-pro' ) . '</p>';
							$theme_message .= '<p>' . sprintf( '<a href="%s" class="button">%s</a>', $theme_upgrade_link, __( 'Update Woostify Now', 'woostify-pro' ) ) . '</p>';

							echo wp_kses_post( $theme_message );
							?>
						</div>
					</div>

					<button type="button" class="notice-dismiss">
						<span class="spinner"></span>
						<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'woostify-pro' ); ?></span>
					</button>
				</div>
				<?php
			}

			// Warning if new version of Woostify PRO is available.
			$plugin_min_ver = 'detect_new_pro_version_' . WOOSTIFY_PRO_MIN_VERSION;
			if (
				is_admin() &&
				defined( 'WOOSTIFY_PRO_MIN_VERSION' ) &&
				! get_user_meta( get_current_user_id(), $plugin_min_ver ) &&
				version_compare( WOOSTIFY_PRO_MIN_VERSION, WOOSTIFY_PRO_VERSION, '>' )
			) {
				?>
				<div class="woostify-admin-notice notice notice-error is-dismissible"
					 data-notice="<?php echo esc_attr( $plugin_min_ver ); //phpcs:ignore ?>">
					<div class="woostify-notice-content">
						<div class="woostify-notice-text">
							<?php
							$plugin_upgrade_link = get_admin_url() . 'update-core.php';

							$plugin_message  = '<p>' . __( 'A new version of Woostify Pro Plugin is available. For better performance and compatibility of Woostify Theme, we recommend updating to the latest version.', 'woostify-pro' ) . '</p>';
							$plugin_message .= '<p>' . sprintf( '<a href="%s" class="button">%s</a>', $plugin_upgrade_link, __( 'Update Woostify Pro Now', 'woostify-pro' ) ) . '</p>';

							echo wp_kses_post( $plugin_message );
							?>
						</div>
					</div>

					<button type="button" class="notice-dismiss">
						<span class="spinner"></span>
						<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'woostify-pro' ); ?></span>
					</button>
				</div>
				<?php
			}
		}

		/**
		 * Index Data.
		 *
		 * @param mixed $post_id The EDD param.
		 * @param array $data The EDD param.
		 */
		public function index_data( $post_id, $data, $meta, $comments, $terms ) {
			if ( class_exists( 'Woostify_Index_Table' ) ) {
				$index = new Woostify_Index_Table();
				$index->import( $post_id, $data );
			}
		}
	}

	Woostify_Pro::get_instance();
}
