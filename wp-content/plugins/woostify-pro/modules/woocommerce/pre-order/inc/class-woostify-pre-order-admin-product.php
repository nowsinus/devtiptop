<?php
/**
 * Woostify Pre Order Admin Product
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_Pre_Order_Admin_Product' ) ) {
	/**
	 * Woostify Pre Order Admin Product
	 */
	class Woostify_Pre_Order_Admin_Product {
		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->init_hooks();
		}

		/**
		 * Initialize hooks
		 */
		public function init_hooks() {
			add_filter( 'woocommerce_product_stock_status_options', array( $this, 'add_stock_status_options' ) );
			add_filter( 'woocommerce_get_availability_text', array( $this, 'filter_woocommerce_get_availability_text' ), 10, 2 );
			add_filter( 'woocommerce_admin_stock_html', array( $this, 'filter_woocommerce_admin_stock_html' ), 10, 2 );
			add_action( 'woocommerce_product_options_stock_status', array( $this, 'add_fields_pre_order' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'fields_pre_order_save' ), 10, 2 );
		}

		public function 	add_stock_status_options( $status ) {
			$status['onpreorder'] = esc_html__( 'On preorder', 'woostify-pro' );
			return $status;
		}

		public function filter_woocommerce_get_availability_text( $availability, $product ) {
			// Get stock status
			switch( $product->get_stock_status() ) {
				case 'onpreorder':
					$availability = esc_html__( 'Pre order', 'woostify-pro' );
				break;
			}
		
			return $availability; 
		}

		public function filter_woocommerce_admin_stock_html( $stock_html, $product ) {
			if($product->get_stock_status()) {
				// Stock status
				switch( $product->get_stock_status() ) {
					case 'onpreorder':
						$stock_html = '<mark class="pre-order">' . esc_html__( 'Pre order', 'woostify-pro' ). '</mark>';
					break;
				}
			}
		 
			return $stock_html;
		}

		public function add_fields_pre_order(){
			$product = wc_get_product();
			?>
			<div class="options_group options_group_preorder form-row form-row-full <?php echo esc_attr( 'onpreorder' === $product->get_stock_status() ? '' : 'hidden' ); ?>">
				<?php
					woocommerce_wp_text_input(
						array(
							'id'          => '_onpreorder_maximum_order',
							'label'       => esc_html__( 'Allow maximum order', 'woostify-pro' ),
							'class'       => 'short',
							'desc_tip'    => true,
							'value'       => get_post_meta( get_the_ID(), '_onpreorder_maximum_order', true ),
							'type'        => 'number',
							'custom_attributes' => array(
								'step'  => 'any',
								'min'   => '0'
							),
						)
					);       
					woocommerce_wp_text_input(
						array(
							'id'          => '_onpreorder_date_to',
							'label'       => esc_html__( 'Pre Order Date', 'woostify-pro' ),
							'placeholder' => date( 'n/j/Y' ),
							'class'       => 'datepicker short',
							'desc_tip'    => true,
							'value'       => get_post_meta( get_the_ID(), '_onpreorder_date_to', true ),
						)
					);
					woocommerce_wp_text_input(
						array(
							'id'          => '_onpreorder_price',
							'label'       => esc_html__( 'Pre Order Price', 'woostify-pro' ),
							'class'       => 'short',
							'desc_tip'    => true,
							'value'       => get_post_meta( get_the_ID(), '_onpreorder_price', true ),
						)
					);
				?>
			</div>
			<?php
		}

		public function fields_pre_order_save( $post_id ) {
			$product = wc_get_product( $post_id );

			if ( $_POST['_onpreorder_maximum_order'] ) {
				$pre_order_maximum_order = esc_html( $_POST['_onpreorder_maximum_order'] );
				$product->update_meta_data( '_onpreorder_maximum_order', esc_attr( $pre_order_maximum_order ) );               
			} else {
				$product->update_meta_data( '_onpreorder_maximum_order', 0 );
			}

			if ( $_POST['_onpreorder_date_to'] ) {
				$pre_order_date_value_to = esc_html( $_POST['_onpreorder_date_to'] );
				$product->update_meta_data( '_onpreorder_date_to', esc_attr( $pre_order_date_value_to ) );               
			} else {
				$product->update_meta_data( '_onpreorder_date_to', date( 'n/j/Y' ) );
			}

			if ( $_POST['_onpreorder_price'] ) {
				$pre_order_price = esc_html( $_POST['_onpreorder_price'] );
				$product->update_meta_data( '_onpreorder_price', esc_attr( $pre_order_price ) );               
			} else {
				$product->update_meta_data( '_onpreorder_price', $product->get_price() );
			}
	
			$product->save();
		}

	}

	new Woostify_Pre_Order_Admin_Product();
}
