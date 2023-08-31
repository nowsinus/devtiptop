<?php
/**
 * Woostify CallBack FrontEnd
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_CallBack_Frontend' ) ) {
	/**
	 * Class Woostify CallBack FrontEnd
	 */
	class Woostify_CallBack_Frontend {
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
		 * Class constructor.
		 */
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_filter( 'woostify_customizer_css', array( $this, 'inline_styles' ), 47 );
			remove_action( 'woocommerce_after_shop_loop_item', 'woostify_loop_product_add_to_cart_button', 10 );
			add_action( 'woocommerce_after_shop_loop_item', array( $this, 'woostify_loop_product_add_to_cart_button_callback' ), 10 );
			remove_action( 'woostify_product_loop_item_action_item', 'woostify_product_loop_item_add_to_cart_icon', 10 );
			add_action( 'woostify_product_loop_item_action_item', array( $this, 'woostify_product_loop_item_add_to_cart_icon_callback' ), 10 );
			remove_action( 'woocommerce_before_shop_loop_item_title', 'woostify_loop_product_add_to_cart_on_image', 70 );
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'woostify_loop_product_add_to_cart_on_image_callback' ), 70 );
			add_action( 'woocommerce_before_single_product_summary', array( $this, 'change_add_to_cart_with_box_callback' ) );
			add_filter( 'woocommerce_variation_is_active', array( $this, 'callback_enable_disabled_variation_dropdown' ), 100, 2 );
			add_filter( 'woocommerce_available_variation', array( $this, 'callback_display_in_variation' ), 999, 3 );

			add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'callback_add_to_cart_button_add_class_variation' ), 0 );
		}

		/**
		 * Replace "add_to_cart" button by "callback" box.
		 */
		public function change_add_to_cart_with_box_callback() {
			$product_id        = get_the_ID();
			$product_id_option = Woostify_CallBack::get_instance()->get_product_ids();
			if ( in_array( $product_id, $product_id_option ) ) {
				remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
				remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
				remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
				add_action( 'woocommerce_simple_add_to_cart', array( $this, 'display_simple_product' ), 30 );
				add_action( 'woocommerce_grouped_add_to_cart', array( $this, 'display_simple_product' ), 30 );
				add_action( 'woocommerce_external_add_to_cart', array( $this, 'display_simple_product' ), 30 );
			}
		}

		/**
		 * Callback Display In No Variation Product
		 */
		public function callback_add_to_cart_button_add_class_variation() {
			global $product;
			$product_id_option = Woostify_CallBack::get_instance()->get_product_ids();
			if ( $product->is_type( 'variable' ) ) :
				$data = array();
				// Loop through variation Ids.
				foreach ( $product->get_visible_children() as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					// $data[$variation_id] = $variation->is_in_stock();
					if ( in_array( $variation_id, $product_id_option ) ) {
						$data[ $variation_id ] = false;
					} else {
						$data[ $variation_id ] = true;
					}
				}
				?>
				<script type="text/javascript">
				jQuery(function($){
					var b = '.woocommerce-variation-add-to-cart';

					$('form.variations_form').on('show_variation hide_variation found_variation', function(){
						$.each(<?php echo wp_json_encode( $data ); ?>, function(j, r){
							var i = $('input[name="variation_id"]').val();
							if(j == i && i != 0 && !r ) {
								$(b).addClass('out-of-stock');
								return false;
							} else {
								$(b).removeClass('out-of-stock');
							}
						});
					});
				});
				</script>
				<?php
			endif;
		}

		/**
		 * Enqueue scripts and stylesheets
		 */
		public function enqueue_scripts() {
			wp_register_style(
				'woostify-callback',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/callback/css/frontend.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);

			wp_register_script(
				'woostify-callback',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/callback/js/frontend' . woostify_suffix() . '.js',
				array(),
				WOOSTIFY_PRO_VERSION,
				true
			);

			wp_localize_script(
				'woostify-callback',
				'woostify_callback',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'security' => wp_create_nonce( 'woostify_callback_security' ),
				)
			);

			wp_register_script(
				'google-recaptcha',
				'https://www.google.com/recaptcha/api.js',
				array(),
				WOOSTIFY_PRO_VERSION,
				true
			);

			wp_enqueue_style( 'woostify-callback' );
			wp_enqueue_script( 'woostify-callback' );
			wp_enqueue_script( 'google-recaptcha' );
		}

		/**
		 * Add dynamic style to theme customize styles
		 *
		 * @param string $styles Customize styles.
		 *
		 * @return string
		 */
		public function inline_styles( $styles ) {
			$options = Woostify_CallBack::get_instance()->get_options();

			// Style.
			$form_sc_border_color = $options['form_sc_border_color'] ? 'border-color: ' . $options['form_sc_border_color'] . ';' : '';

			$form_sc_title_color    = $options['form_sc_title_color'] ? 'color: ' . $options['form_sc_title_color'] . ';' : '';
			$form_sc_title_bg_color = $options['form_sc_title_bg_color'] ? 'background-color: ' . $options['form_sc_title_bg_color'] . ';' : '';

			$form_btn_color              = $options['form_btn_color'] ? 'color: ' . $options['form_btn_color'] . ';' : '';
			$form_btn_bg_color           = $options['form_btn_bg_color'] ? 'background-color: ' . $options['form_btn_bg_color'] . ';' : '';
			$form_btn_border_color       = $options['form_btn_border_color'] ? 'border-color: ' . $options['form_btn_border_color'] . ';' : '';
			$form_btn_color_hover        = $options['form_btn_color_hover'] ? 'color: ' . $options['form_btn_color_hover'] . ';' : '';
			$form_btn_bg_color_hover     = $options['form_btn_bg_color_hover'] ? 'background-color: ' . $options['form_btn_bg_color_hover'] . ';' : '';
			$form_btn_border_color_hover = $options['form_btn_border_color_hover'] ? 'border-color: ' . $options['form_btn_border_color_hover'] . ';' : '';

			$form_btn_pu_color              = $options['form_btn_pu_color'] ? 'color: ' . $options['form_btn_pu_color'] . ';' : '';
			$form_btn_pu_bg_color           = $options['form_btn_pu_bg_color'] ? 'background-color: ' . $options['form_btn_pu_bg_color'] . ';' : '';
			$form_btn_pu_border_color       = $options['form_btn_pu_border_color'] ? 'border-color: ' . $options['form_btn_pu_border_color'] . ';' : '';
			$form_btn_pu_color_hover        = $options['form_btn_pu_color_hover'] ? 'color: ' . $options['form_btn_pu_color_hover'] . ';' : '';
			$form_btn_pu_bg_color_hover     = $options['form_btn_pu_bg_color_hover'] ? 'background-color: ' . $options['form_btn_pu_bg_color_hover'] . ';' : '';
			$form_btn_pu_border_color_hover = $options['form_btn_pu_border_color_hover'] ? 'border-color: ' . $options['form_btn_pu_border_color_hover'] . ';' : '';

			$styles .= '
			    /* VARIANT SWATCHES */
                .woostify-callback-form-inner {
                    border-radius: ' . $options['form_sc_border_radius'] . 'px;
                    border: 1px solid;
					' . $form_sc_border_color . '
                }
                .woostify-callback-form-inner .panel-heading {
                    ' . $form_sc_title_color . $form_sc_title_bg_color . '
					font-size: ' . $options['form_sc_title_font_size'] . 'px;
					font-weight: ' . $options['form_sc_title_font_weight'] . ';
                }
				.woostify-callback-form-inner .callback_product_button {
                    ' . $form_btn_color . $form_btn_bg_color . $form_btn_border_color . '
					width: ' . $options['form_btn_width'] . ';
					height: ' . $options['form_btn_height'] . 'px;
                }
				.woostify-callback-form-inner .callback_product_button:hover {
					' . $form_btn_color_hover . $form_btn_bg_color_hover . $form_btn_border_color_hover . '
                }
				#btn-callback-form-popup {
                    ' . $form_btn_pu_color . $form_btn_pu_bg_color . $form_btn_pu_border_color . '
					width: ' . $options['form_btn_pu_width'] . ';
					height: ' . $options['form_btn_pu_height'] . 'px;
                }
				#btn-callback-form-popup:hover {
                    ' . $form_btn_pu_color_hover . $form_btn_pu_bg_color_hover . $form_btn_pu_border_color_hover . '
                }
			';

			return $styles;
		}

		/**
		 * Loop product add to cart button
		 */
		public function woostify_loop_product_add_to_cart_button_callback() {
			global $product;
			$options = Woostify_CallBack::get_instance()->get_options();

			$woostify_options = woostify_options( false );
			if ( in_array( $woostify_options['shop_page_add_to_cart_button_position'], array( 'none', 'image', 'icon' ), true ) ) {
				return;
			}

			$product_id        = $product->get_id();
			$product_id_option = Woostify_CallBack::get_instance()->get_product_ids();
			if ( in_array( $product_id, $product_id_option ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				$link = $product->get_permalink();
				echo '<a href="' . esc_url( $link ) . '" class="loop-add-to-cart-btn button">' . esc_html( $options['form_btn_label'] ) . '</a>';
			} else {
				woostify_modified_add_to_cart_button();
			}
		}

		/**
		 * Product add to cart ( On image )
		 */
		public function woostify_loop_product_add_to_cart_on_image_callback() {
			global $product;
			$options = Woostify_CallBack::get_instance()->get_options();

			$woostify_options = woostify_options( false );
			if ( 'image' !== $woostify_options['shop_page_add_to_cart_button_position'] ) {
				return;
			}

			$product_id        = $product->get_id();
			$product_id_option = Woostify_CallBack::get_instance()->get_product_ids();
			if ( in_array( $product_id, $product_id_option ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				$link = $product->get_permalink();
				echo '<a href="' . esc_url( $link ) . '" class="loop-add-to-cart-on-image button">' . esc_html( $options['form_btn_label'] ) . '</a>';
			} else {
				woostify_modified_add_to_cart_button();
			}
		}

		/**
		 * Add to cart icon
		 */
		public function woostify_product_loop_item_add_to_cart_icon_callback() {
			global $product;
			$options = Woostify_CallBack::get_instance()->get_options();

			$woostify_options = woostify_options( false );
			if ( 'icon' !== $woostify_options['shop_page_add_to_cart_button_position'] ) {
				return;
			}

			$product_id        = $product->get_id();
			$product_id_option = Woostify_CallBack::get_instance()->get_product_ids();
			if ( in_array( $product_id, $product_id_option ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				$link = $product->get_permalink();
				echo '<a href="' . esc_url( $link ) . '" class="loop-add-to-cart-icon-btn button" title="' . esc_html( $options['form_btn_label'] ) . '">' . Woostify_Icon::fetch_svg_icon( 'shopping-cart-2', false ) . '</a>';
			} else {
				woostify_modified_add_to_cart_button();
			}
		}

		/**
		 * Check Stock Status
		 *
		 * @param  object $product  The product.
		 */
		public function check_stock_status( $product ) {
			if ( method_exists( $product, 'get_stock_status' ) ) {
				$stock_status = $product->get_stock_status(); // For version 3.0+.
			} else {
				$stock_status = $product->stock_status; // Older than version 3.0.
			}
			return $stock_status;
		}

		/**
		 * Callback Display In Variation
		 *
		 * @param  string $atts  The post id.
		 * @param  object $product  The product.
		 * @param  array  $variation  The variation.
		 */
		public function callback_display_in_variation( $atts, $product, $variation ) {
			// $stock_status = $variation ? $variation->get_stock_status() : $product->get_stock_status();
			$variation_id      = $variation->get_id();
			$product_id_option = Woostify_CallBack::get_instance()->get_product_ids();
			$get_stock         = $atts['availability_html'];
			if ( in_array( $variation_id, $product_id_option ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				$atts['availability_html'] = $get_stock . do_shortcode( $this->display_callback_box_variation( $product, $variation, true ) );
			}
			return $atts;
		}

		/**
		 * Callback Enable Disabled Variation Dropdown.
		 *
		 * @param  boolean $active  The active.
		 * @param  array   $variation  The variation.
		 */
		public function callback_enable_disabled_variation_dropdown( $active, $variation ) {
			$variation_id      = $variation->get_id();
			$product_id_option = Woostify_CallBack::get_instance()->get_product_ids();
			if ( in_array( $variation_id, $product_id_option ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				$active = true;
			}
			return $active;
		}

		/**
		 * Display Call Back Simple Product.
		 */
		public function display_simple_product() {
			global $product;
			echo do_shortcode( $this->display_callback_box( $product, array(), true ) );
		}

		/**
		 * Display Call Back Box.
		 *
		 * @param  object  $product  The product.
		 * @param  array   $variation  The variation.
		 * @param  boolean $display  The display.
		 */
		public function display_callback_box( $product, $variation = array(), $display = true ) {
			$options           = Woostify_CallBack::get_instance()->get_options();
			$product_id        = $product->get_id();
			$product_id_option = Woostify_CallBack::get_instance()->get_product_ids();
			ob_start();
			if ( $display ) {
				if ( in_array( $product_id, $product_id_option ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					$this->form_callback( $product, $variation );
				}
			}
			return ob_get_clean();
		}

		/**
		 * Display Call Back Box Variation.
		 *
		 * @param  object  $product  The product.
		 * @param  array   $variation  The variation.
		 * @param  boolean $display  The display.
		 */
		public function display_callback_box_variation( $product, $variation = array(), $display = true ) {
			ob_start();
			if ( $display ) {
				$this->form_callback( $product, $variation );
			}
			return ob_get_clean();
		}

		/**
		 * Form Call Back.
		 *
		 * @param  object $product  The product.
		 * @param  array  $variation  The variation.
		 */
		public function form_callback( $product, $variation = array() ) {
			$options           = Woostify_CallBack::get_instance()->get_options();
			$form_display_type = 'woostify-callback-form';
			if ( $options['form_display_type'] == '2' ) { // phpcs:ignore
				$form_display_type = 'woostify-callback-form-quick-view';
			}

			$callback_email = $callback_name = $callback_phone_number = ''; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
			if ( is_user_logged_in() ) {
				$current_user          = wp_get_current_user();
				$callback_email        = $current_user->user_email;
				$callback_name         = $current_user->user_firstname . ' ' . $current_user->user_lastname;
				$callback_phone_number = $current_user->billing_phone;
			}

			if ( $variation ) {
				$variation_id = $variation->get_id();
			} else {
				$variation_id = 0;
			}

			?>
				<?php if ( $options['form_display_type'] == '2' ) : // phpcs:ignore  ?>
				<button id="btn-callback-form-popup" class="button btn-callback-form-popup"><?php echo esc_html( $options['form_btn_label'] ); ?></button>
				<?php endif; ?>
				<section id="<?php echo esc_attr( $form_display_type ); ?>" class="woostify-callback-form ">
					<div class="woostify-callback-form-inner">
						<div class="panel-heading callback-panel-heading"><?php echo esc_html( $options['form_sc_title'] ); ?></div>
						<div class="panel-body callback-panel-body">
							<div class="form-input grouped_form" data-name-hide="<?php echo esc_attr( $options['hide_name'] ); ?>" data-show-phone="<?php echo esc_attr( $options['show_phone'] ); ?>" data-show-agree="<?php echo esc_attr( $options['show_agree'] ); ?>">
								<?php if ( $options['hide_name'] != 1 ) : ?>
								<input type="text" class="callback-name" name="callback_name" placeholder="<?php echo esc_attr( $options['form_sc_ph_name'] ); ?>" value="<?php echo esc_attr( $callback_name ); ?>" />
								<?php endif; ?>
								<input type="email" class="callback-email" name="callback_email" placeholder="<?php echo esc_attr( $options['form_sc_ph_email'] ); ?>" value="<?php echo esc_attr( $callback_email ); ?>" />                                            
								<?php if ( $options['show_phone'] == 1 ) : ?>
								<input type="phone" class="callback-phone-number" name="callback_phone_number" placeholder="<?php echo esc_attr( $options['form_sc_ph_phone'] ); ?>" value="<?php echo esc_attr( $callback_phone_number ); ?>" />                                    
								<?php endif; ?>
																<?php if ( $options['show_agree'] == 1 ) : ?>
								<div class="callback-agree-checkbox">
									<label for="callback_agree_checkbox_input">
										<input type="checkbox" class="callback-agree-checkbox-input" name="callback_agree_checkbox_input" id="callback_agree_checkbox_input" value="<?php echo esc_attr( $options['show_agree'] ); ?>"/>
																	<?php echo Woostify_CallBack_Helper::get_instance()->replace_tags( $options['show_agree_text'] ); ?>
									</label>
								</div>
								<?php endif; ?>
								<input type="hidden" name="callback_product_id" value="<?php echo esc_attr( $product->get_id() ); ?>" />
								<input type="hidden" name="callback_variation_id" value="<?php echo esc_attr( $variation_id ); ?>" />
								<?php if ( $options['form_sc_m_enable_recaptcha'] && $options['sitekeyrecaptcha'] != '' ) : ?>
								<div class="g-recaptcha" id="rcaptcha" data-sitekey="<?php echo esc_attr( $options['sitekeyrecaptcha'] ); ?>" data-messages-error="<?php echo esc_attr( $options['form_sc_m_recaptcha'] ); ?>"></div>
								<?php endif; ?>
								<input type="submit" class="button callback_product_button" value="<?php echo esc_html( $options['form_btn_label'] ); ?>" />
							</div>
							<div class="callback_product_output_error error"></div>
						</div>
					</div>
				</section>
			<?php
		}
	}

	Woostify_CallBack_Frontend::get_instance();
}
