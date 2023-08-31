<?php
/**
 * Elementor Product Additional Information Widget
 *
 * @package Woostify Pro
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class widget.
 */
class Woostify_Product_Additional_Information extends Widget_Base {
	/**
	 * Category
	 */
	public function get_categories() {
		return array( 'woostify-product' );
	}

	/**
	 * Name
	 */
	public function get_name() {
		return 'woostify-product-additional-information';
	}

	/**
	 * Gets the title.
	 */
	public function get_title() {
		return __( 'Woostify - Product Additional Information', 'woostify-pro' );
	}

	/**
	 * Gets the icon.
	 */
	public function get_icon() {
		return 'eicon-product-info';
	}

	/**
	 * Gets the keywords.
	 */
	public function get_keywords() {
		return array( 'woostify', 'woocommerce', 'shop', 'product', 'additional information', 'store' );
	}

	/**
	 * Script
	 */
	public function get_script_depends() {
		return array( 'woostify-product-additional-information-widget' );
	}

	/**
	 * Render
	 */
	public function render() {
		global $product;
		if ( woostify_is_elementor_editor() ) {
			$product_id         = \Woostify_Woo_Builder::init()->get_product_id();
			$product            = wc_get_product( $product_id );
			$GLOBALS['product'] = $product;
		}

		if ( empty( $product ) ) {
			return;
		}

		wc_get_template( 'single-product/tabs/additional-information.php' );
	}
}
Plugin::instance()->widgets_manager->register( new Woostify_Product_Additional_Information() );
