<?php
/**
 * Woostify CallBack Helper
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_CallBack_Helper' ) ) :

	/**
	 * Class Woostify CallBack Helper
	 */
	class Woostify_CallBack_Helper {
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
		}

		/**
		 * Get list of subscribers.
		 *
		 * @param  int $product_id      product id.
		 */
		public function get_list_of_subscribers( $product_id ) {
			$args = array(
				'post_type'      => 'callback',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'post_status'    => 'cb_subscribed',
			);

			$product = wc_get_product( $product_id );
			$proid = $product->get_parent_id();
			$variation_id = 0;
			if( $proid > 0 ) {
				$variation_id = $product_id;
				$args['meta_query'] = array( //phpcs:ignore
					array(
						'key' => 'woostify_callback_variation_id',
						'value' => ( $variation_id > '0' || $variation_id > 0 ) ? $variation_id : 'no_data_found',
					),
				);
			}else {
				$args['meta_query'] = array( //phpcs:ignore
					array(
						'key'   => 'woostify_callback_product_id',
						'value' => $product_id,
					),
				);
			}			

			$get_posts = get_posts( $args );

			return $get_posts;
		}

		/**
		 * Display product name.
		 *
		 * @param  int $id      id.
		 */
		public function display_product_name( $id ) {
			$pid  = get_post_meta( $id, 'woostify_callback_product_id', true );
			$pvid = get_post_meta( $id, 'woostify_callback_variation_id', true );
			if ( $pvid > 0 ){
				$pid = $pvid;
			}			
			
			$formatted_name = '';
			if ( $pid ) {
				$obj = wc_get_product( $pid );
				if ( $obj ) {
					$formatted_name = $obj->get_formatted_name();
				}
			}
			return $formatted_name;
		}

		/**
		 * Display product link.
		 *
		 * @param  int $id      id.
		 */
		public function display_product_link( $id ) {
			$permalink = '';
			$pid       = get_post_meta( $id, 'woostify_callback_product_id', true );
			$product   = wc_get_product( $pid );
			if ( $product ) {
				$permalink = $product->get_permalink();
			}
			return $permalink;
		}

		/**
		 * Get subscriber mail.
		 *
		 * @param  int $subscriber_id      subscriber id.
		 */
		public function get_subscriber_mail( $subscriber_id ) {
			$subscriber_mail = get_post_meta( $subscriber_id, 'woostify_callback_email', true );
			return $subscriber_mail;
		}

		/**
		 * Get subscriber name.
		 *
		 * @param  int $subscriber_id      subscriber id.
		 */
		public function get_subscriber_name( $subscriber_id ) {
			$subscriber_name = get_post_meta( $subscriber_id, 'woostify_callback_name', true );
			return $subscriber_name;
		}

		/**
		 * Get subscriber phone.
		 *
		 * @param  int $subscriber_id      subscriber id.
		 */
		public function get_subscriber_phone ( $subscriber_id ) {
			$subscriber_phone = get_post_meta( $subscriber_id, 'woostify_callback_phone', true );
			return $subscriber_phone;
		}

		/**
		 * Get cart link.
		 *
		 * @param  int $subscriber_id      subscriber id.
		 */
		public function get_cart_link ( $subscriber_id ) {
			$pid = get_post_meta( $subscriber_id, 'woostify_callback_product_id', true );	
			$pvid = get_post_meta( $subscriber_id, 'woostify_callback_variation_id', true );			
			if( $pvid > 0 ) {
				$pid = $pvid;
			}
			$url = '';
			if ( $pid ) {
				$object = wc_get_product( $pid );
				if ( $object ) {
					$url = $object->add_to_cart_url();					
					if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
						$get_permalink = $object->get_permalink();
						if ( $object->is_type( 'variation' ) ) {
							$get_parent_id = $object->get_parent_id();
							$query_arg = array( 'variation_id' => $pid, 'add-to-cart' => $get_parent_id );
						} else {
							$query_arg = array( 'add-to-cart' => $pid );
						}
						$url = esc_url_raw( add_query_arg( $query_arg, $get_permalink ) );
					}
				}
			}
			return $url;
		}

		public function replace_tags( $content ) {
			$find_array        = array( '{','}' );
			$replace_array     = array( '<','>' );
			$formatted_content = str_replace( $find_array, $replace_array, $content );
			return $formatted_content;
		}
	}

endif;