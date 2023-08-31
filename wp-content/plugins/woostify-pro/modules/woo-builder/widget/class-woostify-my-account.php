<?php
/**
 * Elementor My Account Widget
 *
 * @package Woostify Pro
 */

namespace Elementor;

use Elementor\Core\Files\File_Types\Svg;
use Elementor\Core\Page_Assets\Data_Managers\Font_Icon_Svg\Manager as Font_Icon_Svg_Data_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class widget.
 */
class Woostify_My_Account extends Widget_Base {
	/**
	 * Category
	 */
	public function get_categories() {
		return array( 'woostify-my-account-page' );
	}

	/**
	 * Name
	 */
	public function get_name() {
		return 'woostify-my-account';
	}

	/**
	 * Style
	 */
	public function get_style_depends() {
		return array( 'elementor-font-awesome' );
	}

	/**
	 * Script
	 */
	public function get_script_depends() {
		return array( 'woostify-my-account-widget' );
	}

	/**
	 * Gets the title.
	 */
	public function get_title() {
		return __( 'Woostify - My Account', 'woostify-pro' );
	}

	/**
	 * Gets the icon.
	 */
	public function get_icon() {
		return 'eicon-navigator';
	}

	/**
	 * Gets the keywords.
	 */
	public function get_keywords() {
		return array( 'woostify', 'woocommerce', 'shop', 'account', 'user', 'store' );
	}

	/**
	 * Get saved tempalte
	 */
	public function get_saved_tempalte() {
		$arr         = woostify_narrow_data( 'post', 'elementor_library' );
		$arr['none'] = __( 'None', 'woostify-pro' );

		return $arr;
	}

	/**
	 * Get menu items
	 */
	public function get_menu_items() {
		$arr           = wc_get_account_menu_items();
		$arr['custom'] = __( 'Custom', 'woostify-pro' );

		return $arr;
	}

