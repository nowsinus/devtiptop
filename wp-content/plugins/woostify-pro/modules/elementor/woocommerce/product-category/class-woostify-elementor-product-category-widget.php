<?php
/**
 * Elementor Product Category Widget
 *
 * @package Woostify Pro
 */

namespace Elementor;

/**
 * Class woostify elementor product category widget.
 */
class Woostify_Elementor_Product_Category_Widget extends Widget_Base {
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
		return 'woostify-product-category';
	}

	/**
	 * Title
	 */
	public function get_title() {
		return esc_html__( 'Woostify - Product Category', 'woostify-pro' );
	}

	/**
	 * Icon
	 */
	public function get_icon() {
		return 'eicon-woocommerce';
	}

	/**
	 * Add a script.
	 */
	public function get_script_depends() {
		return array(
			'woostify-elementor-widget',
		);
	}


	/**
	 * Controls
	 */
	protected function register_controls() { // phpcs:ignore
		$this->section_general();
		$this->section_content();
		$this->section_controls();
		$this->section_query();
	}

	/**
	 * General
	 */
	private function section_general() {
		$this->start_controls_section(
			'product_general',
			array(
				'label' => esc_html__( 'General', 'woostify-pro' ),
			)
		);

		// Layout.
		$this->add_control(
			'layout',
			array(
				'type'    => Controls_Manager::SELECT,
				'label'   => esc_html__( 'Layout', 'woostify-pro' ),
				'default' => 'grid',
				'options' => array(
					'grid'     => __( 'Grid', 'woostify-pro' ),
					'carousel' => __( 'Carousel', 'woostify-pro' ),
				),
			)
		);

		// Columns.
		$this->add_responsive_control(
			'columns',
			array(
				'separator'      => 'before',
				'type'           => Controls_Manager::SELECT,
				'label'          => esc_html__( 'Columns', 'woostify-pro' ),
				'default'        => 4,
				'tablet_default' => 2,
				'mobile_default' => 1,
				'options'        => array(
					1 => 1,
					2 => 2,
					3 => 3,
					4 => 4,
					5 => 5,
					6 => 6,
				),
			)
		);

		// Image size.
		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'    => 'image',
				'default' => 'medium_large',
			)
		);

		// Grid space.
		$this->add_responsive_control(
			'space',
			array(
				'type'               => Controls_Manager::DIMENSIONS,
				'label'              => esc_html__( 'Columns Space', 'woostify-pro' ),
				'size_units'         => array( 'px', 'em' ),
				'default'            => array(
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'unit'     => 'px',
					'isLinked' => false,
				),
				'allowed_dimensions' => array(
					'top',
					'bottom',
				),
				'selectors'          => array(
					'{{WRAPPER}} .ht-grid-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'          => array(
					'layout' => 'grid',
				),
			)
		);

		// Columns gap for Grid layout.
		$this->add_responsive_control(
			'columns_gap',
			array(
				'type'       => Controls_Manager::SLIDER,
				'label'      => esc_html__( 'Columns Gap', 'woostify-pro' ),
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 15,
				),
				'selectors'  => array(
					'{{WRAPPER}} .ht-grid'      => 'margin: 0px -{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .ht-grid-item' => 'padding: 0px {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'layout' => 'grid',
				),
			)
		);

		// Columns gap for Carousel layout.
		$this->add_responsive_control(
			'columns_gap_carousel',
			array(
				'type'           => Controls_Manager::SLIDER,
				'label'          => esc_html__( 'Columns Gap', 'woostify-pro' ),
				'size_units'     => array( 'px' ),
				'range'          => array(
					'px' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'        => array(
					'unit' => 'px',
					'size' => 15,
				),
				'tablet_default' => array(
					'unit' => 'px',
					'size' => 15,
				),
				'mobile_default' => array(
					'unit' => 'px',
					'size' => 15,
				),
				'condition'      => array(
					'layout' => 'carousel',
				),
			)
		);

		// Overlay background.
		$this->add_control(
			'overlay_bg',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Overlay', 'woostify-pro' ),
				'default'   => 'rgba(255,255,255,0)',
				'selectors' => array(
					'{{WRAPPER}} .pcw-overlay' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content
	 */
	private function section_content() {
		$this->start_controls_section(
			'product_content',
			array(
				'label' => esc_html__( 'Content', 'woostify-pro' ),
			)
		);

		// Content position.
		$this->add_control(
			'content_pos',
			array(
				'type'    => Controls_Manager::SELECT,
				'label'   => esc_html__( 'Content', 'woostify-pro' ),
				'default' => 'inside',
				'options' => array(
					'inside'  => __( 'Inside', 'woostify-pro' ),
					'outside' => __( 'Outside', 'woostify-pro' ),
				),
			)
		);

		// Horizontal Position.
		$this->add_control(
			'horizontal_pos',
			array(
				'type'        => Controls_Manager::CHOOSE,
				'label'       => esc_html__( 'Horizontal', 'woostify-pro' ),
				'label_block' => false,
				'options'     => array(
					'flex-start' => array(
						'title' => esc_html__( 'Left', 'woostify-pro' ),
						'icon'  => 'eicon-h-align-left',
					),
					'center'     => array(
						'title' => esc_html__( 'Center', 'woostify-pro' ),
						'icon'  => 'eicon-h-align-center',
					),
					'flex-end'   => array(
						'title' => esc_html__( 'Right', 'woostify-pro' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'selectors'   => array(
					'{{WRAPPER}} .pcw-info-inner' => 'align-items: {{VALUE}};',
				),
			)
		);

		// Vertical Position.
		$this->add_control(
			'vertical_pos',
			array(
				'type'        => Controls_Manager::CHOOSE,
				'label'       => esc_html__( 'Vertical', 'woostify-pro' ),
				'label_block' => false,
				'options'     => array(
					'flex-start' => array(
						'title' => esc_html__( 'Top', 'woostify-pro' ),
						'icon'  => 'eicon-v-align-top',
					),
					'center'     => array(
						'title' => esc_html__( 'Middle', 'woostify-pro' ),
						'icon'  => 'eicon-v-align-middle',
					),
					'flex-end'   => array(
						'title' => esc_html__( 'Bottom', 'woostify-pro' ),
						'icon'  => 'eicon-v-align-bottom',
					),
				),
				'selectors'   => array(
					'{{WRAPPER}} .pcw-info' => 'justify-content: {{VALUE}};',
				),
				'condition'   => array(
					'content_pos' => 'inside',
				),
			)
		);

		// Border Radius.
		$this->add_responsive_control(
			'border-radius',
			array(
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => esc_html__( 'Border Radius', 'woostify-pro' ),
				'size_units' => array( 'px', '%', 'em' ),
				'default'    => array(
					'unit' => '%',
				),
				'selectors'  => array(
					'{{WRAPPER}} .pcw-image, {{WRAPPER}} .pcw-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		// Padding.
		$this->add_responsive_control(
			'padding',
			array(
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => esc_html__( 'Padding', 'woostify-pro' ),
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .pcw-info-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		// Margin.
		$this->add_responsive_control(
			'margin',
			array(
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => esc_html__( 'Margin', 'woostify-pro' ),
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .pcw-info-inner' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		// Content background color.
		$this->add_control(
			'content_bg_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Background Color', 'woostify-pro' ),
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .pcw-info-inner' => 'background-color: {{VALUE}}',
				),
			)
		);

		// Category name.
		$this->add_control(
			'category_name',
			array(
				'label'     => __( 'Category Name', 'woostify-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		// Title color.
		$this->add_control(
			'title_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Text Color', 'woostify-pro' ),
				'default'   => '#3c3c3c',
				'selectors' => array(
					'{{WRAPPER}} .pcw-title' => 'color: {{VALUE}}',
				),
			)
		);

		// Title hover color.
		$this->add_control(
			'title_hover_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Text Hover Color', 'woostify-pro' ),
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .pcw-info-inner:hover .pcw-title' => 'color: {{VALUE}}',
				),
			)
		);

		// Typography.
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title',
				'label'    => __( 'Typography', 'woostify-pro' ),
				'selector' => '{{WRAPPER}} .pcw-title',
			)
		);

		// Category name.
		$this->add_control(
			'category_count',
			array(
				'label'     => __( 'Category Count', 'woostify-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		// Category count.
		$this->add_control(
			'count',
			array(
				'type'         => Controls_Manager::SWITCHER,
				'label'        => esc_html__( 'Display', 'woostify-pro' ),
				'default'      => 'yes',
				'label_on'     => esc_html__( 'Yes', 'woostify-pro' ),
				'label_off'    => esc_html__( 'No', 'woostify-pro' ),
				'return_value' => 'yes',
			)
		);

		// Category count color.
		$this->add_control(
			'count_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Text Color', 'woostify-pro' ),
				'default'   => '#bdbdbd',
				'selectors' => array(
					'{{WRAPPER}} .pcw-count' => 'color: {{VALUE}}',
				),
				'condition' => array(
					'count' => 'yes',
				),
			)
		);

		// Custom button.
		$this->add_control(
			'custom_button_heading',
			array(
				'label'     => __( 'Button', 'woostify-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		// Button.
		$this->add_control(
			'button',
			array(
				'type'         => Controls_Manager::SWITCHER,
				'label'        => esc_html__( 'Display', 'woostify-pro' ),
				'default'      => '',
				'label_on'     => esc_html__( 'Yes', 'woostify-pro' ),
				'label_off'    => esc_html__( 'No', 'woostify-pro' ),
				'return_value' => 'yes',
			)
		);

		// Custom button.
		$this->add_control(
			'button_text',
			array(
				'type'      => Controls_Manager::TEXT,
				'label'     => esc_html__( 'Text', 'woostify-pro' ),
				'default'   => __( 'Shop now', 'woostify-pro' ),
				'condition' => array(
					'button' => 'yes',
				),
			)
		);

		// Button color.
		$this->add_control(
			'button_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Text Color', 'woostify-pro' ),
				'default'   => '#e71717',
				'selectors' => array(
					'{{WRAPPER}} .pcw-button' => 'color: {{VALUE}}',
				),
				'condition' => array(
					'button' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Controls
	 */
	private function section_controls() {
		$this->start_controls_section(
			'product_controls',
			array(
				'label'     => esc_html__( 'Controls', 'woostify-pro' ),
				'condition' => array(
					'layout' => 'carousel',
				),
			)
		);

		// Loop for Carousel layout.
		$this->add_control(
			'loop',
			array(
				'type'         => Controls_Manager::SWITCHER,
				'label'        => esc_html__( 'Loop', 'woostify-pro' ),
				'default'      => 'yes',
				'label_on'     => esc_html__( 'Yes', 'woostify-pro' ),
				'label_off'    => esc_html__( 'No', 'woostify-pro' ),
				'return_value' => 'yes',
				'condition'    => array(
					'layout' => 'carousel',
				),
			)
		);

		// Arrows.
		$this->add_control(
			'arrows',
			array(
				'type'         => Controls_Manager::SWITCHER,
				'label'        => esc_html__( 'Enable Arrows', 'woostify-pro' ),
				'default'      => 'yes',
				'label_on'     => esc_html__( 'Yes', 'woostify-pro' ),
				'label_off'    => esc_html__( 'No', 'woostify-pro' ),
				'return_value' => 'yes',
			)
		);

		// Arrows size.
		$this->add_responsive_control(
			'arrows_size',
			array(
				'type'       => Controls_Manager::SLIDER,
				'label'      => esc_html__( 'Size', 'woostify-pro' ),
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 30,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 50,
				),
				'selectors'  => array(
					'{{WRAPPER}} .tns-controls [data-controls]' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'arrows' => 'yes',
				),
			)
		);

		// Arrows border radius.
		$this->add_responsive_control(
			'arrows_border',
			array(
				'type'       => Controls_Manager::SLIDER,
				'label'      => esc_html__( 'Border Radius', 'woostify-pro' ),
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min'  => 1,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 50,
				),
				'selectors'  => array(
					'{{WRAPPER}} .tns-controls [data-controls]' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'arrows' => 'yes',
				),
			)
		);

		// Arrows position.
		$this->add_responsive_control(
			'arrows_position',
			array(
				'type'      => Controls_Manager::SLIDER,
				'label'     => esc_html__( 'Position', 'woostify-pro' ),
				'size_unis' => array( 'px' ),
				'range'     => array(
					'px' => array(
						'min'  => -150,
						'max'  => 150,
						'step' => 1,
					),
				),
				'default'   => array(
					'unit' => 'px',
					'size' => 20,
				),
				'selectors' => array(
					'{{WRAPPER}} .tns-controls [data-controls="prev"]' => 'left: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .tns-controls [data-controls="next"]' => 'right: {{SIZE}}{{UNIT}};',
				),
				'condition' => array(
					'arrows' => 'yes',
				),
			)
		);

		// Separator.
		$this->add_control(
			'hr_tab_post_meta',
			array(
				'type'  => Controls_Manager::DIVIDER,
				'style' => 'thick',
			)
		);

		// START TAB CONTROLS.
		$this->start_controls_tabs( 'tab_controls' );

		// Tab normal start.
		$this->start_controls_tab(
			'tab_normal',
			array(
				'label'     => __( 'Normal', 'woostify-pro' ),
				'condition' => array(
					'arrows' => 'yes',
				),
			)
		);

		// Arrows background color.
		$this->add_control(
			'arrows_bg_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Background Color', 'woostify-pro' ),
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .tns-controls [data-controls]' => 'background-color: {{VALUE}}',
				),
				'condition' => array(
					'arrows' => 'yes',
				),
			)
		);

		// Arrows color.
		$this->add_control(
			'arrows_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Color', 'woostify-pro' ),
				'default'   => '#333333',
				'selectors' => array(
					'{{WRAPPER}} .tns-controls [data-controls]' => 'color: {{VALUE}}',
				),
				'condition' => array(
					'arrows' => 'yes',
				),
			)
		);

		// Tab normal end.
		$this->end_controls_tab();

		// Tab hover start.
		$this->start_controls_tab(
			'tab_hover',
			array(
				'label'     => __( 'Hover', 'woostify-pro' ),
				'condition' => array(
					'arrows' => 'yes',
				),
			)
		);

		// Arrows background color hover.
		$this->add_control(
			'arrows_bg_color_hover',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Background Color', 'woostify-pro' ),
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .tns-controls [data-controls]:hover' => 'background-color: {{VALUE}}',
				),
				'condition' => array(
					'arrows' => 'yes',
				),
			)
		);

		// Arrows color hover.
		$this->add_control(
			'arrows_color_hover',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Color', 'woostify-pro' ),
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .tns-controls [data-controls]:hover' => 'color: {{VALUE}}',
				),
				'condition' => array(
					'arrows' => 'yes',
				),
			)
		);

		// Tab hover end.
		$this->end_controls_tab();

		// END TAB CONTROLS.
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Generate Tiny slider settings
	 *
	 * @param      array $settings The widget settings.
	 * @param      int   $desktop  The col desktop.
	 * @param      int   $tablet   The col tablet.
	 * @param      int   $mobile   The col mobile.
	 *
	 * @return     string Tiny slider data
	 */
	private function tiny_slider_options( $settings, $desktop, $tablet, $mobile ) {

		// This function works only Carousel Layout.
		if ( 'carousel' !== $settings['layout'] ) {
			return '';
		}

		$gap = isset( $settings['columns_gap_carousel']['size'] ) ? absint( $settings['columns_gap_carousel']['size'] ) : 15;

		$options = array(
			'items'      => 3,
			'controls'   => 'yes' === $settings['arrows'] ? true : false,
			'nav'        => false,
			'loop'       => 'yes' === $settings['loop'] ? true : false,
			'autoHeight' => true,
			'gutter'     => 15,
			'mouseDrag'  => true,
			'responsive' => array(
				240  => array(
					'items'  => $mobile,
					'gutter' => isset( $settings['columns_gap_carousel_mobile']['size'] ) ? absint( $settings['columns_gap_carousel_mobile']['size'] ) : $gap,
				),
				767  => array(
					'items'  => $tablet,
					'gutter' => isset( $settings['columns_gap_carousel_tablet']['size'] ) ? absint( $settings['columns_gap_carousel_tablet']['size'] ) : $gap,
				),
				1024 => array(
					'items'  => $desktop,
					'gutter' => $gap,
				),
			),
		);

		$tiny_slider_options = "data-tiny-slider='" . wp_json_encode( $options ) . "'";

		return $tiny_slider_options;
	}

	/**
	 * Query
	 */
	private function section_query() {
		$this->start_controls_section(
			'product_query',
			array(
				'label' => esc_html__( 'Query', 'woostify-pro' ),
			)
		);

		// Category ids.
		$this->add_control(
			'category_ids',
			array(
				'label' => esc_html__( 'Category', 'woostify-pro' ),
				'type'  => 'autocomplete',
				'query' => array(
					'type' => 'term',
					'name' => 'product_cat',
				),
			)
		);

		// Exclude category ids.
		$this->add_control(
			'exclude_category_ids',
			array(
				'label' => esc_html__( 'Exclude Category', 'woostify-pro' ),
				'type'  => 'autocomplete',
				'query' => array(
					'type' => 'term',
					'name' => 'product_cat',
				),
			)
		);

		// Orderby.
		$this->add_control(
			'orderby',
			array(
				'label'   => esc_html__( 'Order By', 'woostify-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'name',
				'options' => array(
					'name'        => esc_html__( 'Name', 'woostify-pro' ),
					'slug'        => esc_html__( 'Slug', 'woostify-pro' ),
					'description' => esc_html__( 'Description', 'woostify-pro' ),
					'count'       => esc_html__( 'Count', 'woostify-pro' ),
				),
			)
		);

		// Order.
		$this->add_control(
			'order',
			array(
				'label'   => esc_html__( 'Order', 'woostify-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'ASC',
				'options' => array(
					'ASC'  => esc_html__( 'ASC', 'woostify-pro' ),
					'DESC' => esc_html__( 'DESC', 'woostify-pro' ),
				),
			)
		);

		// Subcategory.
		$this->add_control(
			'subcategory',
			array(
				'type'         => Controls_Manager::SWITCHER,
				'label'        => esc_html__( 'Display Subcategories', 'woostify-pro' ),
				'default'      => 'yes',
				'label_on'     => esc_html__( 'Yes', 'woostify-pro' ),
				'label_off'    => esc_html__( 'No', 'woostify-pro' ),
				'return_value' => 'yes',
			)
		);

		// Hide empty.
		$this->add_control(
			'hide_empty',
			array(
				'type'         => Controls_Manager::SWITCHER,
				'label'        => esc_html__( 'Hide Empty', 'woostify-pro' ),
				'default'      => 'yes',
				'label_on'     => esc_html__( 'Yes', 'woostify-pro' ),
				'label_off'    => esc_html__( 'No', 'woostify-pro' ),
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		// Layout.
		$layout = $settings['layout'];

		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => $settings['hide_empty'],
			'orderby'    => $settings['orderby'],
			'order'      => $settings['order'],
		);

		if ( 'yes' !== $settings['subcategory'] ) {
			$args['parent'] = 0;
		}

		$in_cat_id = empty( $settings['category_ids'] ) ? array() : $settings['category_ids'];
		$ex_cat_id = empty( $settings['exclude_category_ids'] ) ? array() : $settings['exclude_category_ids'];

		$cat_ids    = array_diff( $in_cat_id, $ex_cat_id );
		$ex_cat_ids = empty( $settings['category_ids'] ) && ! empty( $settings['exclude_category_ids'] ) ? $settings['exclude_category_ids'] : array();
		if ( ! empty( $cat_ids ) ) {
			$args['include'] = $cat_ids;
		} elseif ( ! empty( $ex_cat_ids ) ) {
			$args['exclude'] = $ex_cat_ids;
		}

		$product_cat = get_terms( $args );

		if ( empty( $product_cat ) ) {
			return;
		}

		// Grid.
		$columns        = isset( $settings['columns'] ) ? $settings['columns'] : 4;
		$columns_tablet = isset( $settings['columns_tablet'] ) ? $settings['columns_tablet'] : 2;
		$columns_mobile = isset( $settings['columns_mobile'] ) ? $settings['columns_mobile'] : 1;

		// Carousel settings for Preview mode.
		$tiny = $this->tiny_slider_options( $settings, $columns, $columns_tablet, $columns_mobile );

		// Classes.
		if ( 'grid' === $layout ) {
			$grid   = array();
			$grid[] = 'ht-grid'; // Defined grid.
			$grid[] = 'ht-grid-' . $columns; // On desktop.
			$grid[] = 'ht-grid-tablet-' . $columns_tablet; // On tablet.
			$grid[] = 'ht-grid-mobile-' . $columns_mobile; // On mobile.
			$grid[] = 'content-' . $settings['content_pos'];

			// Wrapper classes.
			$wrapper_classes = 'grid-layout';

			// Generate classes.
			$classes = implode( ' ', $grid );

			// Item classes.
			$item_classes = 'ht-grid-item';
		} else {
			$carousel   = array();
			$carousel[] = 'woostify-product-category-slider tns';
			$carousel[] = 'content-' . $settings['content_pos'];

			// Wrapper classes.
			$wrapper_classes = 'carousel-layout';

			// Generate classes.
			$classes = implode( ' ', $carousel );

			// Item classes.
			$item_classes = 'product-category-item tnsi';
		}
		?>

		<div class="woostify-product-category-widget <?php echo esc_attr( $wrapper_classes ); ?>">
			<div class="<?php echo esc_attr( $classes ); ?>" <?php echo wp_kses_post( $tiny ); ?>>
				<?php
				foreach ( $product_cat as $k ) {
					$img_id    = get_term_meta( $k->term_id, 'thumbnail_id', true );
					$img_alt   = woostify_image_alt( $img_id, __( 'Product category image', 'woostify-pro' ), true );
					$img_src   = wp_get_attachment_image_src( $img_id, $settings['image_size'] );
					$img_src   = $img_id ? $img_src[0] : wc_placeholder_img_src();
					$term_link = get_term_link( $k->term_id, 'product_cat' );

					// Content.
					$product_count = sprintf(
						/* translators: 1: number of comments, 2: post title */
						_nx( '%1$s Product', '%1$s Products', $k->count, 'product count', 'woostify-pro' ),
						$k->count
					);

					$content  = '<span class="pcw-info">';
					$content .= '<span class="pcw-info-inner">';
					// Add permalink.
					if ( 'outside' === $settings['content_pos'] ) {
						$content .= '<a class="pcw-link" href="' . esc_url( $term_link ) . '"></a>';
					}
					$content .= '<span class="pcw-title">' . esc_html( $k->name ) . '</span>';
					// Category count.
					if ( 'yes' === $settings['count'] ) {
						$content .= '<span class="pcw-count">' . esc_html( $product_count ) . '</span>';
					}
					// Custom button.
					if ( 'yes' === $settings['button'] ) {
						$content .= '<span class="pcw-button">' . esc_html( $settings['button_text'] ) . '</span>';
					}
					$content .= '</span>';
					$content .= '</span>';
					?>

					<div class="<?php echo esc_attr( $item_classes ); ?>">
						<div class="pcw-item">
							<a class="pcw-image" href="<?php echo esc_url( $term_link ); ?>">
								<img src="<?php echo esc_url( $img_src ); ?>" alt="<?php echo esc_attr( $img_alt ); ?>">

								<?php
								if ( 'inside' === $settings['content_pos'] ) {
									echo $content; // phpcs:ignore
								}

								if ( '' !== $settings['overlay_bg'] && 'inside' === $settings['content_pos'] ) {
									?>
									<span class="pcw-overlay"></span>
									<?php
								}
								?>
							</a>

							<?php
							if ( 'outside' === $settings['content_pos'] ) {
								echo $content; // phpcs:ignore
							}
							?>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}
}
Plugin::instance()->widgets_manager->register( new Woostify_Elementor_Product_Category_Widget() );
