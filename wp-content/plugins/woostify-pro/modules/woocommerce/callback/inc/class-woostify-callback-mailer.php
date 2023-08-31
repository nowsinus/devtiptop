<?php
/**
 * Woostify CallBack Mailler
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_CallBack_Mailer' ) ) :

	/**
	 * Class Woostify CallBack Mailler
	 */
	class Woostify_CallBack_Mailer {
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
		 * Replace shortcode.
		 *
		 *  @param  string $content      content.
		 */
		private function replace_shortcode( $content, $subscriber_id ) {
			$obj			   = Woostify_CallBack_Helper::get_instance();
			$product_id        = get_post_meta( $subscriber_id, 'woostify_callback_product_id', true );
			$product_name      = $obj->display_product_name( $subscriber_id );
			$product_link      = $obj->display_product_link( $subscriber_id );
			$subscriber_email  = $obj->get_subscriber_mail( $subscriber_id );
			$subscriber_name   = $obj->get_subscriber_name( $subscriber_id );
			$subscriber_phone  = $obj->get_subscriber_phone( $subscriber_id );
			$cart_link         = $obj->get_cart_link( $subscriber_id );
			$shopname          = get_bloginfo('name');
			$find_array        = array( '{product_name}', '{product_id}', '{product_link}', '{shopname}', '{subscriber_email}', '{subscriber_name}', '{subscriber_phone}', '{cart_link}' );
			$replace_array     = array( strip_tags( $product_name ), $product_id, $product_link, $shopname, $subscriber_email, $subscriber_name, $subscriber_phone, $cart_link );
			$formatted_content = str_replace( $find_array, $replace_array, $content );
			return $formatted_content;
		}

		/**
		 * Get subject.
		 */
		public function get_subject( $subscriber_id ) {
			$options = Woostify_CallBack::get_instance()->get_options();
			$subject = $options['callback_mail_subject'];
			return do_shortcode( $this->replace_shortcode( $subject, $subscriber_id ) );
		}

		/**
		 * Get message.
		 */
		public function get_message( $subscriber_id ) {
			$options = Woostify_CallBack::get_instance()->get_options();
			$message = wpautop( $options['callback_mail_message'] );
			return do_shortcode( $this->replace_shortcode( $message, $subscriber_id ) );
		}

        /**
		 * Format html message.
		 */
		public function format_html_message( $subscriber_id ) {
			ob_start();
			if ( function_exists( 'wc_get_template' ) ) {
				do_action( 'woocommerce_email_header', $this->get_subject( $subscriber_id ), null );
				echo do_shortcode( $this->get_message( $subscriber_id ) );
				do_action( 'woocommerce_email_footer', get_option('woocommerce_email_footer_text') );
			} else {
				woocommerce_get_template( 'emails/email-header.php', array( 'email_heading' => $this->get_subject( $subscriber_id ) ) );
				echo do_shortcode( $this->get_message( $subscriber_id ) );
				woocommerce_get_template( 'emails/email-footer.php' );
			}
			return ob_get_clean();
		}

        /**
		 * Send.
		 *
		 * @param  string $mail_to      send mail.
         * @param  int $subscriber_id      subscriber id.
		 */
		public function send ( $mail_to, $subscriber_id ) {
			$options = Woostify_CallBack::get_instance()->get_options();
			if ( $options['subsciption_mail_send'] == 1 )	{
				$to = $mail_to;		
				$mailer   = WC()->mailer();
				$sendmail = $mailer->send( $to, $this->get_subject( $subscriber_id ), $this->format_html_message( $subscriber_id ) );
				if ( $sendmail ) {
					return true;
				} else {
					return false;
				}
			}
		}

        /**
		 * Get subject instock.
		 */
		public function get_subject_instock( $subscriber_id ) {
			$options = Woostify_CallBack::get_instance()->get_options();
			$subject = $options['callback_mail_subject_instock'];
			return do_shortcode( $this->replace_shortcode( $subject, $subscriber_id ) );
		}

		/**
		 * Get message instock.
		 */
		public function get_message_instock( $subscriber_id ) {
			$options = Woostify_CallBack::get_instance()->get_options();
			$message = wpautop( $options['callback_mail_message_instock'] );
			return do_shortcode( $this->replace_shortcode( $message, $subscriber_id ) );
		}		

		/**
		 * Format html message instock.
		 */
		public function format_html_message_instock( $subscriber_id ) {
			ob_start();
			if ( function_exists( 'wc_get_template' ) ) {
				do_action( 'woocommerce_email_header', $this->get_subject_instock( $subscriber_id ), null );
				echo do_shortcode( $this->get_message_instock( $subscriber_id ) );
				do_action( 'woocommerce_email_footer', get_option('woocommerce_email_footer_text') );
			} else {
				woocommerce_get_template( 'emails/email-header.php', array( 'email_heading' => $this->get_subject_instock( $subscriber_id ) ) );
				echo do_shortcode( $this->get_message_instock( $subscriber_id ) );
				woocommerce_get_template( 'emails/email-footer.php' );
			}
			return ob_get_clean();
		}

		/**
		 * Send instock.
		 *
		 * @param  string $mail_to      send mail.
         * @param  int $subscriber_id      subscriber id.
		 */
		public function send_instock ( $mail_to, $subscriber_id ) {
			$options = Woostify_CallBack::get_instance()->get_options();
			if ( $options['instock_mail_send'] == 1 )	{
				$to = $mail_to;
				$mailer   = WC()->mailer();
				$sendmail = $mailer->send( $to, $this->get_subject_instock( $subscriber_id ), $this->format_html_message_instock( $subscriber_id ) );
				if ( $sendmail ) {
					return true;
				} else {
					return false;
				}
			}
		}
	}

endif;