	/**
	 * Navigation
	 */
	protected function navigation() {
		$this->start_controls_section(
			'repeater',
			array(
				'label' => __( 'Navigation', 'woostify-pro' ),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'nav_title',
			array(
				'label'       => __( 'Title', 'woostify-pro' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Dashboard', 'woostify-pro' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'nav_item',
			array(
				'label'   => __( 'Menu Item', 'woostify-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dashboard',
				'options' => $this->get_menu_items(),
			)
		);

		$repeater->add_control(
			'custom_tempate',
			array(
				'label'     => __( 'Template', 'woostify-pro' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'none',
				'options'   => $this->get_saved_tempalte(),
				'condition' => array(
					'nav_item' => 'custom',
				),
			)
		);

		$repeater->add_control(
			'nav_url',
			array(
				'label'       => __( 'Url', 'woostify-pro' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( '#', 'woostify-pro' ),
				'label_block' => true,
				'conditions'  => array(
					'relation' => 'and',
					'terms'    => array(
						array(
							'name'     => 'nav_item',
							'operator' => '===',
							'value'    => 'custom',
						),
						array(
							'name'     => 'custom_tempate',
							'operator' => '===',
							'value'    => 'none',
						),
					),
				),
			)
		);

		$repeater->add_control(
			'nav_icon',
			array(
				'label'   => __( 'Icon', 'woostify-pro' ),
				'type'    => Controls_Manager::ICONS,
				'default' => array(
					'value' => 'fas fa-shopping-cart',
				),
			)
		);

		$this->add_control(
			'navigation',
			array(
				'show_label'  => false,
				'title_field' => '{{{ nav_title }}}',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'nav_title' => __( 'Dashboard', 'woostify-pro' ),
						'nav_item'  => 'dashboard',
						'nav_icon'  => array(
							'value' => 'fas fa-cogs',
						),
					),
					array(
						'nav_title' => __( 'Orders', 'woostify-pro' ),
						'nav_item'  => 'orders',
						'nav_icon'  => array(
							'value' => 'fas fa-list-ul',
						),
					),
					array(
						'nav_title' => __( 'Download', 'woostify-pro' ),
						'nav_item'  => 'downloads',
						'nav_icon'  => array(
							'value' => 'fas fa-download',
						),
					),
					array(
						'nav_title' => __( 'Address', 'woostify-pro' ),
						'nav_item'  => 'edit-address',
						'nav_icon'  => array(
							'value' => 'fas fa-address-book',
						),
					),
					array(
						'nav_title' => __( 'Account Details', 'woostify-pro' ),
						'nav_item'  => 'edit-account',
						'nav_icon'  => array(
							'value' => 'fas fa-users-cog',
						),
					),
					array(
						'nav_title' => __( 'Logout', 'woostify-pro' ),
						'nav_item'  => 'customer-logout',
						'nav_icon'  => array(
							'value' => 'fas fa-sign-out-alt',
						),
					),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Menu items
	 */
	protected function menu_items() {
		// Start.
		$this->start_controls_section(
			'start',
			array(
				'label' => __( 'Tab Head', 'woostify-pro' ),
			)
		);

		$this->add_control(
			'head_position',
			array(
				'label'   => __( 'Position', 'woostify-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'top',
				'options' => array(
					'top'    => __( 'Top', 'woostify-pro' ),
					'right'  => __( 'Right', 'woostify-pro' ),
					'bottom' => __( 'Bottom', 'woostify-pro' ),
					'left'   => __( 'Left', 'woostify-pro' ),
				),
			)
		);

		$this->add_control(
			'icon_position',
			array(
				'label'   => __( 'Icon Position', 'woostify-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => array(
					'left'  => __( 'Left', 'woostify-pro' ),
					'right' => __( 'Right', 'woostify-pro' ),
				),
			)
		);

		$this->add_responsive_control(
			'icon_space',
			array(
				'label'      => __( 'Icon Space', 'woostify-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'em' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .has-icon-left .account-menu-item-icon'  => 'margin-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .has-icon-right .account-menu-item-icon' => 'margin-left: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'icon_svg_width',
			array(
				'label'      => __( 'Icon SVG Width', 'woostify-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'em' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 17,
				),
				'selectors'  => array(
					'{{WRAPPER}} .account-menu-item-icon svg'  => 'width: {{SIZE}}{{UNIT}}; vertical-align: middle;',
				),
			)
		);

		$this->add_responsive_control(
			'head_width',
			array(
				'label'      => __( 'Width', 'woostify-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'conditions' => array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'     => 'head_position',
							'operator' => '===',
							'value'    => 'left',
						),
						array(
							'name'     => 'head_position',
							'operator' => '===',
							'value'    => 'right',
						),
					),
				),
				'range'      => array(
					'px' => array(
						'min'  => 200,
						'max'  => 500,
						'step' => 1,
					),
					'%'  => array(
						'min' => 0,
						'max' => 70,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .woostify-my-account-tab-head'    => 'width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .woostify-my-account-tab-content' => 'width: calc( 100% - {{SIZE}}{{UNIT}} );',
				),
			)
		);

		$this->add_control(
			'head_inline',
			array(
				'label'        => __( 'Inline display', 'woostify-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'woostify-pro' ),
				'label_off'    => __( 'No', 'woostify-pro' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'conditions'   => array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'     => 'head_position',
							'operator' => '===',
							'value'    => 'top',
						),
						array(
							'name'     => 'head_position',
							'operator' => '===',
							'value'    => 'bottom',
						),
					),
				),
			)
		);

		$this->add_control(
			'head_bg',
			array(
				'label'     => __( 'Background color', 'woostify-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .woostify-my-account-tab-head' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_responsive_control(
			'head_align',
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
					'{{WRAPPER}} .woostify-my-account-tab-head' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'head_margin',
			array(
				'label'      => __( 'Margin', 'woostify-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .woostify-my-account-tab-head' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'for_menu_items',
			array(
				'label'     => __( 'Menu Items', 'woostify-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'color',
			array(
				'label'     => __( 'Text color', 'woostify-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .account-menu-item a' => 'color: {{VALUE}};fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'highlight_color',
			array(
				'label'     => __( 'Highlight color', 'woostify-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .account-menu-item a:hover, {{WRAPPER}} .account-menu-item.active a' => 'color: {{VALUE}};fill: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'tab_head_typo',
				'selector' => '{{WRAPPER}} .account-menu-item a',
			)
		);

		$this->add_responsive_control(
			'item_margin',
			array(
				'label'      => __( 'Margin', 'woostify-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .account-menu-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Tab content
	 */
	protected function tab_content() {
		$this->start_controls_section(
			'tab_content',
			array(
				'label' => __( 'Tab Content', 'woostify-pro' ),
			)
		);

		$this->add_control(
			'tab_content_bg',
			array(
				'label'     => __( 'Background color', 'woostify-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .woostify-my-account-tab-content' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_responsive_control(
			'tab_content_align',
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
					'{{WRAPPER}} .woostify-my-account-tab-content' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'tab_content_padding',
			array(
				'label'      => __( 'Padding', 'woostify-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .woostify-my-account-tab-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'tab_content_margin',
			array(
				'label'      => __( 'Margin', 'woostify-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'separator'  => 'after',
				'selectors'  => array(
					'{{WRAPPER}} .woostify-my-account-tab-content' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'tab_content_color',
			array(
				'label'     => __( 'Text color', 'woostify-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .woostify-my-account-tab-content' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'tab_content_link_color',
			array(
				'label'     => __( 'Link color', 'woostify-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .woostify-my-account-tab-content a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'tab_content_link_hover_color',
			array(
				'label'     => __( 'Link Hover Color', 'woostify-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .woostify-my-account-tab-content a:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'tab_content_typo',
				'selector' => '{{WRAPPER}} .woostify-my-account-tab-content',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Controls
	 */
	protected function register_controls() {
		$this->navigation();
		$this->menu_items();
		$this->tab_content();
	}

	/**
	 * Isset endpoint
	 */
	public function isset_endpoint() {
		global $wp;
		$items = $this->get_menu_items();
		if ( empty( $items ) ) {
			return false;
		}

		$current = false;
		foreach ( $items as $k => $v ) {
			if ( isset( $wp->query_vars[ $k ] ) ) {
				$current = true;
				break;
			}
		}

		return $current;
	}

	/**
	 * Render uploaded svg icon.
	 *
	 * @param string $value value.
	 */
	public function render_uploaded_svg_icon( $value ) {
		if ( ! isset( $value['id'] ) ) {
			return '';
		}

		return Svg::get_inline_svg( $value['id'] );
	}

	/**
	 * Render
	 */
	public function render() {
		$settings = $this->get_settings_for_display();
		if ( empty( $settings['navigation'] ) ) {
			return;
		}

		global $wp;
		$is_view_order        = empty( $wp->query_vars['view-order'] ) ? false : $wp->query_vars['view-order'];
		$is_view_subscription = empty( $wp->query_vars['view-subscription'] ) ? false : $wp->query_vars['view-subscription'];
		$head_inline          = in_array( $settings['head_position'], array( 'top', 'bottom' ), true ) && $settings['head_inline'] ? ' head-inline' : '';

		?>
		<div class="woostify-my-account-widget position-<?php echo esc_attr( $settings['head_position'] ); ?><?php echo esc_attr( $head_inline ); ?>">
			<div class="woostify-my-account-tab-head">
				<?php
				foreach ( $settings['navigation'] as $k => $v ) {
					$nav_item    = $v['nav_item'];
					$current_nav = isset( $wp->query_vars[ $nav_item ] );
					$url         = isset( $nav_item ) ? wc_get_account_endpoint_url( $nav_item ) : '#';
					if ( 'custom' === $nav_item ) {
						$url = isset( $v['nav_url'] ) ? $v['nav_url'] : '#';
					}

					$icon_position = 'has-icon-left';
					$label         = $v['nav_title'];

					$icon = '';
					if ( isset( $v['nav_icon'] ) && ! empty( $v['nav_icon']['value'] ) ) {
						if ( 'svg' === $v['nav_icon']['library'] ) {
							$icon = $this->render_uploaded_svg_icon( $v['nav_icon']['value'] );

							if ( $icon && 'left' === $settings['icon_position'] ) {
								$label = '<span class="account-menu-item-icon">' . $icon . '</span>' . $v['nav_title'];
							} elseif ( $icon && 'right' === $settings['icon_position'] ) {
								$icon_position = 'has-icon-right';
								$label         = $v['nav_title'] . '<span class="account-menu-item-icon">' . $icon . '</span>';
							}
						} else {
							$icon = $v['nav_icon']['value'];

							if ( $icon && 'left' === $settings['icon_position'] ) {
								$label = '<span class="account-menu-item-icon ' . esc_attr( $icon ) . '"></span>' . $v['nav_title'];
							} elseif ( $icon && 'right' === $settings['icon_position'] ) {
								$icon_position = 'has-icon-right';
								$label         = $v['nav_title'] . '<span class="account-menu-item-icon ' . esc_attr( $icon ) . '"></span>';
							}
						}
					}

					$nav_class   = array();
					$nav_class[] = 'account-menu-item';
					$nav_class[] = 'account-menu-item-' . $nav_item;
					$nav_class[] = $icon_position;

					// Active class.
					if ( $is_view_order ) {
						if ( 'orders' === $nav_item ) {
							$nav_class[] = 'active no-prevent';
						}
					} else {
						$nav_class[] = $current_nav ? 'active' : ( 'dashboard' === $nav_item && ! $this->isset_endpoint() ? 'active' : '' );
					}
					?>
					<div class="<?php echo esc_attr( implode( ' ', array_filter( $nav_class ) ) ); ?>">
						<a data-id="tab-<?php echo esc_attr( $v['_id'] ); ?>" href="<?php echo esc_url( $url ); ?>">
							<?php echo wp_kses_post( $label ); ?>
						</a>
					</div>
				<?php } ?>
			</div>

			<div class="woostify-my-account-tab-content">
				<?php

				if ( woostify_is_elementor_editor() ) {
					WC()->session = new \WC_Session_Handler();
					WC()->session->init();
					WC()->customer = new \WC_Customer( get_current_user_id(), true );
				}

				foreach ( $settings['navigation'] as $k => $v ) {
					$nav_item = $v['nav_item'];
					if ( 'customer-logout' === $nav_item || ( 'custom' === $nav_item && 'none' === $v['custom_tempate'] ) ) {
						continue;
					}

					$current_tab = isset( $wp->query_vars[ $nav_item ] );
					$tab_class   = array();
					$tab_class[] = 'my-account-tab-content-item';

					// Active class.
					if ( $is_view_order ) {
						if ( 'orders' === $nav_item ) {
							$tab_class[] = 'active';
						}
					} elseif ( $is_view_subscription ) {
						if ( 'custom' === $nav_item ) {
							$tab_class[] = 'active';
						}
					} else {
						if ( 'payment-methods' === $nav_item ) {
							$tab_class[] = 'payment-methods';
						}
						$tab_class[] = $current_tab ? 'active' : ( 'dashboard' === $nav_item && ! $this->isset_endpoint() ? 'active' : '' );
					}
					?>

					<div class="<?php echo esc_attr( implode( ' ', array_filter( $tab_class ) ) ); ?>" id="tab-<?php echo esc_attr( $v['_id'] ); ?>">
						<?php
						switch ( $nav_item ) {
							case 'dashboard':
								wc_get_template(
									'myaccount/dashboard.php',
									array(
										'current_user' => get_user_by( 'id', get_current_user_id() ),
									)
								);

								break;
							case 'custom':
								$frontend = new \Elementor\Frontend();
								if ( $is_view_subscription ) {
									do_action( 'woocommerce_account_view-subscription_endpoint', $is_view_subscription );
									\WCS_Template_Loader::get_view_subscription_template( $is_view_subscription );
								} else {
									echo $frontend->get_builder_content_for_display( $v['custom_tempate'], true ); // phpcs:ignore
								}
								break;
							case 'orders':
								if ( $is_view_order ) {
									woocommerce_account_view_order( $is_view_order );
								} else {
									do_action( 'woocommerce_account_' . $nav_item . '_endpoint' );
								}
								break;
							case 'edit-address':
								$load_address = isset( $wp->query_vars[ $nav_item ] ) ? wc_edit_address_i18n( sanitize_title( $wp->query_vars[ $nav_item ] ), true ) : 'billing';
								if ( ! empty( $wp->query_vars[ $nav_item ] ) && $load_address ) {
									woocommerce_account_edit_address( $load_address );
								} else {
									do_action( 'woocommerce_account_' . $nav_item . '_endpoint' );
								}
								break;
							case 'payment-methods':
								woocommerce_account_add_payment_method();
								echo '<div class="payment-methods-content">';
								do_action( 'woocommerce_account_' . $nav_item . '_endpoint' );
								echo '</div>';
								break;
							default:
								do_action( 'woocommerce_account_' . $nav_item . '_endpoint' );
								break;
						}
						?>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}
}
Plugin::instance()->widgets_manager->register( new Woostify_My_Account() );
