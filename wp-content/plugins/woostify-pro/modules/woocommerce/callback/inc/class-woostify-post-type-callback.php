<?php
/**
 * Woostify Callback Post Type
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_CallBack_Post_Type' ) ) :

	/**
	 * Woostify Callback
	 */
	class Woostify_CallBack_Post_Type {
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
			add_action( 'init', array( $this, 'register_post_type' ) );
			add_action( 'init', array( $this, 'register_post_status' ) );			
			add_filter( 'manage_callback_posts_columns', array( $this, 'callback_add_column_head' ), 10 );
			add_action( 'manage_callback_posts_custom_column', array( $this, 'callback_order_id_column' ), 10, 2 );	
			add_filter( 'post_row_actions', array( $this, 'callback_remove_row_actions_edit' ), 10, 2 );
			add_action( 'admin_action_callback-sendmail', array( $this, 'send_manual_mail' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_style' ) );

			// Callback create new.
			add_action( 'wp_ajax_woostify_callback_ajax_create_post', array( $this, 'woostify_callback_ajax_create_post' ) );
			add_action( 'wp_ajax_nopriv_woostify_callback_ajax_create_post', array( $this, 'woostify_callback_ajax_create_post' ) );	
			

			
			add_action( 'woocommerce_update_product',  array( $this, 'callback_check_send_mail_instock' ) );
			add_action( 'woocommerce_update_product_variation',  array( $this, 'callback_check_send_mail_instock_product_variation' ) );

			// Bulk action unset edit
			add_filter( 'bulk_actions-edit-callback', array( $this, 'remove_from_bulk_actions' ) );			
			// handle bulk actions
			add_filter( 'handle_bulk_actions-edit-callback', array( $this, 'handle_bulk_actions' ), 10, 3 );
			//mark status to mail sent
			add_action( 'callback_handle_action_mark_status_sent', array( $this, 'bulk_mark_status_sent' ) );
			//mark status to subscribed
			add_action( 'callback_handle_action_mark_status_subscribed', array( $this, 'bulk_mark_status_subscribed' ) );
			//mark status to unsubscribed
			add_action( 'callback_handle_action_mark_status_unsubscribed', array( $this, 'bulk_mark_status_unsubscribed' ) );
			//send mail in bulk
			add_action( 'callback_handle_action_send_mail', array( $this, 'bulk_send_manual_email' ) );
		}

		public function load_admin_style() {
			wp_enqueue_style(
				'woostify-callback-admin',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/callback/css/admin.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);
		}

		/**
		 * Register Post Type
		 */
		public function register_post_type() {
			$labels = array(
				'name' => esc_html__( 'Callback', 'All Callback', 'woostify-pro' ),
				'singular_name' => esc_html__( 'All Callback', 'All Callback', 'woostify-pro' ),
				'menu_name' => esc_html__( 'Callback Notifier', 'Callback Notifier', 'woostify-pro' ),
				'name_admin_bar' => esc_html__( 'Callback Notifier', 'Name in Admin Bar', 'woostify-pro' ),
				'add_new' => esc_html__( 'Add New Callback', 'add new in menu', 'woostify-pro' ),
				'add_new_item' => esc_html__( 'Add New Callback', 'woostify-pro' ),
				'new_item' => esc_html__( 'New Callback', 'woostify-pro' ),
				'edit_item' => esc_html__( 'Edit Callback', 'woostify-pro' ),
				'view_item' => esc_html__( 'View Callback', 'woostify-pro' ),
				'all_items' => esc_html__( 'All Callback', 'woostify-pro' ),
				'search_items' => esc_html__( 'Search Callback', 'woostify-pro' ),
				'parent_item_colon' => esc_html__( 'Parent:', 'woostify-pro' ),
				'not_found' => esc_html__( 'No Callback Found', 'woostify-pro' ),
				'not_found_in_trash' => esc_html__( 'No Callback found in Trash', 'woostify-pro' ),
			);

			$args = array(
				'labels' => $labels,
				'show_ui' => true,
				'show_in_menu' => false,
				'capability_type' => 'post',
				'capabilities' => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap' => true,
				'supports' => false,
			);

			register_post_type( 'callback', $args );
			if ( ! get_option( 'woostify_callback_flush_rewrite_rules' ) ) {
				flush_rewrite_rules();
				update_option( 'woostify_callback_flush_rewrite_rules', true );
			}
		}

		/**
		 * Register Post Status
		 */
		public function register_post_status() {
			/*
			 * (%s) is for count of specific statuses
			 */
			register_post_status('cb_mailsent', array(
				'label' => _x('Mail Sent', 'post', 'woostify-pro'),
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count */
				'label_count' => _n_noop('Mail Sent <span class="count">(%s)</span>', 'Mail Sent <span class="count">(%s)</span>', 'woostify-pro'),
			));

			register_post_status('cb_subscribed', array(
				'label' => _x('Subscribed', 'post', 'woostify-pro'),
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count */
				'label_count' => _n_noop('Subscribed <span class="count">(%s)</span>', 'Subscribed <span class="count">(%s)</span>'),
			));

			register_post_status('cb_unsubscribed', array(
				'label' => _x('Unsubscribed', 'post', 'woostify-pro'),
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count */
				'label_count' => _n_noop('Unsubscribed <span class="count">(%s)</span>', 'Unsubscribed <span class="count">(%s)</span>'),
			));
		}

		/**
		 * Callback remove row actions edit
		 *
		 * @param  string $actions  The actions.
		 */
		public function callback_remove_row_actions_edit( $actions, $post ){

			if( get_post_type($post) == 'callback' ){
				$post_id = intval($post->ID);
				$newactions['edit'] = "<span class='id' style='color:#a0a0a0;'>" . __('ID:', 'woostify-pro') . " $post_id" . '</span>';
				
				unset($actions['inline hide-if-no-js']);
				$edit_list = admin_url('edit.php?post_type=callback');
				$action = 'callback-sendmail';
				$nonce = wp_create_nonce('callback_sendmail-' . $post_id);
				$query_arg = esc_url_raw(add_query_arg(array('action' => $action, 'post_id' => $post_id, 'nonce' => $nonce), $edit_list));

				$caption = __('Send Instock Mail', 'woostify-pro');
				$sendmail = "<a href='$query_arg'>$caption</a>";
				$newactions['sendmail'] = $sendmail;

				if (isset($actions['trash'])) {
					$newactions['trash'] = $actions['trash'];
				}
				return apply_filters('callback_row_actions', $newactions, $actions, $post);
			}

			return $actions;
		}

		public function remove_from_bulk_actions( $actions) {
			unset($actions['edit']);
			$newactions = array();
			$list_of_actions = array('mark_status_sent' => __('Change status to Mail Sent', 'woostify-pro'), 'mark_status_subscribed' => __('Change status to Subscribed', 'woostify-pro'), 'mark_status_unsubscribed' => __('Change status to Unsubscribed', 'woostify-pro'), 'send_mail' => __('Send Email', 'woostify-pro'));
			foreach ($list_of_actions as $key => $each_action) {
				$newactions[$key] = $each_action;
			}
			$merge_actions = array_merge($newactions, $actions);
			return apply_filters('callback_bulk_actions', $merge_actions);
		}

		public function handle_bulk_actions( $redirect_to, $action, $post_ids) {
			do_action('callback_handle_action_' . $action, $post_ids);
			return $redirect_to;
		}

		public function bulk_mark_status_sent( $post_ids ) {
			if ( is_array( $post_ids ) && !empty( $post_ids ) ) {
				foreach ($post_ids as $each_id) {
					$this->mail_sent_status( $each_id );
				}
			}
		}

		public function mail_sent_status( $subscribe_id ) {
			$args = array(
				'ID' => $subscribe_id,
				'post_type' => 'callback',
				'post_status' => 'cb_mailsent',
			);
			$id = wp_update_post( $args );
			return $id;
		}

		public function bulk_mark_status_subscribed( $post_ids ) {			
			if ( is_array( $post_ids ) && !empty( $post_ids ) ) {
				foreach ($post_ids as $each_id) {
					$this->subscriber_subscribed( $each_id );
				}
			}
		}	

		public function subscriber_subscribed( $subscribe_id ) {
			$args = array(
				'ID' => $subscribe_id,
				'post_type' => 'callback',
				'post_status' => 'cb_subscribed',
			);
			$id = wp_update_post( $args );
			return $id;
		}

		public function bulk_mark_status_unsubscribed( $post_ids ) {
			if ( is_array( $post_ids ) && !empty( $post_ids ) ) {
				foreach ( $post_ids as $each_id ) {
					$this->subscriber_unsubscribed( $each_id );
				}
			}
		}

		public function subscriber_unsubscribed( $subscribe_id ) {
			$args = array(
				'ID' => $subscribe_id,
				'post_type' => 'callback',
				'post_status' => 'cb_unsubscribed',
			);
			$id = wp_update_post( $args );
			return $id;
		}

		public function bulk_send_manual_email( $post_ids ) {
			$mailer = Woostify_CallBack_Mailer::get_instance();	

			if ( is_array( $post_ids ) && !empty( $post_ids ) ) {
				foreach ( $post_ids as $each_id ) {
					$email = get_post_meta( $each_id, 'woostify_callback_email', true );					
					$send_mail = $mailer->send_instock( $email, $each_id );
					$this->mail_sent_status( $each_id );
				}
			}
		}

		/**
		 * Callback Add Column Head.
		 *
		 * @param  string $columns  The column name.
		 */
		public function callback_add_column_head( $columns ) {
			$columns = array(
				'cb' => $columns['cb'],
				'email' => __( 'Email', 'woostify-pro' ),
				'name' => __( 'Name', 'woostify-pro' ),
				'phone' => __( 'Phone', 'woostify-pro' ),
				'product' => __( 'Product', 'woostify-pro' ),
				'status' => __( 'Status', 'woostify-pro' ),
				'date' => __( 'Date', 'woostify-pro' ),
			);

			return $columns;
		}

		/**
		 * Column content
		 *
		 * @param      string $column  The column name.
		 * @param      int    $post_id      The post id.
		 */
		public function callback_order_id_column( $column, $post_id ) {
			switch ( $column ) {
				case 'email':
					$title = get_post_meta( $post_id, 'woostify_callback_email', true );
					echo esc_html( $title );
					break;
				case 'name':
					$name  = get_post_meta( $post_id, 'woostify_callback_name', true );
					echo esc_html( $name );
					break;
				case 'phone':
					$phone  = get_post_meta( $post_id, 'woostify_callback_phone', true );
					echo esc_html( $phone );
					break;
				case 'product':
					$helper = Woostify_CallBack_Helper::get_instance();
					$pid  = (int)get_post_meta( $post_id, 'woostify_callback_product_id', true );
					$variation_id  = (int)get_post_meta( $post_id, 'woostify_callback_variation_id', true );
					if ( $variation_id > 0 ) {
						$pid = $variation_id;
					}					
					$product_name = $helper->display_product_name( $post_id );
					$prod_obj = wc_get_product( $pid );
													
					if ( $prod_obj ) {
						$get_type = $prod_obj->get_type();						
						$product_id = ( 'variation' == $get_type ) ? $prod_obj->get_parent_id() : $pid;
						if ( $product_id ) {
							$permalink = esc_url_raw( admin_url( "post.php?post=$product_id&action=edit" ) );
							$permalink = " <a href='$permalink'>#{$pid } {$product_name}</a>";
							echo wp_kses_post( $permalink );
						}
					}
					break;
				case 'status':
					$this->display_status( $post_id );
					break;
				case 'date':
					echo esc_html( gmdate( 'y-m-d h:i:s' ) );
					break;
			}
		}

		/**
		 * Display Status
		 *
		 * @param  int $id  The post id.
		 */
		public function display_status( $id ) {
			$get_post_status = get_post_status( $id );
			switch ($get_post_status) {
				case 'cb_subscribed':
					$subscribed = __( 'Subscribed', 'woostify-pro' );
					echo wp_kses_post( '<mark class="cbmark cbsubscribed">'.$subscribed.'</mark>' );
					break;
				case 'cb_mailsent':
					$mailsent = __( 'Mail Sent', 'woostify-pro' );
					echo wp_kses_post( '<mark class="cbmark cbmailsent">'.$mailsent.'</mark>' );
					break;
				case 'cb_unsubscribed':
					$unsubscribed = __( 'Unsubscribed', 'woostify-pro' );
					echo wp_kses_post( '<mark class="cbmark cbunsubscribed">'.$unsubscribed.'</mark>' );
					break;
				default:
					$otherstatus = $get_post_status;
					echo wp_kses_post( '<mark class="cbmark">'.$otherstatus.'</mark>' );
					break;
			}
		}

		/**
		 * Callback Ajax Create Post
		 */
		public function woostify_callback_ajax_create_post() {
			$options = Woostify_CallBack::get_instance()->get_options();
			$security = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
			if ( check_ajax_referer( 'woostify_callback', $security, false ) ) {
				return;
			}		
			
			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
			$product_id = isset( $_POST['product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) : '';
			$variation_id = isset( $_POST['variation_id'] ) ? sanitize_text_field( wp_unslash( $_POST['variation_id'] ) ) : 0;
			$agree = isset( $_POST['agree'] ) ? sanitize_text_field( wp_unslash( $_POST['agree'] ) ) : 0;			

			$args = array(
				'post_type'=> 'callback',
				'posts_per_page' => -1,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => 'woostify_callback_email',
						'value' => $email,
						'compare' => '=',
					),
					array(
						'key' => 'woostify_callback_product_id',
						'value' => $product_id,
						'compare' => '=',
					)
				)
			);
			$query = new WP_Query($args);
			$post_count = 0;
			if ( $query->have_posts() ){
				$post_count = $query->post_count;
			}

			$options = Woostify_CallBack::get_instance()->get_options();
			if ( $options['hide_name'] != 1 && $name == '' )	{				
				$error = $options['form_sc_m_yourname'];
				return wp_send_json_error( $error );
			} elseif ( wp_unslash( $_POST['email'] ) == '' ) {
				$error = $options['form_sc_m_yourmail'];
				return wp_send_json_error( $error );
			} elseif ( sanitize_email( wp_unslash( $_POST['email'] ) ) == '' ){
				$error = $options['form_sc_m_notemail'];
				return wp_send_json_error( $error );
			} elseif( $options['show_agree'] == 1 && $agree != 1 ){
				$error = $options['form_sc_m_agree'];
				return wp_send_json_error( $error );
			}elseif ( $post_count > 0 ){
				$error = $options['form_sc_m_already_sub'];
				return wp_send_json_error( $error );
			} elseif ( $options['show_phone'] == 1 && $options['phone_field_optional'] == 1 && $phone == '' ) {
				$error = $options['form_sc_m_yourphone'];
				return wp_send_json_error( $error );
			}else {

				if( $options['form_sc_m_enable_recaptcha'] ) {
					if( isset( $_POST['g-recaptcha-response'] ) ) {
						$captcha = $_POST['g-recaptcha-response'];
					}
					if( !$captcha ){
						$error = $options['form_sc_m_recaptcha'];
						return wp_send_json_error( $error );
					}else {
						$response = json_decode( file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=".$options['secretkeyrecaptcha']."&response=".$captcha."&remoteip=".$_SERVER['REMOTE_ADDR'] ), true );
					}
					if( $response['success'] == false ){
						$error = $options['form_sc_m_validaterecaptcha'];
						return wp_send_json_error( $error );
					}
				}

				$subscriber_id = $this->insert_callback_post( $email );
				// data meta.
				$data = array(
					'woostify_callback_email' => $email,
					'woostify_callback_name' => $name,
					'woostify_callback_phone' => $phone,
					'woostify_callback_product_id' => $product_id,
					'woostify_callback_variation_id' => $variation_id,
				);
				$this->insert_data_meta_post( $subscriber_id, $data );
				
				//send mail
				$mailer = Woostify_CallBack_Mailer::get_instance();
				$send_mail = $mailer->send( $email, $subscriber_id ); // mail sent	

				$success = $options['form_sc_m_success'];
				wp_send_json_success( $success );				
			}			
		}

		/**
		 * Callback Create New Post.
		 *
		 * @param  string $email  post title.
		 * @param  string $status      cb_subscribed.
		 */
		public function insert_callback_post( $email, $status = 'cb_subscribed' ) {
			$args = array(
				'post_title' => wp_strip_all_tags( $email ),
				'post_type' => 'callback',
				'post_status' => $status,
			);

			$subscriber_id = wp_insert_post( $args );
			if ( ! is_wp_error( $subscriber_id ) ) {
				return $subscriber_id;
			} else {
				return false;
			}			
		}

		/**
		 * Insert Data Meta Post.
		 *
		 * @param  int $id post id.
		 * @param  arrray $data array.
		 */
		public function insert_data_meta_post( $id, $data ) {
			foreach ( $data as $key => $value ) {
				update_post_meta( $id, $key, $value );
			}
		}
		
		public function send_manual_mail() {
			if ( isset( $_REQUEST['nonce'] ) && isset( $_REQUEST['post_id'] ) && isset( $_SERVER['HTTP_REFERER'] ) ) {
				$post_id = intval($_REQUEST['post_id']);
				$nonce = sanitize_text_field($_REQUEST['nonce']);
				if ( wp_verify_nonce($nonce, 'callback_sendmail-' . $post_id) ) {					
					$email = get_post_meta( $post_id, 'woostify_callback_email', true );
					$mailer = Woostify_CallBack_Mailer::get_instance();	
					$send_mail = $mailer->send_instock( $email, $post_id );
					$this->mail_sent_status( $post_id );
				}
				wp_redirect($_SERVER['HTTP_REFERER']);
				exit();
			}
		}				

		/**
		 * Callback check send mail instock.
		 *
		 * @param  int $product_id product id.
		 */
		public function callback_check_send_mail_instock( $product_id ) {
			$product = wc_get_product( $product_id );
			$product_type = $product->get_type();
			if ( 'variable' != $product_type ) {
				if ( method_exists( $product, 'get_stock_status' ) ) {
					$stock_status = $product->get_stock_status(); // For version 3.0+
				} else {
					$stock_status = $product->stock_status; // Older than version 3.0
				}			

				if ( $stock_status == 'instock' ) {
					$mailer = Woostify_CallBack_Mailer::get_instance();
					$helper = Woostify_CallBack_Helper::get_instance();
					$list_subscribers = $helper->get_list_of_subscribers( $product_id );
					foreach ( $list_subscribers as $key => $subscriber_id ) {
						if ( get_post_status( $subscriber_id ) == 'cb_subscribed' ) {
							$email = $helper->get_subscriber_mail( $subscriber_id );
							$send_mail = $mailer->send_instock( $email, $subscriber_id );
							if ( $send_mail ) {
								$update_post = array(
									'ID' => $subscriber_id,
									'post_type' => 'callback',
									'post_status' => 'cb_mailsent',
								);
								wp_update_post( $update_post );
							}
						}
					}
				}
			}
		}

		public function callback_check_send_mail_instock_product_variation( $variation_id ) {
			$product = wc_get_product( $variation_id );	
			if ( method_exists( $product, 'get_stock_status' ) ) {
				$stock_status = $product->get_stock_status(); // For version 3.0+
			} else {
				$stock_status = $product->stock_status; // Older than version 3.0
			}

			if ( $stock_status === 'instock' ) {
				$mailer = Woostify_CallBack_Mailer::get_instance();
				$helper = Woostify_CallBack_Helper::get_instance();
				$list_subscribers = $helper->get_list_of_subscribers( $variation_id );
				foreach ( $list_subscribers as $key => $subscriber_id ) {
					if ( get_post_status( $subscriber_id ) == 'cb_subscribed' ) {
						$email = $helper->get_subscriber_mail( $subscriber_id );
						$send_mail = $mailer->send_instock( $email, $subscriber_id );
						if ( $send_mail ) {
							$update_post = array(
								'ID' => $subscriber_id,
								'post_type' => 'callback',
								'post_status' => 'cb_mailsent',
							);
							wp_update_post( $update_post );
						}
					}
				}
			}
		}

	}

	Woostify_CallBack_Post_Type::get_instance();
endif;
