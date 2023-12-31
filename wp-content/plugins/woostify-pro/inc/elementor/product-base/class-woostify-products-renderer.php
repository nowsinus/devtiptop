<?php
/**
 * Woostify Products Renderer
 *
 * @package Woostify Pro
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This class describes a woostify products renderer.
 */
class Woostify_Products_Renderer extends Woostify_Base_Products_Renderer {
	/**
	 * Settings
	 *
	 * @var $settings
	 */
	private $settings = array();

	/**
	 * Filter
	 *
	 * @var $is_added_product_filter
	 */
	private $is_added_product_filter = false;

	/**
	 * Constructs a new instance.
	 *
	 * @param      array  $settings The settings.
	 * @param      string $type     The type.
	 */
	public function __construct( $settings = array(), $type = 'products' ) {
		$this->settings   = $settings;
		$this->type       = $type;
		$this->attributes = $this->parse_attributes(
			array(
				'columns'  => $settings['col'],
				'paginate' => $settings['pro_pagi'],
				'cache'    => false,
			)
		);
		$this->query_args = $this->parse_query_args();
	}

	/**
	 * Override the original `get_query_results`
	 * with modifications that:
	 * 1. Remove `pre_get_posts` action if `is_added_product_filter`.
	 *
	 * @return bool|mixed|object
	 */
	protected function get_query_results() {
		$results = parent::get_query_results();

		// Start edit.
		if ( $this->is_added_product_filter ) {
			remove_action( 'pre_get_posts', array( wc()->query, 'product_query' ) );
		}
		// End edit.

		return $results;
	}

	/**
	 * Parse query
	 */
	protected function parse_query_args() {
		$settings = &$this->settings;

		$query_args = array(
			'post_type'   => 'product',
			'post_status' => 'publish',
			'cache'       => false,
			'orderby'     => $settings['order_by'],
			'order'       => $settings['order'],
		);

		// Query latest product.
		if ( 'latest' === $settings['source'] ) {
			$ordering_args         = WC()->query->get_catalog_ordering_args( $query_args['orderby'], $query_args['order'] );
			$query_args['orderby'] = $ordering_args['orderby'];
			$query_args['order']   = $ordering_args['order'];
		}

		$query_args['meta_query'] = WC()->query->get_meta_query(); // phpcs:ignore
		$query_args['tax_query']  = array(); // phpcs:ignore

		$front_page = is_front_page();
		if ( 'yes' === $settings['pro_pagi'] && 'yes' === $settings['ordering'] && ! $front_page ) {
			$ordering_args = WC()->query->get_catalog_ordering_args();
		} else {
			$ordering_args = WC()->query->get_catalog_ordering_args( $query_args['orderby'], $query_args['order'] );
		}

		$query_args['orderby'] = $ordering_args['orderby'];
		$query_args['order']   = $ordering_args['order'];
		if ( $ordering_args['meta_key'] ) {
			$query_args['meta_key'] = $ordering_args['meta_key']; // phpcs:ignore
		}

		// Visibility.
		$this->set_visibility_query_args( $query_args );

		// Set specific types query args.
		if ( method_exists( $this, "set_{$this->type}_query_args" ) ) {
			$this->{"set_{$this->type}_query_args"}( $query_args );
		}

		// Manual selection.
		$this->manual_select_query_args( $query_args );

		// Remove default notices.
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );

		// Pagination.
		if ( 'yes' === $settings['pro_pagi'] ) {
			$page = isset( $_GET['product-page'] ) ? intval( wp_unslash( $_GET['product-page'] ) ) : 1; // phpcs:ignore

			if ( 1 < $page ) {
				$query_args['paged'] = $page;
			}

			// Ordering.
			if ( 'yes' === $settings['ordering'] ) {
				add_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
			} else {
				remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
			}

			// Result count.
			if ( 'yes' === $settings['result_count'] ) {
				add_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
			} else {
				remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
			}
		}

		// Products columns.
		add_filter( 'woostify_product_catalog_columns', array( $this, 'set_product_columns' ) );

		// Fallback to the widget's default settings in case settings was left empty.
		$columns                      = empty( $settings['col'] ) ? 4 : $settings['col'];
		$query_args['posts_per_page'] = $settings['count'];

