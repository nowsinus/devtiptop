<?php
/**
 * Elementor Product Rating Widget
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
class Woostify_Product_Rating extends Widget_Base {
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
		return 'woostify-product-rating';
	}

	/**
	 * Gets the title.
	 */
	public function get_title() {
		return __( 'Woostify - Product Rating', 'woostify-pro' );
	}

	/**
	 * Gets the icon.
	 */
	public function get_icon() {
		return 'eicon-product-rating';
	}

	/**
	 * Gets the keywords.
	 */
	public function get_keywords() {
		return array( 'woostify', 'woocommerce', 'shop', 'product', 'rating', 'store' );
	}

	/**
	 * Controls
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'start',
			array(
				'label' => __( 'General', 'woostify-pro' ),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'woostify-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => __( 'Left', 'woostify-pro' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'woostify-pro' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'woostify-pro' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render
	 */
	protected function render() {
		global $product;
		if ( woostify_is_elementor_editor() ) {
			$product_id         = \Woostify_Woo_Builder::init()->get_product_id();
			$product            = wc_get_product( $product_id );
			$GLOBALS['product'] = $product;
		}

		if ( empty( $product ) ) {
			return;
		}

		wc_get_template( 'single-product/rating.php' );
	}
}
Plugin::instance()->widgets_manager->register( new Woostify_Product_Rating() );
