<?php
/**
 * Woostify White Label
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_White_Label' ) ) :

	/**
	 * Woostify White Label
	 */
	class Woostify_White_Label {
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

			// JS/ CSS for this page only.
			add_action( 'admin_enqueue_scripts', array( $this, 'woostify_white_label_admin_scripts' ) );

			// Save settings.
			add_action( 'wp_ajax_woostify_save_white_label_options', array( $this, 'save_options' ) );

			// Add Setting url.
			add_action( 'admin_menu', array( $this, 'add_setting_url' ), 10 );
			// Register settings.
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			$this->hook();
		}

		/**
		 * Define constant
		 */
		public function define_constants() {
			if ( ! defined( 'WOOSTIFY_PRO_WHITE_LABEL' ) ) {
				define( 'WOOSTIFY_PRO_WHITE_LABEL', WOOSTIFY_PRO_VERSION );
			}
		}

		/**
		 * Load stylesheet and scripts in White Label options page.
		 *
		 * @param string $hook Hook name.
		 */
		public function woostify_white_label_admin_scripts( $hook ) {

			// General style.
			wp_enqueue_style(
				'woostify-white-label-admin',
				WOOSTIFY_PRO_MODULES_URI . 'white-label/css/admin.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);
			wp_add_inline_style(
				'woostify-white-label-admin',
				$this->wooostify_white_label_custom_css()
			);
			if ( strpos( $hook, 'white-label-settings' ) !== false ) {
				// General style.
				wp_enqueue_style(
					'woostify-white-label-admin',
					WOOSTIFY_PRO_MODULES_URI . 'white-label/css/admin.css',
					array(),
					WOOSTIFY_PRO_VERSION
				);
				wp_enqueue_media();
				wp_enqueue_script(
					'woostify-white-label-admin',
					WOOSTIFY_PRO_MODULES_URI . 'white-label/js/whitelabel-backend' . woostify_suffix() . '.js',
					array( 'jquery' ),
					WOOSTIFY_PRO_VERSION,
					true
				);
				wp_localize_script(
					'woostify-white-label-admin',
					'woostify_white_label_admin',
					array(
						'i18n'       => array(
							'mediaTitle'  => esc_html__( 'Choose an image', 'woostify-pro' ),
							'mediaButton' => esc_html__( 'Use image', 'woostify-pro' ),
						),
						'defaultScr' => defined( 'WOOSTIFY_THEME_URI' ) ? WOOSTIFY_THEME_URI . 'assets/images/logo.svg' : '',
					)
				);

			}

		}


		/**
		 * Save options
		 */
		public function save_options() {
			if ( ! current_user_can( 'edit_theme_options' ) ) {
				wp_send_json_error();
			}

			$setting_id = isset( $_POST['setting_id'] ) ? sanitize_text_field( wp_unslash( $_POST['setting_id'] ) ) : '';
			$nonce      = 'woostify-' . $setting_id . '-setting-nonce';
			check_ajax_referer( $nonce, 'security_nonce' );

			$options = isset( $_POST['options'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['options'] ) ), true ) : array();

			if ( ! empty( $options ) ) {
				$array = array();

				foreach ( $options as $k => $v ) {
					$value = sanitize_textarea_field( wp_unslash( $v ) );

					if ( false !== strpos( $k, '[]' ) ) {
						array_push( $array, $value );

						// Get option name.
						$name = strstr( $k, '[', true ) . '[]';

						update_option( $name, implode( '@_sn', $array ) );
					} else {
						update_option( $k, $value );
					}
				}
			}

			// Update dynamic css.
			if ( class_exists( 'Woostify_Get_CSS' ) ) {
				$get_css = new Woostify_Get_CSS();
				$get_css->delete_dynamic_stylesheet_folder();
			}

			wp_send_json_success();
		}

		/**
		 * Add submenu
		 *
		 * @see  add_submenu_page()
		 */
		public function add_setting_url() {
			$sub_menu = add_submenu_page( 'woostify-welcome', 'Settings', __( 'White Label', 'woostify-pro' ), 'manage_options', 'white-label-settings', array( $this, 'add_settings_page' ) );
		}

		/**
		 * Register settings
		 */
		public function register_settings() {
			register_setting( 'white-label-settings', 'woostify_white_label_agency_author' );
			register_setting( 'white-label-settings', 'woostify_white_label_agency_author_url' );
			register_setting( 'white-label-settings', 'woostify_white_label_agency_author_link' );
			register_setting( 'white-label-settings', 'woostify_white_label_theme_name' );
			register_setting( 'white-label-settings', 'woostify_white_label_theme_description' );
			register_setting( 'white-label-settings', 'woostify_white_label_theme_screenshot_url' );
			register_setting( 'white-label-settings', 'woostify_white_label_plugin_name' );
			register_setting( 'white-label-settings', 'woostify_white_label_plugin_description' );
			register_setting( 'white-label-settings', 'woostify_white_label_hide_branding' );

			// Site library.
			register_setting( 'white-label-settings', 'woostify_white_label_library_plugin_name' );
			register_setting( 'white-label-settings', 'woostify_white_label_library_plugin_description' );
			register_setting( 'white-label-settings', 'woostify_white_label_library_plugin_summary' );
		}

		/**
		 * Get options
		 */
		public function get_options() {
			$options                                     = array();
			$options['woostify_wl_agency_author']        = get_option( 'woostify_white_label_agency_author', '' );
			$options['woostify_wl_agency_author_url']    = get_option( 'woostify_white_label_agency_author_url', '' );
			$options['woostify_wl_agency_author_link']   = get_option( 'woostify_white_label_agency_author_link', '' );
			$options['woostify_wl_theme_name']           = get_option( 'woostify_white_label_theme_name', '' );
			$options['woostify_wl_theme_description']    = get_option( 'woostify_white_label_theme_description', '' );
			$options['woostify_wl_theme_screenshot_url'] = get_option( 'woostify_white_label_theme_screenshot_url', '' );
			$options['woostify_wl_plugin_name']          = get_option( 'woostify_white_label_plugin_name', '' );
			$options['woostify_wl_plugin_description']   = get_option( 'woostify_white_label_plugin_description', '' );
			$options['woostify_wl_hide_branding']        = get_option( 'woostify_white_label_hide_branding', 0 );
			$options['woostify_wl_library_plugin_name']  = get_option( 'woostify_white_label_library_plugin_name', '' );
			$options['woostify_wl_library_plugin_description'] = get_option( 'woostify_white_label_library_plugin_description', '' );
			$options['woostify_wl_library_plugin_summary']     = get_option( 'woostify_white_label_library_plugin_summary', '' );
			$options['woostify_wl_logo_attachment']            = get_option( 'woostify_white_label_logo_attachment', '' );

			return $options;
		}

		/**
		 * Create Settings page
		 */
		public function add_settings_page() {
			$options = $this->get_options();
			?>
			<div class="woostify-options-wrap woostify-featured-setting woostify-white-label-setting" data-id="white-label" data-nonce="<?php echo esc_attr( wp_create_nonce( 'woostify-white-label-setting-nonce' ) ); ?>">

				<?php Woostify_Admin::get_instance()->woostify_save_option_messages(); ?>

				<?php Woostify_Admin::get_instance()->woostify_welcome_screen_header(); ?>

				<div class="wrap woostify-settings-box">
					<div class="woostify-welcome-container">
						<div class="woostify-notices-wrap">
							<h2 class="notices" style="display:none;"></h2>
						</div>
						<div class="woostify-settings-content">
							<h4 class="woostify-settings-section-title"><?php esc_html_e( 'White Label', 'woostify-pro' ); ?></h4>

							<div class="woostify-settings-section-content">
								<!-- General. -->
								<table class="form-table woostify-setting-tab-content" data-tab="general">
									<tr>
										<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Agency Details', 'woostify-pro' ); ?></th>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Agency Author', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_white_label_agency_author">
												<input class="field-empty" name="woostify_white_label_agency_author" type="text" id="woostify_white_label_agency_author" value="<?php echo esc_attr( $options['woostify_wl_agency_author'] ); ?>">
											</label>
										</td>
									</tr>

									<tr>
										<th scope="row"><?php esc_html_e( 'Agency Author URL', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_white_label_agency_author_url">
												<input class="field-empty" name="woostify_white_label_agency_author_url" type="text" id="woostify_white_label_agency_author_url" value="<?php echo esc_attr( $options['woostify_wl_agency_author_url'] ); ?>">
											</label>
										</td>
									</tr>

									<tr>
										<th scope="row"><?php esc_html_e( 'Agency Licence Link', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_white_label_agency_author_link">
												<input class="field-empty" name="woostify_white_label_agency_author_link" type="text" id="woostify_white_label_agency_author_link" value="<?php echo esc_attr( $options['woostify_wl_agency_author_link'] ); ?>">
											</label>
										</td>
									</tr>

									<tr>
										<th scope="row"><?php esc_html_e( 'Agency Logo', 'woostify-pro' ); ?>:</th>
										<td>
											<!-- <label for="woostify_white_label_logo_attachment">
												<input class="field-empty" name="woostify_white_label_logo_attachment" type="text" id="woostify_white_label_logo_attachment" value="<?php echo esc_attr( $options['woostify_wl_logo_attachment'] ); ?>">
											</label> -->
											<div class="row agency-logo-row">
												<a href="javascript:void(0);" class="upload_image_button tips" id="woostify_white_label_logo_select"><?php esc_html_e( 'Click here to select a logo', 'woostify' ); ?></a>
												<?php
												$class  = 'hide';
												$src    = defined( 'WOOSTIFY_THEME_URI' ) ? WOOSTIFY_THEME_URI . 'assets/images/logo.svg' : '';
												$remove = '<a class="remove hide" href="#"></a>';
												if ( $options['woostify_wl_logo_attachment'] ) {
													$attachment_id = $options['woostify_wl_logo_attachment'];
													$wl_src        = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
													$src           = ! empty( $wl_src ) ? $wl_src[0] : $src;
													$class         = '';
													$remove        = '<a class="remove" href="#"></a>';

												}
												?>
												<div class="clearfix"></div>
												<div class="while-label-logo-image <?php echo esc_attr( $class ); ?>">
												<?php if ( $src ) { ?>
													<img width="100" height="100" class="while-label-logo-image <?php echo esc_attr( $class ); ?>" src="<?php echo esc_url( $src ); ?>" />
													<?php echo wp_kses_post( $remove ); ?>
												<?php } ?>
												</div>
												<input type="hidden" name="woostify_white_label_logo_attachment" class="upload_image_id" id="woostify_white_label_logo_attachment" value="<?php echo esc_attr( $options['woostify_wl_logo_attachment'] ); ?>" />
											</div>
										</td>
									</tr>

									<tr>
										<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Woostify Theme Branding', 'woostify-pro' ); ?></th>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Theme Name', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_white_label_theme_name">
												<input  class="field-empty" name="woostify_white_label_theme_name" type="text" id="woostify_white_label_theme_name" value="<?php echo esc_attr( $options['woostify_wl_theme_name'] ); ?>">
											</label>
										</td>
									</tr>

									<tr>
										<th scope="row"><?php esc_html_e( 'Theme Description', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_white_label_theme_description">
												<textarea class="field-empty" name="woostify_white_label_theme_description" id="woostify_white_label_theme_description"><?php echo esc_attr( $options['woostify_wl_theme_description'] ); ?></textarea>
											</label>
										</td>
									</tr>

									<tr>
										<th scope="row"><?php esc_html_e( 'Theme Screenshot URL', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_white_label_theme_screenshot_url">
												<input class="field-empty" name="woostify_white_label_theme_screenshot_url" type="text" id="woostify_white_label_theme_screenshot_url" value="<?php echo esc_attr( $options['woostify_wl_theme_screenshot_url'] ); ?>">
											</label>
										</td>
									</tr>

									<tr>
										<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Woostify Pro Branding', 'woostify-pro' ); ?></th>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Plugin Name', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_white_label_plugin_name">
												<input class="field-empty" name="woostify_white_label_plugin_name" type="text" id="woostify_white_label_plugin_name" value="<?php echo esc_attr( $options['woostify_wl_plugin_name'] ); ?>">
											</label>
										</td>
									</tr>

									<tr>
										<th scope="row"><?php esc_html_e( 'Plugin Description', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_white_label_plugin_description">
												<textarea class="field-empty" name="woostify_white_label_plugin_description" id="woostify_white_label_plugin_description"><?php echo esc_attr( $options['woostify_wl_plugin_description'] ); ?></textarea>
											</label>
										</td>
									</tr>

									<tr>
										<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Woostify Site Library Branding', 'woostify-pro' ); ?></th>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Plugin Name', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_white_label_plugin_name">
												<input class="field-empty" name="woostify_white_label_library_plugin_name" type="text" id="woostify_white_label_library_plugin_name" value="<?php echo esc_attr( $options['woostify_wl_library_plugin_name'] ); ?>">
											</label>
										</td>
									</tr>

									<tr>
										<th scope="row"><?php esc_html_e( 'Plugin Description', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_white_label_plugin_description">
												<textarea class="field-empty" name="woostify_white_label_library_plugin_description" id="woostify_white_label_library_plugin_description"><?php echo esc_attr( $options['woostify_wl_library_plugin_description'] ); ?></textarea>
											</label>
										</td>
									</tr>

									<tr>
										<th scope="row"><?php esc_html_e( 'Summary text', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_white_label_plugin_description">
												<textarea class="field-empty" name="woostify_white_label_library_plugin_summary" id="woostify_white_label_library_plugin_summary"><?php echo esc_attr( $options['woostify_wl_library_plugin_summary'] ); ?></textarea>
											</label>
										</td>
									</tr>

									<tr>
										<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'White Label Settings', 'woostify-pro' ); ?></th>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Hide Branding', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_white_label_hide_branding">
												<input name="woostify_white_label_hide_branding" type="checkbox" id="woostify_white_label_hide_branding" value="<?php echo esc_attr( $options['woostify_wl_hide_branding'] ); ?>"  <?php checked( $options['woostify_wl_hide_branding'], '1' ); ?> >
												<span class="checkbox-descriptons">
													<?php esc_html_e( 'Enable this option will hide Woostify Whitelabel add-on. You need to disable and enable Woostify Pro to show this add-on again.' ); ?>
												</span>
											</label>
										</td>
									</tr>
								</table>
								<!-- END General. -->
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
		 * Include required core files used in admin and on the frontend.
		 */
		public function hook() {
			register_deactivation_hook( WOOSTIFY_PRO_FILE, array( $this, 'woostify_white_label_show_branding' ) );
			$options = $this->get_options();
			if ( is_admin() ) {
				add_filter( 'wp_prepare_themes_for_js', array( $this, 'woostify_white_label_themes_page' ) );
				add_filter( 'all_plugins', array( $this, 'woostify_white_label_plugins_page' ) );
				add_filter( 'update_right_now_text', array( $this, 'woostify_white_label_admin_dashboard_page' ) );
				add_filter( 'woostify_custom_site_library_label', array( $this, 'woostify_while_label_custom_site_library_label' ), 10, 3 );
				add_filter( 'woostify_site_library_custom_summary', array( $this, 'woostify_while_label_site_library_summary' ), 10, 3 );
				add_filter( 'woostify_theme_custom_logo_src', array( $this, 'woostify_while_label_custom_logo_src' ), 10, 3 );
				add_action( 'customize_render_section', array( $this, 'woostify_white_label_theme_customizer' ) );
				if ( '' !== $options['woostify_wl_theme_name'] ) {
					add_filter( 'gettext', array( $this, 'woostify_white_label_theme_gettext' ), 20, 3 );
				}
				if ( '' !== $options['woostify_wl_plugin_name'] ) {
					add_filter( 'gettext', array( $this, 'woostify_white_label_plugin_gettext' ), 20, 3 );
				}

				if ( ! $this->woostify_show_branding() ) {
					add_action( 'admin_menu', array( $this, 'woostify_white_label_hide_remove_menu_pages' ) );
					if ( has_action( 'woostify_change_log_tab_menu' ) ) {
						remove_all_actions( 'woostify_change_log_tab_menu' );
					}
					add_action( 'admin_notices', array( $this, 'woostify_white_label_hide_settings' ) );
					remove_all_actions( 'woostify_welcome_panel_sidebar' );
				}
			}
		}

		/**
		 * Custom CSS for logo menu item
		 */
		public function wooostify_white_label_custom_css() {
			$options = $this->get_options();
			if ( empty( $options['woostify_wl_logo_attachment'] ) ) {
				return '';
			}
			$attachment_id = $options['woostify_wl_logo_attachment'];
			$src           = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
			if ( empty( $src ) ) {
				return '';
			}
			$src        = $src[0];
			$inline_css = '.menu-top#toplevel_page_woostify-welcome .wp-menu-image:before { content: ""; background: url(' . $src . ') center no-repeat; background-size: contain; transform: rotate(0deg); }';
			return $inline_css;
		}

		/**
		 * Custom Site Library Label button.
		 *
		 * @param string $label | Current label.
		 */
		public function woostify_while_label_custom_site_library_label( $label ) {
			$options = $this->get_options();
			if ( empty( $options['woostify_wl_library_plugin_name'] ) ) {
				return $label;
			}
			return esc_html( 'Activate' ) . ' ' . $options['woostify_wl_library_plugin_name'];
		}

		/**
		 * Custom Wooostify Theme Logo
		 *
		 * @param string $logo | Current logo src.
		 */
		public function woostify_while_label_custom_logo_src( $logo ) {
			$options = $this->get_options();
			if ( empty( $options['woostify_wl_logo_attachment'] ) ) {
				return $logo;
			}
			$attachment_id = $options['woostify_wl_logo_attachment'];
			$src           = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
			$src           = ! empty( $src ) ? $src[0] : $logo;

			return esc_html( $src );
		}

		/**
		 * Custom Site Library Label button.
		 *
		 * @param string $label | Current label.
		 */
		public function woostify_while_label_site_library_summary( $label ) {
			$options = $this->get_options();
			if ( empty( $options['woostify_wl_library_plugin_summary'] ) ) {
				return $label;
			}
			return esc_html( $options['woostify_wl_library_plugin_summary'] );
		}

		/**
		 * Disable Hide branding when deactive the plugin
		 */
		public function woostify_white_label_show_branding() {
			$options = $this->get_options();
			if ( isset( $options['woostify_wl_hide_branding'] ) ) {
				update_option( 'woostify_white_label_hide_branding', 0 );
			}
		}

		/**
		 * Remove white label setting for Woostify Welcome Page
		 */
		public function woostify_white_label_hide_settings() {
			global $submenu, $menu, $pagenow;
			$screen = get_current_screen();
			if ( current_user_can( 'manage_options' ) ) {
				if ( $pagenow == 'admin.php' && $screen->id == 'toplevel_page_woostify-welcome' ) {
					?>
					<script type='text/javascript'>
						jQuery(function($) {
							$("#module-id-woostify_white_label").closest('.module-item').remove();
						});
					</script>
					<?php
				}
			}
		}

		/**
		 * Remove menu white label
		 */
		public function woostify_white_label_hide_remove_menu_pages() {
			remove_submenu_page( 'woostify-welcome', 'white-label-settings' );
		}

		/**
		 * White labels the theme on the themes page.
		 *
		 * @param array $themes Themes Array.
		 * @return array
		 */
		public function woostify_white_label_themes_page( $themes ) {
			$options      = $this->get_options();
			$woostify_key = 'woostify';

			if ( isset( $themes[ $woostify_key ] ) ) {
				if ( '' !== $options['woostify_wl_theme_name'] ) {

					$themes[ $woostify_key ]['name'] = $options['woostify_wl_theme_name'];

					foreach ( $themes as $key => $theme ) {
						if ( isset( $theme['parent'] ) && 'Woostify' === $theme['parent'] ) {
							$themes[ $key ]['parent'] = $options['woostify_wl_theme_name'];
						}
					}
				}

				if ( '' !== $options['woostify_wl_theme_description'] ) {
					$themes[ $woostify_key ]['description'] = $options['woostify_wl_theme_description'];
				}

				if ( '' !== $options['woostify_wl_agency_author'] ) {
					$author_url                              = ( '' === $options['woostify_wl_agency_author_url'] ) ? '#' : $options['woostify_wl_agency_author_url'];
					$themes[ $woostify_key ]['author']       = $options['woostify_wl_agency_author'];
					$themes[ $woostify_key ]['authorAndUri'] = '<a href="' . esc_url( $author_url ) . '">' . $options['woostify_wl_agency_author'] . '</a>';
				}

				if ( '' !== $options['woostify_wl_theme_screenshot_url'] ) {
					$themes[ $woostify_key ]['screenshot'] = array( $options['woostify_wl_theme_screenshot_url'] );
				}
			}

			return $themes;
		}

		/**
		 * White labels the plugins page.
		 *
		 * @param array $plugins Plugins Array.
		 * @return array
		 */
		public function woostify_white_label_plugins_page( $plugins ) {
			$options = $this->get_options();
			$key     = plugin_basename( WOOSTIFY_PRO_PATH . 'woostify-pro.php' );

			if ( isset( $plugins[ $key ] ) && false !== $options['woostify_wl_plugin_name'] ) {
				$plugins[ $key ]['Name']        = $options['woostify_wl_plugin_name'];
				$plugins[ $key ]['Description'] = $options['woostify_wl_plugin_description'];
			}

			$author     = $options['woostify_wl_agency_author'];
			$author_uri = $options['woostify_wl_agency_author_url'];

			if ( ! empty( $author ) ) {
				$plugins[ $key ]['Author']     = $author;
				$plugins[ $key ]['AuthorName'] = $author;
			}

			if ( ! empty( $author_uri ) ) {
				$plugins[ $key ]['AuthorURI'] = $author_uri;
				$plugins[ $key ]['PluginURI'] = $author_uri;
			}

			// White labels the Woostify Site Library plugins page.
			$key = 'woostify-sites-library/woostify-sites.php';

			if ( isset( $plugins[ $key ] ) && false !== $options['woostify_wl_library_plugin_name'] ) {
				$plugins[ $key ]['Name']        = $options['woostify_wl_library_plugin_name'];
				$plugins[ $key ]['Description'] = $options['woostify_wl_library_plugin_description'];
			}
			// END White labels the Woostify Site Library plugins page.

			return $plugins;
		}

		/**
		 * White labels the theme on the dashboard 'At a Glance' metabox
		 *
		 * @param mixed $content Content.
		 * @return array
		 */
		public function woostify_white_label_admin_dashboard_page( $content ) {
			$options = $this->get_options();
			if ( is_admin() && 'Woostify' === wp_get_theme() && '' !== $options['woostify_wl_theme_name'] ) {
				return sprintf( $content, get_bloginfo( 'version', 'display' ), '<a href="themes.php">' . $options['woostify_wl_theme_name'] . '</a>' );
			}

			return $content;
		}

		/**
		 * White labels the builder theme using the `customize_render_section` hook
		 * to cover areas that we can't access like the Customizer.
		 *
		 * @param object $section  Woostify Object.
		 * @return string           Only return if theme branding has been filled up.
		 */
		public function woostify_white_label_theme_customizer( $section ) {
			$options = $this->get_options();
			if ( 'Woostify' === $section->title ) {
				if ( '' !== $options['woostify_wl_theme_name'] ) {
					$section->title = $options['woostify_wl_theme_name'];
					return $section->title;
				}
			}
		}

		/**
		 * White labels the theme using the gettext filter
		 * to cover areas that we can't access like the Customizer.
		 *
		 * @param string $text  Translated text.
		 * @param string $original         Text to translate.
		 * @param string $domain       Text domain. Unique identifier for retrieving translated strings.
		 * @return string
		 */
		public function woostify_white_label_theme_gettext( $text, $original, $domain ) {
			$options = $this->get_options();
			// $text    = str_replace( 'Woostify', $options['woostify_wl_theme_name'], $text );
			if ( 'Woostify' === $original ) {
				$text = $options['woostify_wl_theme_name'];
			}

			return $text;
		}

		/**
		 * White labels the plugin using the gettext filter
		 * to cover areas that we can't access.
		 *
		 * @param string $text  Translated text.
		 * @param string $original   Text to translate.
		 * @param string $domain       Text domain. Unique identifier for retrieving translated strings.
		 * @return string
		 */
		public function woostify_white_label_plugin_gettext( $text, $original, $domain ) {
			$options = $this->get_options();
			// $text    = str_replace( 'Woostify Pro', $options['woostify_wl_plugin_name'], $text );

			if ( 'Woostify Pro' === $original ) {
				$text = $options['woostify_wl_plugin_name'];
			}
			if ( 'Woostify Pro %s' === $original ) {
				$text = $options['woostify_wl_plugin_name'] . ' %s';
			}
			if ( 'Woostify Options' === $original ) {
				$text = $options['woostify_wl_plugin_name'] . ' ' . esc_html__( 'Options', 'woostify-pro' );
			}

			return $text;
		}

		/**
		 * Show white label.
		 *
		 * @return bool true | false
		 */
		public function woostify_show_branding() {
			$options       = $this->get_options();
			$show_branding = true;

			if ( true === (bool) $options['woostify_wl_hide_branding'] ) {
				$show_branding = false;
			}

			if ( defined( 'WOOSTIFY_PRO_WP_WHITE_LABEL' ) && WOOSTIFY_PRO_WP_WHITE_LABEL ) {
				$show_branding = false;
			}

			return apply_filters( 'woostify_addon_show_branding', $show_branding );
		}

	}

	Woostify_White_Label::get_instance();
endif;