		// Update orderby.
		if ( isset( $query_args['orderby'] ) && in_array( $query_args['orderby'], array( 'rating', 'popularity', 'price' ), true ) ) {
			switch ( $query_args['orderby'] ) {
				case 'rating':
					$query_args['meta_key'] = '_wc_average_rating'; // phpcs:ignore
					$query_args['order']    = 'DESC';
					break;
				case 'popularity':
					$query_args['meta_key'] = 'total_sales'; // phpcs:ignore
					$query_args['order']    = 'DESC';
					break;
				default:
					$query_args['meta_key'] = '_price'; // phpcs:ignore
					break;
			}

			$query_args['orderby'] = 'meta_value_num';
		}

		// For stock status order by.
		if ( 'stock' === $settings['order_by'] ) {
			$query_args['orderby']  = 'meta_value';
			$query_args['meta_key'] = '_stock_status';
		}

		$query_args = apply_filters( 'woocommerce_shortcode_products_query', $query_args, $this->attributes, $this->type );

		// Always query only IDs.
		$query_args['fields'] = 'ids';

		// Support 'product-filter' addon.
		if ( class_exists( 'Woostify_Product_Filter' ) ) {
			$params_query = \Woostify_Product_Filter::init()->get_active_data( 'args' );
			$query_args   = wp_parse_args( $params_query, $query_args );
		}

		return $query_args;
	}

	/**
	 * Product columns
	 *
	 * @param string $class The product class.
	 */
	public function set_product_columns( $class ) {
		$settings = &$this->settings;
		$classs[] = 'products';
		$classs[] = 'columns-' . $settings['col'];
		$classs[] = 'tablet-columns-' . $settings['col_tablet'];
		$classs[] = 'mobile-columns-' . $settings['col_mobile'];

		return esc_attr( implode( ' ', $classs ) );
	}

	/**
	 * Manual selection
	 *
	 * @param      array $query_args  The query arguments.
	 */
	protected function manual_select_query_args( &$query_args ) {
		$settings = &$this->settings;
		if ( 'current_query' === $settings['source'] ) {
			return;
		}

		switch ( $settings['source'] ) {
			case 'sale':
				parent::set_sale_products_query_args( $query_args );
				$post__in = wc_get_product_ids_on_sale();
				if ( ! empty( $post__in ) ) {
					$query_args['post__in'] = $post__in;
					remove_action( 'pre_get_posts', array( wc()->query, 'product_query' ) );
				}
				break;
			case 'featured':
				$product_visibility_term_ids = wc_get_product_visibility_term_ids();
				if ( ! empty( $product_visibility_term_ids['featured'] ) ) {
					$query_args['tax_query'][] = array(
						'taxonomy' => 'product_visibility',
						'field'    => 'term_taxonomy_id',
						'terms'    => array( $product_visibility_term_ids['featured'] ),
					);
				}
				break;
			case 'select':
				if ( ! empty( $settings['include_products'] ) ) {
					$query_args['post__in'] = $settings['include_products'];
				}
				break;
		}

		// Not apply for 'select' query select product.
		if ( in_array( $settings['source'], array( 'sale', 'featured', 'latest' ), true ) ) {
			// Products.
			if ( ! empty( $settings['exclude_products'] ) ) {
				$query_args['post__not_in'] = empty( $query_args['post__in'] ) ? $settings['exclude_products'] : array_diff( $query_args['post__in'], $settings['exclude_products'] );
			}

			// Terms.
			global $wp_taxonomies;
			$in_term_id = empty( $settings['include_terms'] ) ? array() : $settings['include_terms'];
			$ex_term_id = empty( $settings['exclude_terms'] ) ? array() : $settings['exclude_terms'];

			$include_term_ids = array_diff( $in_term_id, $ex_term_id );
			$exclude_term_ids = ! empty( $ex_term_id ) ? $ex_term_id : array();

			if ( ! empty( $include_term_ids ) ) {
				$tax_query_group = array(
					'relation' => 'OR',
				);
				foreach ( $include_term_ids as $in ) {
					$in_term = get_term( $in );

					$tax_query_group[] = array(
						'taxonomy' => $wp_taxonomies[ $in_term->taxonomy ]->name,
						'field'    => 'term_id',
						'terms'    => $in,
					);
				}
				$query_args['tax_query'][] = $tax_query_group;
			}
			if ( ! empty( $exclude_term_ids ) ) {
				foreach ( $exclude_term_ids as $ex ) {
					$ex_term = get_term( $ex );

					$query_args['tax_query'][] = array(
						'taxonomy' => $wp_taxonomies[ $ex_term->taxonomy ]->name,
						'field'    => 'term_id',
						'terms'    => $ex,
						'operator' => 'NOT IN',
					);
				}
			}
		}
	}
}
