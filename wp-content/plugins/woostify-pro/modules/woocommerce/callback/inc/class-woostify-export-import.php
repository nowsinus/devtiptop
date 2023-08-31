<?php
/**
 * Woostify CallBack Export Import
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_CallBack_Export_Import' ) ) :

	/**
	 * Class Woostify CallBack Export Import
	 */
	class Woostify_CallBack_Export_Import {
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
			add_action( 'manage_posts_extra_tablenav', array( $this, 'admin_order_list_top_bar_button' ) );
			add_action( 'admin_init', array( $this, 'woostify_callBack_export_subscribed' ) );
			add_action( 'admin_notices', array( $this, 'woostify_callBack_form_import_subscribed' ) );
			add_action( 'admin_init', array( $this, 'woostify_callBack_import_subscribed' ) );
		}

		public function admin_order_list_top_bar_button ( $which ) {
			global $post_type;
			if ( 'callback' === $post_type && 'top' === $which ) {
				?>
				<div class="alignleft actions custom">
					<input type="submit" name="woostify_callBack_export_subscribed" class="button button-primary" value="<?php echo __( 'Subscribed Export CSV', 'woostify-pro' ); ?>" />
				</div>
				<?php
			}
		}

		/**
		 * Woostify callBack export subscribed
		 */
		public function woostify_callBack_export_subscribed() {
			if(isset($_GET['woostify_callBack_export_subscribed'])) {
				$args = array(
					'post_type' => 'callback',
					'post_status' => array( 'cb_subscribed','cb_mailsent','cb_unsubscribed' ),
				);
		 
				if ( isset($_GET['post']) ) {
					$args['post__in'] = $_GET['post'];
				} else {
					$args['posts_per_page'] = -1;
				}
		  
				global $post;
				$arr_post = get_posts($args);
				if ($arr_post) {
					$helper = Woostify_CallBack_Helper::get_instance();
		  
					header('Content-type: text/csv');
					header('Content-Disposition: attachment; filename="callback-export-subscribed.csv"');
					header('Pragma: no-cache');
					header('Expires: 0');
		  
					$file = fopen('php://output', 'w');
		  
					fputcsv($file, array('id', 'email', 'name', 'phone', 'product_id', 'variation_id', 'status'));
		  
					foreach ($arr_post as $post) {
						setup_postdata($post);
						$email        = get_post_meta( get_the_ID(), 'woostify_callback_email', true );
						$name         = get_post_meta( get_the_ID(), 'woostify_callback_name', true );
						$phone        = get_post_meta( get_the_ID(), 'woostify_callback_phone', true );
						$product_id   = get_post_meta( get_the_ID(), 'woostify_callback_product_id', true );
						$variation_id = get_post_meta( get_the_ID(), 'woostify_callback_variation_id', true );
		  
						fputcsv( $file, array( get_the_ID(), $email, $name, $phone, $product_id, $variation_id, get_post_status() ) );
					}
		  
					exit();
				}
			}
		}

		public function woostify_callBack_form_import_subscribed() {
			$screen = get_current_screen();
			if( $screen->id !='edit-callback' ) {
				return;
			}
			?>
			<div class='updated'>
				<p>
					<form method='post' enctype="multipart/form-data">
						<input type="file" name="woostify_callBack_upload_subscribed" value="" aria-required="true" accept=".csv">
						<input type="submit" name="woostify_callBack_import_subscribed" class="button button-primary" value="<?php echo __( 'Subscribed Import CSV', 'woostify-pro' ); ?>" />
					</form>
				</p>
			</div>
			<?php
		}
		
		/**
		 * Create and insert posts from CSV files
		 */
		public function woostify_callBack_import_subscribed() {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			global $wpdb;
			
			if ( ! isset( $_POST["woostify_callBack_import_subscribed"] ) ) {
				return;
			}
			if ( ! file_exists( $_FILES['woostify_callBack_upload_subscribed']['tmp_name'] ) ) {
				return;
			}
			
			$callback_args = array(
				"custom-post-type" => "callback"
			);

			// Get the data from all those CSVs!
			$posts = function() {
				$data = array();
				$errors = array();
				
				$file = $_FILES['woostify_callBack_upload_subscribed']['tmp_name'];

				// Attempt to change permissions if not readable
				if ( ! is_readable( $file ) ) {
					chmod( $file, 0744 );
				}
				
				if ( is_readable( $file ) && $_file = fopen( $file, "r" ) ) {
					$post = array();
					$header = fgetcsv( $_file );

					while ( $row = fgetcsv( $_file ) ) {
						foreach ( $header as $i => $key ) {
							$post[$key] = $row[$i];
						}
						$data[] = $post;
					}
					fclose( $_file );
				} else {
					$errors[] = esc_html__( 'File could not be opened. Check the file\'s permissions to make sure it\'s readable by your server.', 'woostify-pro' );
				}
				

				if ( ! empty( $errors ) ) {
					// ... do stuff with the errors
				}

				return $data;
			};
			
			$post_exists = function( $title, $product_id, $variation_id ) use ( $wpdb, $callback_args ) {			
				$args = array(
					'post_type'      => $callback_args["custom-post-type"],
					'posts_per_page' => -1,
					'post_status' => array( 'cb_subscribed','cb_mailsent','cb_unsubscribed' ),
				);
				$get_posts = get_posts( $args );
				foreach ($get_posts as $post) {
					if( $title == $post->post_title && $product_id == get_post_meta( $post->ID, 'woostify_callback_product_id', true ) && $variation_id ==get_post_meta( $post->ID, 'woostify_callback_variation_id', true ) ) {
						return true;
					}
				}
				return false;
			};			

			foreach ( $posts() as $post ) {				
				if ( $post_exists( $post["email"], $post["product_id"], $post["variation_id"] ) ) {
					continue;
				}
				
				$subscriber_id = wp_insert_post( array(
					'post_type' => $callback_args['custom-post-type'],
					'post_title' => wp_strip_all_tags( $post['email'] ),
					'post_status' => $post['status']
				));
				
				if( $subscriber_id > 0 ) {
					update_post_meta( $subscriber_id, 'woostify_callback_email', $post['email'] );
					update_post_meta( $subscriber_id, 'woostify_callback_name', $post['name'] );
					update_post_meta( $subscriber_id, 'woostify_callback_phone', $post['phone'] );
					update_post_meta( $subscriber_id, 'woostify_callback_product_id', $post['product_id'] );
					update_post_meta( $subscriber_id, 'woostify_callback_variation_id', $post['variation_id'] );
				}				
			}
		}
		
	}

	Woostify_CallBack_Export_Import::get_instance();

endif;