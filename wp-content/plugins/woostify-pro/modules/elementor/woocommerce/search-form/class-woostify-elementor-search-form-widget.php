<?php
/**
 * Elementor Search Form Widget
 *
 * @package Woostify Pro
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for woostify elementor Search form widget.
 */
class Woostify_Elementor_Search_Form_Widget extends Widget_Base {
	/**
	 * Category
	 */
	public function get_categories() {
		return array( 'woostify-theme' );
	}

	/**
	 * Name
	 */
	public function get_name() {
		return 'woostify-search-form';
	}

	/**
	 * Gets the title.
	 */
	public function get_title() {
		return __( 'Woostify - Search Form', 'woostify-pro' );
	}

	/**
	 * Gets the icon.
	 */
	public function get_icon() {
		return 'eicon-site-search';
	}

	/**
	 * Gets the keywords.
	 */
	public function get_keywords() {
		return array( 'woostify', 'search', 'form' );
	}

	/**
	 * General
	 */
	public function general() {
		$this->start_controls_section(
			'general',
			array(
				'label' => __( 'General', 'woostify-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		// Text color.
		$this->add_control(
			'text_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Text Color', 'woostify-pro' ),
				'selectors' => array(
					'{{WRAPPER}} .woocommerce-product-search .search-field' => 'color: {{VALUE}};',
				),
			)
		);

		// Background color.
		$this->add_control(
			'bg_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Background Color', 'woostify-pro' ),
				'selectors' => array(
					'{{WRAPPER}} .woocommerce-product-search:not(.category-filter) .search-field, {{WRAPPER}} .woocommerce-product-search.category-filter' => 'background-color: {{VALUE}};',
				),
			)
		);

		// Border color.
		$this->add_control(
			'border_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Border Color', 'woostify-pro' ),
				'selectors' => array(
					'{{WRAPPER}} .woocommerce-product-search:not(.category-filter) .search-field, {{WRAPPER}} .woocommerce-product-search.category-filter' => 'border-color: {{VALUE}};',
				),
			)
		);

		// Border radius.
		$this->add_responsive_control(
			'padding',
			array(
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => esc_html__( 'Border Radius', 'woostify-pro' ),
				'size_units' => array(
					'px',
					'em',
				),
				'selectors'  => array(
					'{{WRAPPER}} .woocommerce-product-search:not(.category-filter) .search-field, {{WRAPPER}} .woocommerce-product-search.category-filter' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		// Search Icon.
		$this->add_control(
			'search_icon',
			array(
				'label'     => __( 'Search Icon', 'woostify-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		// Color Search.
		$this->add_control(
			'search_color',
			array(
				'label'     => __( 'Color', 'woostify-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .search-form-icon .woostify-svg-icon' => 'color: {{VALUE}};',
					'{{WRAPPER}} .search-form-icon svg path' => 'fill: {{VALUE}};',
				),
			)
		);

		// Background Search.
		$this->add_control(
			'search_background',
			array(
				'label'     => __( 'Background', 'woostify-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .woocommerce-product-search button[type="submit"]' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		// Search size icon.
		$this->add_control(
			'icon_size',
			array(
				'label'      => __( 'Icon Size', 'woostify-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min'  => 10,
						'max'  => 200,
						'step' => 1,
					),
					'%'  => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .search-form-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Controls
	 */
	protected function register_controls() { // phpcs:ignore
		$this->general();
	}

	/**
	 * Render
	 */
	public function render() {
		$total_product = (int) get_option( 'woostify_ajax_search_product_total', '-1' );
		?>
		<div class="woostify-search-form-widget site-search woostify-search-wrap">
			<div class="dialog-search-main woostify-search-wrap">
				<?php the_widget( 'WC_Widget_Product_Search', 'title=' ); ?>
			</div>
			<div class="search-results-wrapper">
				<div class="ajax-search-results"></div>
				<?php if ( -1 != $total_product ) : //phpcs:ignore ?>
					<div class="total-result">
						<div class="total-result-wrapper">
						</div>
					</div>
				<?php endif ?>

			</div>
		</div>
		<?php
	}
}
Plugin::instance()->widgets_manager->register( new Woostify_Elementor_Search_Form_Widget() );
