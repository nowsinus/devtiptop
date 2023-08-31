<?php
/**
 * Woostify Pre Order Front End
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_Pre_Order_Frontend' ) ) {
	/**
	 * Class Woostify Pre Order Front End
	 */
	class Woostify_Pre_Order_Frontend {
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
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 99 );
			add_filter( 'woostify_customizer_css', array( $this, 'inline_styles' ), 47 );

			add_action( 'woocommerce_single_product_summary', array( $this, 'pre_order_render_single_product' ), 26 );
			add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'pre_order_add_to_cart_change_text' ) );
			add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'pre_order_add_to_cart_change_text' ) );
			add_filter( 'woocommerce_get_price_html', array( $this, 'pre_order_change_price_product_html' ), 10, 2 );
			add_filter( 'woocommerce_quantity_input_args', array( $this, 'pre_order_quantity_maximum_order' ), 9999, 2 );

			add_filter( 'woocommerce_available_variation', array( $this, 'pre_order_quantity_variation_maximum_order' ), 9999, 3 );
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'pre_order_update_maximum_order' ), 10, 1 );
			add_action( 'woocommerce_before_calculate_totals', array( $this, 'pre_order_change_product_price_cart' ), 10, 1 );
			add_filter( 'woocommerce_cart_item_price', array( $this, 'pre_order_display_product_price_cart' ), 100, 3 );
			remove_action( 'woocommerce_before_shop_loop_item_title', 'woostify_change_sale_flash', 23 );
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'preorder_change_sale_text' ), 23 );
			$options = woostify_options( false );
			$gallery = $options['shop_single_product_gallery_layout_select'];
			if ( 'theme' === $gallery ) {
				remove_action( 'woostify_product_images_box_end', 'woostify_change_sale_flash', 10 );
				add_action( 'woostify_product_images_box_end', array( $this, 'preorder_change_sale_text' ), 10 );
			} else {
				remove_action( 'woocommerce_before_single_product_summary', 'woostify_change_sale_flash', 25 );
				add_action( 'woocommerce_before_single_product_summary', array( $this, 'preorder_change_sale_text' ), 25 );
			}
			add_action( 'woocommerce_shop_loop_item_title', array( $this, 'preorder_add_template_loop_product_title' ), 11 );
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'pre_order_add_to_cart_validation' ), 10, 5 );
			add_filter( 'woocommerce_is_purchasable', array( $this, 'preorder_woocommerce_cloudways_purchasable' ) );
			//add_action( 'wp_loaded', array( $this, 'preorder_woocommerce_cloudways_purchasable' ) );
		}

		/**
		 * Enqueue scripts and stylesheets
		 */
		public function enqueue_scripts() {
			wp_register_style(
				'woostify-pre-order',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/pre-order/css/style.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);

			wp_register_script(
				'woostify-pre-order',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/pre-order/js/script' . woostify_suffix() . '.js',
				array(),
				WOOSTIFY_PRO_VERSION,
				true
			);
			wp_enqueue_style( 'woostify-pre-order' );
            wp_enqueue_script( 'woostify-pre-order' );
		}

		/**
		 * Add dynamic style to theme customize styles
		 *
		 * @param string $styles Customize styles.
		 *
		 * @return string
		 */
		public function inline_styles( $styles ) {
			$options = Woostify_Pre_Order::get_instance()->get_options();

			// Style.
			$styles .= '
			    /* VARIANT SWATCHES */
				.woostify-tag-on-preorder.onsale {			
					color: ' . $options['label_text_color'] . ';
					background-color: ' . $options['label_background_color'] . ';
					border-radius: ' . $options['label_border_radius'] . 'px;
					font-size: ' . $options['label_font_size'] . 'px;
				}
				.woostify-tag-on-preorder.onsale.sale-none {
					display:none;
				}
				.woostify-preorder-message {
					color: ' . $options['messages_color'] . ';
					font-size: ' . $options['messages_font_size'] . 'px;
					font-weight: ' . $options['messages_font_weight'] . ';
				}
			';

			return $styles;
		}


		/**
		 * Pre Order on Single Product
		 */
		public function pre_order_render_single_product() {
			$options    = Woostify_Pre_Order::get_instance()->get_options();
			$product_id = get_the_ID();

			$onpreorder_date_from     = get_post_meta( $product_id, '_onpreorder_date_from', true );
			$onpreorder_date_to       = get_post_meta( $product_id, '_onpreorder_date_to', true );
			$onpreorder_maximum_order = get_post_meta( $product_id, '_onpreorder_maximum_order', true );

			$message      = Woostify_Pre_Order::get_instance()->get_message();
			$stock_status = get_post_meta( $product_id, '_stock_status', true );
			if ( $this->check_stock_status() == true ) :
				?>
				<div class="woostify-pre-order-product">
					<?php if( $options['countdown'] ) : ?>
					<div class="woostify-countdown-preorder-wrap">
						<?php if ( $options['countdown_label'] != '' ) : ?>
						<div class="woostify-countdown-preorder-lablel">
							<div class="woostify-countdown-preorder-lablel-text"><?php echo esc_attr( $options['countdown_label'] ); ?></div>
						</div>
						<?php endif; ?>

						<div class="woostify-countdown-preorder" data-date-to="<?php echo esc_attr( $onpreorder_date_to ); ?>" data-closed-label="<?php echo esc_attr( $options['closed_label'] ); ?>" data-check-order="<?php echo esc_attr( $onpreorder_maximum_order ); ?>">                               
							<div class="woostify-preorder-timer-item">
								<div class="woostify-preorder-timer woostify-preorder-timer-days"></div>                            
								<div class="woostify-preorder-timer-label"><?php echo esc_html( $options['days_label'] ); ?></div>                            
							</div>
							<div class="woostify-preorder-timer-item">
								<div class="woostify-preorder-timer woostify-preorder-timer-hours"></div>                            
								<div class="woostify-preorder-timer-label"><?php echo esc_html( $options['hours_label'] ); ?></div>                            
							</div>
							<div class="woostify-preorder-timer-item">
								<div class="woostify-preorder-timer woostify-preorder-timer-minutes"></div>                            
								<div class="woostify-preorder-timer-label"><?php echo esc_html( $options['minutes_label'] ); ?></div>                            
							</div>
							<div class="woostify-preorder-timer-item">
								<div class="woostify-preorder-timer woostify-preorder-timer-seconds"></div>                            
								<div class="woostify-preorder-timer-label"><?php echo esc_html( $options['seconds_label'] ); ?></div>                            
							</div>                          
						</div>
					</div>
					<?php endif; ?>

					<?php if ( $this->check_stock_status() == true && $this->check_maximum_order() == true && $this->check_date_counter() == true ) : ?>
						<?php if ( $onpreorder_maximum_order ) : ?>
							<div class="woostify-preorder-maximum-order"><?php echo esc_html__( 'Remaining Item/s: ', 'woostify-pro' ) . '<strong>' . esc_attr( $onpreorder_maximum_order ) . '</strong>'; ?></div>
						<?php endif; ?>

						<div class="woostify-preorder-message">
							<?php if ( $options['message'] != '' ) : ?>
								<div class="title"><?php echo wp_kses_post( $message ); ?></div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
				<?php
			endif;
		}

		public function preorder_woocommerce_cloudways_purchasable() {
			if ( $this->check_stock_status() == true ) {
				if ( $this->check_stock_status() == true && $this->check_maximum_order() == true && $this->check_date_counter() == true ) {
					return true;
				} else {
					return false;
				}
			} else {
				return true;
			}
		}

		/**
		 * Pre Order Change Text Button
		 */
		public function pre_order_add_to_cart_change_text() {
			if ( $this->check_stock_status() == true && $this->check_maximum_order() == true && $this->check_date_counter() == true ) {
				return esc_html__( 'Pre-order', 'woostify-pro' );
			} else {
				return esc_html__( 'Add to cart', 'woostify-pro' );
			}
		}

		/**
		 * Pre Order Change Price Display
		 */
		public function pre_order_change_price_product_html( $price_html, $product ) {
			if ( $this->check_stock_status() == true && $this->check_maximum_order() > 0 && $this->check_date_counter() == true ) {
				$onpreorder_price = get_post_meta( $product->get_id(), '_onpreorder_price', true );
				$price            = $product->get_regular_price();
				if ( $product->is_type( 'simple' ) ) {
					$price_html = '';
					if ( $product->is_on_sale() ) {
						$sale = $product->get_sale_price();
						if ( $price && $onpreorder_price ) {
							$sale = wc_format_decimal( $onpreorder_price );
						}
						$price_html .= '<del>' . ( ( is_numeric( $price ) ) ? wc_price( $price ) : $price ) . '</del>
											<ins>' . ( ( is_numeric( $sale ) ) ? wc_price( $sale ) : $sale ) . '</ins>';
					} else {
						if ( $price && $onpreorder_price ) {
							$price = wc_format_decimal( $onpreorder_price );
						}
						$price_html .= '<ins>' . ( ( is_numeric( $price ) ) ? wc_price( $price ) : $price ) . '</ins>';
					}
					$price_html .= '';
					return $price_html;
				} elseif ( $product->is_type( 'variable' ) ) {
					return $price_html;
				} else {
					return $price_html;
				}
			}
			return $price_html;
		}

		/**
		 * Pre Order limit quantity
		 */
		public function pre_order_quantity_maximum_order( $args, $product ) {
			$stock_status             = get_post_meta( $product->get_id(), '_stock_status', true );
			$onpreorder_maximum_order = get_post_meta( $product->get_id(), '_onpreorder_maximum_order', true );
			$date_now                 = current_datetime()->format( 'n/j/Y H:i:s' );
			$onpreorder_date_to       = get_post_meta( $product->get_id(), '_onpreorder_date_to', true );
			$to                       = new DateTime( $onpreorder_date_to );
			$date_to                  = $to->format( 'n/j/Y 23:59:00' );

			if ( 'onpreorder' === $stock_status && $onpreorder_maximum_order > 0 && strtotime( $date_now ) < strtotime( $date_to ) ) {
				$args['max_value'] = $onpreorder_maximum_order; // Max quantity (default = -1)
				$args['step']      = 1;
			}
			return $args;
		}

		/**
		 * Pre Order limit quantity variation
		 */
		public function pre_order_quantity_variation_maximum_order( $args, $product, $variation ) {
			$stock_status             = get_post_meta( $product->get_id(), '_stock_status', true );
			$onpreorder_maximum_order = get_post_meta( $product->get_id(), '_onpreorder_maximum_order', true );
			$date_now                 = current_datetime()->format( 'n/j/Y H:i:s' );
			$onpreorder_date_to       = get_post_meta( $product->get_id(), '_onpreorder_date_to', true );
			$to                       = new DateTime( $onpreorder_date_to );
			$date_to                  = $to->format( 'n/j/Y 23:59:00' );

			if ( 'onpreorder' === $stock_status && $onpreorder_maximum_order > 0 && strtotime( $date_now ) < strtotime( $date_to ) ) {
				$product_id               = $product->get_product_id();
				$onpreorder_maximum_order = get_post_meta( $product_id, '_onpreorder_maximum_order', true );
				$args['max_qty']          = $onpreorder_maximum_order;
			}
			return $args;
		}

		/**
		 * Pre Order update maximum order when order sucsess
		 */
		public function pre_order_update_maximum_order( $order_id ) {
			$order      = new WC_Order( $order_id );
			$order_item = $order->get_items();
			$date_now   = current_datetime()->format( 'n/j/Y H:i:s' );
			foreach ( $order_item as $item ) {
				$product_id               = $item->get_product_id();
				$product                  = $item->get_product();
				$stock_status             = get_post_meta( $product_id, '_stock_status', true );
				$onpreorder_maximum_order = get_post_meta( $product_id, '_onpreorder_maximum_order', true );
				$onpreorder_date_to       = get_post_meta( $product_id, '_onpreorder_date_to', true );
				$to                       = new DateTime( $onpreorder_date_to );
				$date_to                  = $to->format( 'n/j/Y 23:59:00' );

				if ( 'onpreorder' === $stock_status && $onpreorder_maximum_order > 0 && strtotime( $date_now ) < strtotime( $date_to ) ) {
					$pre_order_maximum_order_new = $onpreorder_maximum_order - $item->get_quantity();
					$product->update_meta_data( '_onpreorder_maximum_order', esc_attr( $pre_order_maximum_order_new ) );
					$product->save();
				}
			}
		}

		/**
		 * Pre Order change price cart
		 */
		public function pre_order_change_product_price_cart( $cart ) {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}

			if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
				return;
			}

			foreach ( $cart->get_cart() as $cart_item ) {
				$product                  = $cart_item['data'];
				$product_id               = $product->get_id();
				$stock_status             = get_post_meta( $product_id, '_stock_status', true );
				$onpreorder_maximum_order = get_post_meta( $product_id, '_onpreorder_maximum_order', true );
				$date_now                 = current_datetime()->format( 'n/j/Y H:i:s' );
				$onpreorder_date_to       = get_post_meta( $product_id, '_onpreorder_date_to', true );
				$to                       = new DateTime( $onpreorder_date_to );
				$date_to                  = $to->format( 'n/j/Y 23:59:00' );

				if ( 'onpreorder' === $stock_status && $onpreorder_maximum_order > 0 && strtotime( $date_now ) < strtotime( $date_to ) ) {
					// check if is simple product
					if ( $product->is_type( 'simple' ) ) {
						$new_price = get_post_meta( $product_id, '_onpreorder_price', true );
						$new_price = wc_format_decimal( $new_price );
						// WooCommerce versions compatibility
						if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
							$cart_item['data']->price = $new_price; // Before WC 3.0
						} else {
							$cart_item['data']->set_price( $new_price ); // WC 3.0+
						}
					} else { // if product has variations
						$parent_id = $product->get_parent_id(); // The parent product ID
						$new_price = get_post_meta( $parent_id, '_onpreorder_price', true );
						$new_price = wc_format_decimal( $new_price );
						if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
							$cart_item['data']->price = $new_price; // Before WC 3.0
						} else {
							$cart_item['data']->set_price( $new_price ); // WC 3.0+
						}
					}
				}
			}
		}

		/**
		 * Pre Order change price cart display
		 */
		public function pre_order_display_product_price_cart( $price, $cart_item, $cart_item_key ) {
				$product                  = $cart_item['data'];
				$product_id               = $product->get_id();
				$stock_status             = get_post_meta( $product_id, '_stock_status', true );
				$onpreorder_maximum_order = get_post_meta( $product_id, '_onpreorder_maximum_order', true );
				$date_now                 = current_datetime()->format( 'n/j/Y H:i:s' );
				$onpreorder_date_to       = get_post_meta( $product_id, '_onpreorder_date_to', true );
				$to                       = new DateTime( $onpreorder_date_to );
				$date_to                  = $to->format( 'n/j/Y 23:59:00' );

			if ( 'onpreorder' === $stock_status && $onpreorder_maximum_order > 0 && strtotime( $date_now ) < strtotime( $date_to ) ) {
				// check if is simple product
				if ( $product->is_type( 'simple' ) ) {
					$new_price = get_post_meta( $product_id, '_onpreorder_price', true );
					$new_price = wc_format_decimal( $new_price );
					return wc_price( $new_price );
				} else { // if product has variations
					$parent_id = $product->get_parent_id(); // The parent product ID
					$new_price = get_post_meta( $parent_id, '_onpreorder_price', true );
					$new_price = wc_format_decimal( $new_price );
					return wc_price( $new_price );
				}
			}
				return wc_price( $product->get_price() );
		}

		/**
		 * Pre Order sale text
		 */
		public function preorder_change_sale_text() {
			if ( $this->check_stock_status() == true && $this->check_maximum_order() > 0 && $this->check_date_counter() == true ) {
				$options_preorder = Woostify_Pre_Order::get_instance()->get_options();
				$options          = woostify_options( false );
				$classes[]        = 'woostify-tag-on-preorder onsale';
				$classes[]        = 'sale-' . $options_preorder['label_position'];
				$classes[]        = $options['shop_page_sale_square'] ? 'is-square' : '';
				?>
				<span class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>">
					<?php echo esc_html( $options_preorder['label'] ); ?>
				</span>
				<?php
			} else {
				woostify_change_sale_flash();
			}
		}

		/**
		 * Pre Order add template availabel Pre-Order after title product
		 */
		public function preorder_add_template_loop_product_title() {
			if ( $this->check_stock_status() == true && $this->check_maximum_order() > 0 ) {
				$product_id         = get_the_ID();
				$options            = Woostify_Pre_Order::get_instance()->get_options();
				$onpreorder_date_to = get_post_meta( $product_id, '_onpreorder_date_to', true );
				$message            = Woostify_Pre_Order::get_instance()->get_message();
				if ( $this->check_date_counter() == true ) {
				?>
					<div class="woostify-preorder-message">
						<?php if ( $options['message'] != '' ) : ?>
							<div class="title"><?php echo wp_kses_post( $message ); ?></div>
						<?php endif; ?>
					</div>
				<?php } else { ?>
					<div class="woostify-preorder-message">
						<?php if ( $options['closed_label'] != '' ) : ?>
							<div class="title"><?php echo wp_kses_post( $options['closed_label'] ); ?></div>
						<?php endif; ?>
					</div>
				<?php }
			}
		}

		/**
		 * Pre Order check stock status
		 */
		public function check_stock_status() {
			$product = wc_get_product();

			if ( ! empty( $product ) ) {
				$product_id   = $product->get_id();
				$stock_status = get_post_meta( $product_id, '_stock_status', true );
				if ( 'onpreorder' === $stock_status ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Pre Order check maximum order
		 */
		public function check_maximum_order() {
			$product                  = wc_get_product();
			$product_id               = $product->get_id();
			$onpreorder_maximum_order = get_post_meta( $product_id, '_onpreorder_maximum_order', true );
			if ( $onpreorder_maximum_order > 0 ) {
				return true;
			}
			return false;
		}

		/**
		 * Pre Order check date counter
		 */
		public function check_date_counter() {
			$product            = wc_get_product();
			$product_id         = $product->get_id();
			$date_now           = current_datetime()->format( 'n/j/Y H:i:s' );
			$onpreorder_date_to = get_post_meta( $product_id, '_onpreorder_date_to', true );
			$to                 = new DateTime( $onpreorder_date_to );
			$date_to            = $to->format( 'n/j/Y 23:59:00' );

			if ( strtotime( $date_now ) < strtotime( $date_to ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Pre Order add to cart validation
		 */
		public function pre_order_add_to_cart_validation( $passed, $product_id, $quantity ){
			$cart_items_count = WC()->cart->get_cart_contents_count();
			$total_count      = $cart_items_count + $quantity;
			if( get_post_meta( $product_id, '_stock_status', true ) == 'onpreorder' ){
				$product_quantity = get_post_meta( $product_id, '_onpreorder_maximum_order', true );
				if ( $cart_items_count >= $product_quantity || $total_count > $product_quantity ){
					// Set to false
					$passed = false;
					wc_add_notice( sprintf( __( 'You can’t add more than %s items in cart', 'woostify-pro' ), $product_quantity ), 'error' );
				}
			}
			return $passed;
		}

	}

	Woostify_Pre_Order_Frontend::get_instance();
}
