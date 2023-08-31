<?php
/**
 * Product filter render
 *
 * @package Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_Filter_Render' ) ) {
	/**
	 * Main class
	 */
	class Woostify_Filter_Render {

		/**
		 * Instance Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 *  Initiator
		 */
		public static function init() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_notices', array( $this, 'add_notice' ) );
		}

		/**
		 * Add notice.
		 */
		public function add_notice() {
			if ( ! $this->isset_table() ) {
				?>
				<div class="notice notice-error message woostify-filter-index-notice woostify-notice">
					<div class="notice-content-wrapper">
						<div class="notice-logo">
							<img src="<?php echo esc_url( WOOSTIFY_PRO_URI . 'assets/images/logo.png' ); ?>" alt="<?php esc_attr_e( 'Woostify', 'woostify-pro' ); ?>">
						</div>

						<div class="notice-content">
							<h2 class="notice-head"><?php esc_html_e( 'Important Setup!', 'woostify-pro' ); ?></h2>

							<span class="notice-indexer">
								<?php echo wp_kses_post( /* translators: Strong html tag */sprintf( __( 'Woostify Smart Product Filter requires setup to work, please go to Smart Product Filter page and click <%1$s>Index Data button.</%1$s>', 'woostify-pro' ), 'strong' ) ); ?>
							</span>

							<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-product-filter-settings' ) ); ?>">
								<?php esc_html_e( 'Index Now', 'woostify-pro' ); ?>
							</a>

							<span class="btn admin-btn btn-close-notice notice-dismiss"></span>
						</div>
					</div>
				</div>
				<?php
			}
		}

		/**
		 * Sort taxonomy values.
		 *
		 * @param  array $values The values.
		 */
		public function sort_taxonomy_values( $values = array() ) {
			$final = array();
			$cache = array();

			// Create an "order" sort value based on the top-level items.
			foreach ( $values as $key => $val ) {
				if ( ! $val['depth'] ) {
					$val['order']             = $key;
					$cache[ $val['term_id'] ] = $key;
					$final[]                  = $val;
				} elseif ( isset( $cache[ $val['parent_id'] ] ) ) {
					$val['order']             = $cache[ $val['parent_id'] ] . ".$key";
					$cache[ $val['term_id'] ] = $val['order'];
					$final[]                  = $val;
				}
			}

			// Sort the array based on the new "order" element.
			// Since this is a dot-separated hierarchy string, use version_compare.
			usort(
				$final,
				function ( $a, $b ) {
					return version_compare( $a['order'], $b['order'] );
				}
			);

			return $final;
		}

		/**
		 * Render hierarchical.
		 *
		 * @param int     $filter_id      The filter id.
		 * @param array   $output         The array output values.
		 * @param array   $selected_value The selected value.
		 * @param boolean $expand_default Expand by default.
		 */
		public function render_hierarchical( $filter_id, $output, $selected_value, $expand_default ) {
			$product_filter      = Woostify_Product_Filter::init();
			$options             = $product_filter->get_options();
			$quick_search        = get_post_meta( $filter_id, 'woostify_product_filter_quick_search', true );
			$quick_search_holder = get_post_meta( $filter_id, 'woostify_product_filter_quick_search_holder', true );
			$limit               = (int) $product_filter->set_value( $filter_id, 'woostify_product_filter_limit', 0 );
			$init_depth          = -1;
			$last_depth          = -1;

			// Quick search.
			if ( $quick_search ) {
				?>
				<input type="text" class="w-filter-quick-search w-product-filter-text-field" placeholder="<?php echo esc_attr( $quick_search_holder ); ?>">
				<?php
			}

			$values = $this->sort_taxonomy_values( $output );
			foreach ( $values as $key => $value ) {
				// Set limit.
				if ( $limit && $key >= $limit ) {
					break;
				}

				$depth            = (int) $value['depth'];
				$checkbox_id      = (int) $value['term_id'];
				$checkbox_name    = $value['term_name'];
				$checkbox_html_id = uniqid( $value['term_id'] . $key );

				if ( -1 === $last_depth ) {
					$init_depth = $depth;
				} elseif ( $depth > $last_depth ) {
					?>
					<div class="w-filter-item-depth<?php echo esc_attr( $expand_default ? ' visible' : '' ); ?>">
						<?php
				} elseif ( $depth < $last_depth ) {
					for ( $i = $last_depth; $i > $depth; $i-- ) {
						?>
					</div>
						<?php
					}
				}

				$slug_attr = '';
				$term      = get_term_by( 'id', $value['term_id'], 'product_cat' );
				$slug      = $term->slug;
				if ( ! empty( $slug ) ) {
					$slug_attr = 'data-slug=' . esc_attr( $slug );
				}

				$checked_input = ! empty( $selected_value ) && in_array( $slug, $selected_value, true ) ? 'checked' : '';
				?>
				<div class="w-filter-item-wrap">
					<label class="w-filter-item" for="<?php echo esc_attr( $checkbox_html_id ); ?>" <?php echo esc_attr( $slug_attr ); ?> data-id="<?php echo esc_attr( $checkbox_id ); ?>">
						<input class="w-filter-item-input" id="<?php echo esc_attr( $checkbox_html_id ); ?>" <?php echo esc_attr( $checked_input ); ?> type="checkbox" name="w-filter-checkbox-<?php echo esc_attr( $value['term'] ); ?>">
						<span class="w-filter-item-name"><?php echo esc_html( $checkbox_name ); ?></span>

						<?php if ( $options['general_item_count'] ) { ?>
							<span class="w-filter-item-count">( <?php echo esc_html( $value['count'] ); ?> )</span>
						<?php } ?>
					</label>
				</div>
				<?php
				$last_depth = $depth;
			}

			for ( $i = $last_depth; $i > $init_depth; $i-- ) {
				?>
			</div>
				<?php
			}
		}

		/**
		 * Check table isset
		 */
		public function isset_table() {
			global $wpdb;

			$table_name  = Woostify_Product_Filter::init()->table_name();
			$isset_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // phpcs:ignore

			return $isset_table;
		}

		/**
		 * Get term count
		 *
		 * @param  int $filter_id The filter id.
		 * @param  int $term_id   The term id.
		 * @param  int $count     The term count.
		 */
		public function get_term_count( $filter_id, $term_id, $count ) {
			$count = (int) $count;
			$type  = get_post_meta( $filter_id, 'woostify_product_filter_type', true );

			// Return default count number if this option disabled.
			if ( 'yes' !== get_option( 'woocommerce_hide_out_of_stock_items' ) && 'stock' !== $type ) {
				return $count;
			}

			$product_filter = Woostify_Product_Filter::init();
			$options        = $product_filter->get_options();
			if ( ! $options['general_item_count'] ) {
				return false;
			}

			// If not product attributes, return default count.
			$source = get_post_meta( $filter_id, 'woostify_product_filter_data', true );

			if ( ! is_numeric( $source ) && 'stock' !== $type ) {
				return $count;
			}

			$args = array(
				'post_type'      => 'product',
				'posts_status'   => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);

			if ( 'stock' !== $type ) {
				$attr = wc_get_attribute( $source );
				if ( empty( $attr ) || is_wp_error( $attr ) ) {
					return $count;
				}

				$taxonomy = $attr->slug;

				// Query only variable product.
				$args['tax_query'][] = array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'variable',
				);

				$args['tax_query'][] = array(
					'taxonomy' => $taxonomy,
					'terms'    => $term_id,
				);
			} else {
				$queried_object = get_queried_object();

				if ( 'WP_Term' == get_class( $queried_object ) ) {
					$args['tax_query'][] = array(
						'taxonomy'         => $queried_object->taxonomy,
						'field'            => 'id',
						'terms'            => array( $queried_object->term_id ),
						'include_children' => true,
						'operator'         => 'IN'
					);
				}
				if ( 'onsale' === $term_id ) {
					$args['meta_query'][] = array(
						'key'     => '_sale_price',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'numeric',
					);
				} else {
					$args['meta_query'][] = array(
						'key'     => '_stock_status',
						'value'   => $term_id,
						'compare' => '=',
					);
				}
			}

			$ids = get_posts( $args );
			if ( empty( $ids ) ) {
				return $count;
			}

			$output = array();
			foreach ( $ids as $pid ) {
				$product  = wc_get_product( $pid );
				$children = $product->get_children();


				if ( empty( $children ) ) {
					continue;
				}
				if ( 'stock' !== $type ) {
					foreach ( $children as $cid ) {
						$attr = wc_get_product_variation_attributes( $cid );
						$name = isset( $attr[ "attribute_$taxonomy" ] ) ? $attr[ "attribute_$taxonomy" ] : false;
						if ( ! $name ) {
							continue;
						}

						$term = get_term_by( 'slug', $name, $taxonomy );
						if ( empty( $term ) || is_wp_error( $term ) ) {
							continue;
						}

						$stock = get_post_meta( $cid, '_stock_status', true );
						if ( $term->term_id === $term_id && 'outofstock' === $stock && ! isset( $output[ $pid ] ) ) {
							array_push( $output, $pid );
						}
					}
				}
			}

			if ( 'stock' !== $type ) {
				$result = $count - count( $output );
				$result = $count;
			} else {
				$result = count( $ids );
			}

			return $result;
		}

		/**
		 * Get all product ids of term_id ( include all term children id )
		 *
		 * @param  string|int $term_id   The term id.
		 * @param  string     $term_name The term name.
		 */
		public function get_product_ids_from_term_ids( $term_id, $term_name ) {
			$product_filter = Woostify_Product_Filter::init();
			$child_ids      = get_term_children( $term_id, $term_name );
			if ( is_wp_error( $child_ids ) ) {
				return (array) $term_id;
			}

			$child_ids[] = $term_id;

			$output = array();
			foreach ( $child_ids as $pid ) {
				$get_ids = $product_filter->get_product_ids_by_term( '', $pid );
				$output  = array_merge( $output, $get_ids );
			}

			return array_unique( $output );
		}

		/**
		 * Get all product ids from current query
		 */
		public function get_posts_id_from_current_query() {
			global $wp_query;

			$args                   = $wp_query->query_vars;
			$args['fields']         = 'ids';
			$args['tax_query'][]    = $wp_query->tax_query;
			$args['posts_per_page'] = 999999999; // phpcs:ignore

			return get_posts( $args );
		}

		/**
		 * Get product ids on search results
		 */
		public function get_product_ids_on_search_result() {
			global $wp_query;

			$args = array(
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'post_type'      => 'product',
				'fields'         => 'ids',
				's'              => esc_html( $wp_query->query['s'] ),
			);

			return get_posts( $args );
		}

		/**
		 * Render filter
		 *
		 * @param int|string   $filter_id      The filter ID.
		 * @param array        $product_ids    The parameters.
		 * @param string|array $selected_value The current value.
		 * @param array        $data           The query data.
		 * @param boolean      $empty          The query result.
		 * @param int          $is_tax         Detect is taxonomy.
		 */
		public function render_filter( $filter_id = false, $product_ids = array(), $selected_value = false, $data = array(), $empty = false, $is_tax = false, $taxonomy = false ) {
			if ( ! $filter_id || ! $this->isset_table() ) {
				return;
			}

			global $wpdb, $wp_query;
			$product_filter = Woostify_Product_Filter::init();
			$table_name     = $product_filter->table_name();
			$options        = $product_filter->get_options();
			$get_params     = $_GET; // phpcs:ignore
			$slug           = '';

			// Out of stock prooduct ids.
			$outstock_ids = $product_filter->get_outstock_product_ids();

			// Get data form url.
			$slug           = '_' . str_replace( '-', '_', basename( get_permalink( $filter_id ) ) );
			$get_url_params = isset( $get_params[ $slug ] ) ? htmlspecialchars( $get_params[ $slug ], ENT_QUOTES ) : false;

			// Detect filter value on url params.
			if ( $product_filter->detect_url_params() ) {
				$product_ids = $this->get_posts_id_from_current_query();
			}

			// Filter args.
			$args                = array( 'hide_empty' => true );
			$type                = get_post_meta( $filter_id, 'woostify_product_filter_type', true );
			$label               = get_post_meta( $filter_id, 'woostify_product_filter_label', true );
			$label               = '' !== $label ? $label : get_the_title( $filter_id );
			$source              = 'stock' === $type ? 'stock' : get_post_meta( $filter_id, 'woostify_product_filter_data', true );
			$native_source       = $source;
			$quick_search        = get_post_meta( $filter_id, 'woostify_product_filter_quick_search', true );
			$quick_search_holder = get_post_meta( $filter_id, 'woostify_product_filter_quick_search_holder', true );
			$quick_search_output = $quick_search ? '<input type="text" class="w-filter-quick-search w-product-filter-text-field" placeholder="' . $quick_search_holder . '">' : '';
			$quick_search_allow  = array(
				'input' => array(
					'class'       => true,
					'type'        => true,
					'placeholder' => true,
				),
			);
			$condition_select    = get_post_meta( $filter_id, 'woostify_product_filter_term_condition_select', true );
			$condition_field     = get_post_meta( $filter_id, 'woostify_product_filter_term_condition_field', true );
			$limit               = (int) $product_filter->set_value( $filter_id, 'woostify_product_filter_limit', 0 );
			$soft_limit          = (int) $product_filter->set_value( $filter_id, 'woostify_product_filter_soft_limit', 0 );
			$need_soft_limit     = ! ( $limit && $soft_limit && $limit <= $soft_limit );
			$no_posts            = '<span class="woocommerce-info">' . esc_html__( 'No posts found!', 'woostify-pro' ) . '</span>';
			$filter_sort_by      = get_post_meta( $filter_id, 'woostify_product_filter_sort_by', true );
			$orderby             = 'count DESC, term_name ASC';

			// Get product attributes.
			$variation_id = '';
			if ( is_numeric( $source ) ) {
				$attr = wc_get_attribute( $source );

				if ( ! empty( $attr ) && ! is_wp_error( $attr ) ) {
					$source = $attr->slug;
				}
			}
			// Term condition.
			$where_term_ids      = '';
			$product_taxonomy_id = $is_tax ? (array) $is_tax : ( isset( get_queried_object()->term_id ) ? (array) get_queried_object()->term_id : array() ); // $is_tax for ajax load.
			$condition_term      = array();
			if ( $condition_select && $condition_field ) {
				$condition_field = explode( PHP_EOL, $condition_field );
				$term_arr        = array();

				foreach ( $condition_field as $name ) {
					$condi_term = get_term_by( 'name', $name, $source );
					if ( ! $condi_term ) {
						continue;
					}

					$term_arr[] = $condi_term->term_id;
				}

				if ( ! empty( $term_arr ) ) {
					$condition_term[ $condition_select ] = $term_arr;
				}
			}

			if ( $is_tax || ( ! empty( get_queried_object() ) && isset( get_queried_object()->term_id ) ) ) {
				$checkterm = get_term( $product_taxonomy_id[0], $taxonomy );
				if ( ! $taxonomy ) {
					$checkcat = get_the_category_by_ID( $product_taxonomy_id[0] );
				}

				if ( ( ! $checkcat ) && $checkterm && $checkterm->count == 0 ) { //phpcs:ignore
					$empty = true;
				}
			}

			$all_ids_in_term = '';
			if ( ! empty( $condition_term ) ) {
				if ( empty( $product_taxonomy_id ) ) {
					if ( isset( $condition_term['include'] ) ) {
						$where_term_ids = 'AND term_id IN (' . implode( ',', $condition_term['include'] ) . ')';
					} elseif ( ! empty( $condition_term['exclude'] ) ) {
						$where_term_ids = ' AND term_id NOT IN (' . implode( ',', $condition_term['exclude'] ) . ')';
					}
				} else {
					if ( isset( $condition_term['include'] ) ) {

						$where_term_ids = 'AND term_id IN (' . implode( ',', $condition_term['include'] ) . ')';
					} elseif ( isset( $condition_term['exclude'] ) ) {
						$where_term_ids = 'AND term_id IN (' . implode( ',', $product_taxonomy_id ) . ')';

						if ( ! empty( $condition_term['exclude'] ) ) {
							$where_term_ids .= ' AND term_id NOT IN (' . implode( ',', $condition_term['exclude'] ) . ')';
						}
					}
				}
			}

			// Order by.
			switch ( $filter_sort_by ) {
				case 'count':
					$orderby = 'count DESC, term_name ASC';
					break;
				case 'term_name':
					$orderby = 'term_name ASC';
					break;
				case 'term_order':
					if ( ! empty( $condition_term['include'] ) ) {
						$order_term_ids = implode( ',', $condition_term['include'] );
						$orderby        = "FIELD(term_id, $order_term_ids)";
					} else {
						// Order by term order setting.
						$terms    = get_terms( $source, array( 'hide_empty' => false ) );
						$term_ids = array();
						if ( ! empty( $terms ) ) {
							foreach( $terms as $term ) {
								array_push( $term_ids, $term->term_id );
							}
						}
						unset($terms);
						$order_term_ids = implode( ',', $term_ids );
						$orderby        = "FIELD(term_id, $order_term_ids)";
					}
					break;
			}

			// Get all product ids by current term id, if is tax page.
			if ( empty( $product_taxonomy_id ) ) {
				if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! empty( $outstock_ids ) ) {
					$all_ids_in_term = 'AND product_id NOT IN (' . implode( ',', $outstock_ids ) . ')';
				}
			} else {
				$current_term_id   = $is_tax ? $is_tax : ( isset( get_queried_object()->term_id ) ? get_queried_object()->term_id : false );
				$current_term_name = isset( get_queried_object()->taxonomy ) ? get_queried_object()->taxonomy : 'product_cat';
				$all_ids_in_term   = $this->get_product_ids_from_term_ids( $current_term_id, $current_term_name );

				// Remove outstock product ids.
				if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! empty( $outstock_ids ) ) {
					$all_ids_in_term = array_diff( $all_ids_in_term, $outstock_ids );
				}

				if ( ! empty( $all_ids_in_term ) ) {
					$all_ids_in_term = 'AND product_id IN (' . implode( ',', $all_ids_in_term ) . ')';
				}
			}

			// On search result.
			if ( ! empty( $wp_query->query['s'] ) ) {
				if ( empty( $this->get_product_ids_on_search_result() ) ) {
					$empty = true;
				} else {
					$product_ids = $this->get_product_ids_on_search_result();
				}
			}

			if ( empty( $wp_query->posts ) || count( $wp_query->posts ) == 0 ) { //phpcs:ignore
				$empty = true;
			}
			// Where clause, not filter product ids if is first active filter.
			$first_active_filter = isset( $data['first_active_filter'] ) && $filter_id === $data['first_active_filter'];

			// Remove out of stock products ids.
			if ( empty( $product_ids ) ) {
				$where_product_ids = $all_ids_in_term;
			} else {
				if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! empty( $outstock_ids ) ) {
					$where_product_ids = 'AND product_id IN (' . implode( ',', array_diff( $product_ids, $outstock_ids ) ) . ')';
				} else {
					$where_product_ids = 'AND product_id IN (' . implode( ',', $product_ids ) . ')';
				}
			}

			// Counter.
			$item_count = $options['general_item_count'];

			// Filter content.
			ob_start();
			switch ( $type ) {
				case 'visual':
					if ( $empty ) {
						break;
					}

					$product_attr     = $native_source;
					$get_product_attr = wc_get_attribute( $product_attr );

					if ( empty( $get_product_attr ) || is_wp_error( $get_product_attr ) ) {
						break;
					}

					// Set first active filter.
					if ( $first_active_filter ) {
						$where_product_ids = $all_ids_in_term;
					}

					$swatch_type = $get_product_attr->type;
					$swatch_slug = $get_product_attr->slug;
					$sql         = "SELECT DISTINCT term_name, term_id, term FROM $table_name WHERE term='$swatch_slug' $where_product_ids $where_term_ids";
					$output      = $wpdb->get_results($sql, ARRAY_A); // phpcs:ignore

					if ( empty( $output ) ) {
						break;
					}

					// Load data form url.
					$get_url_params        = $get_url_params ? array_map( 'intval', explode( ',', $get_url_params ) ) : array();
					$swatch_selected_value = empty( $selected_value ) ? $get_url_params : $selected_value;

					foreach ( $output as $pat ) {
						$swatch_name     = $pat['term_name'];
						$swatch_id       = (int) $pat['term_id'];
						$swatch_meta     = get_term_meta( $swatch_id, $swatch_type, true );
						$selected_visual = in_array( $swatch_id, $swatch_selected_value, true ) ? ' selected' : '';
						$term            = get_term_by( 'id', $swatch_id, $swatch_slug );
						$slug            = $term->slug;
						$selected_visual = in_array( $slug, $swatch_selected_value, true ) ? ' selected' : '';
						switch ( $swatch_type ) {
							case 'color':
								?>
							<div class="w-filter-item w-filter-swatch-color<?php echo esc_attr( $selected_visual ); ?>" data-id="<?php echo esc_attr( $swatch_id ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>" style="background-color: <?php echo esc_attr( $swatch_meta ); ?>">
								<span class="w-tooltip"><?php echo esc_html( $swatch_name ); ?></span>
							</div>
								<?php
								break;
							case 'image':
								$image = $swatch_meta ? wp_get_attachment_image_src( $swatch_meta ) : '';
								$image = $image ? $image[0] : WC()->plugin_url() . '/assets/images/placeholder.png';
								?>
							<div class="w-filter-item w-filter-swatch-image<?php echo esc_attr( $selected_visual ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>" data-id="<?php echo esc_attr( $swatch_id ); ?>">
								<span class="w-tooltip"><?php echo esc_html( $swatch_name ); ?></span>
								<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $swatch_name ); ?>">
							</div>
								<?php
								break;
							case 'label':
								?>
							<div class="w-filter-item w-filter-swatch-label<?php echo esc_attr( $selected_visual ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>" data-id="<?php echo esc_attr( $swatch_id ); ?>">
								<span class="w-tooltip"><?php echo esc_html( $swatch_name ); ?></span>
								<span class="w-visual-label"><?php echo esc_html( $swatch_meta ); ?></span>
							</div>
								<?php
								break;
						}
					}
					break;
				case 'stock':
					if ( $empty ) {
						break;
					}
					// Set first active filter.
					if ( $first_active_filter ) {
						$where_product_ids = $all_ids_in_term;
					}

					// Load data form url.
					$get_url_params = $get_url_params ? explode( ',', $get_url_params ) : array();

					// Checked value.
					$checked_value = empty( $selected_value ) ? $get_url_params : $selected_value;

					$filter_name    = basename( get_permalink( $filter_id ) );
					$hierarchical   = get_post_meta( $filter_id, 'woostify_product_filter_term_hierarchical', true );
					$expand_default = get_post_meta( $filter_id, 'woostify_product_filter_term_hierarchical_expand', true );
					$source         = $hierarchical ? $filter_name : $source;
					$sql            = "SELECT term_name, term_id, term, parent_id, depth, COUNT(DISTINCT product_id) as count FROM $table_name WHERE term='$source' AND term <> '' AND term_id <> 0 $where_product_ids $where_term_ids GROUP BY term_id ORDER BY depth, $orderby";
					$output         = $wpdb->get_results($sql, ARRAY_A); // phpcs:ignore

					$table_stock_name = $product_filter->table_stock_name();
					$stock            = get_post_meta( $filter_id, 'woostify_product_filter_stock', true );
					$stock            = ! empty( $stock ) ? explode( '|', $stock ) : array();
					$stock_data       = array(
						'onsale'      => __( 'On Sale', 'woostify-pro' ),
						'instock'     => __( 'In Stock', 'woostify-pro' ),
						'onbackorder' => __( 'On Backorder', 'woostify-pro' ),
					);

					if ( 'yes' !== get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
						$stock_data['outofstock'] = __( 'Out of Stock', 'woostify-pro' );
					}

					foreach ( $stock as $value ) {
						$checkbox_html_id = uniqid( $filter_id . '-' . $value );
						$count            = $this->get_term_count( $filter_id, $value, 0 );
						if ( $count > 0 && isset( $stock_data[ $value ] ) ) {
							?>
							<div class="w-filter-item-wrap">
								<label class="w-filter-item" for="<?php echo esc_attr( $checkbox_html_id ); ?>" data-slug="<?php echo esc_attr( $value ); ?>">
									<input class="w-filter-item-input" id="<?php echo esc_attr( $checkbox_html_id ); ?>" <?php echo in_array( $value, $checked_value, true ) ? 'checked' : ''; ?> type="checkbox" name="w-filter-checkbox-<?php echo esc_attr( $source ); ?>">
									<span class="w-filter-item-name"><?php echo esc_html( $stock_data[ $value ] ); ?></span>
									<?php if ( $count ) { ?>
										<span class="w-filter-item-count">( <?php echo esc_html( $count ); ?> )</span>
									<?php } ?>
								</label>
							</div>
							<?php
						}
					}
					break;
				case 'checkbox':
					if ( $empty ) {
						break;
					}

					// Set first active filter.
					if ( $first_active_filter ) {
						$where_product_ids = $all_ids_in_term;
					}

					// Load data form url.
					$get_url_params = $get_url_params ? explode( ',', $get_url_params ) : array();

					// Checked value.
					$checked_value = empty( $selected_value ) ? $get_url_params : $selected_value;

					$filter_name    = basename( get_permalink( $filter_id ) );
					$hierarchical   = get_post_meta( $filter_id, 'woostify_product_filter_term_hierarchical', true );
					$expand_default = get_post_meta( $filter_id, 'woostify_product_filter_term_hierarchical_expand', true );
					$source         = $hierarchical ? $filter_name : $source;

					if ( empty( $where_product_ids ) ) {
						$where_product_ids = '';
					}

					$sql = "SELECT term_name, term_id, term, parent_id, depth, COUNT(DISTINCT product_id) as count FROM $table_name WHERE term='$source' AND term <> '' AND term_id <> 0 $where_product_ids $where_term_ids GROUP BY term_id ORDER BY depth, $orderby";

					$output = $wpdb->get_results($sql, ARRAY_A); // phpcs:ignore

					if ( empty( $output ) ) {
						break;
					}

					// Render tanoxnomy hierarchical.
					if ( $hierarchical ) {
						$this->render_hierarchical( $filter_id, $output, $checked_value, $expand_default, $limit );
						break;
					}

					// Quick search.
					echo wp_kses( $quick_search_output, $quick_search_allow );

					$key = 0;
					foreach ( $output as $key => $check ) {
						$checkbox_id      = intval( $check['term_id'] );
						$checkbox_html_id = uniqid( $filter_id . $checkbox_id );
						$checkbox_count   = $this->get_term_count( $filter_id, $checkbox_id, $check['count'] );
						if ( $item_count && ! $checkbox_count ) {
							continue;
						}

						$term = get_term_by( 'id', $check['term_id'], $check['term'] );
						$slug = $term->slug;

						// Set limit.
						if ( $limit && $key >= $limit ) {
							break;
						}

						// Set soft limit.
						if ( ! $quick_search && $need_soft_limit && $soft_limit && $key === $soft_limit ) {
							?>
						<div class="w-filter-item-overflow w-filter-hidden">
							<?php
						}
						if ( ! empty( $slug ) ) {
							$slug_attr = 'data-slug=' . esc_attr( $slug );
						}
						?>

						<div class="w-filter-item-wrap">
							<label class="w-filter-item" for="<?php echo esc_attr( $checkbox_html_id ); ?>" <?php echo esc_attr( $slug_attr ); ?> data-id="<?php echo esc_attr( $checkbox_id ); ?>">
								<input class="w-filter-item-input" id="<?php echo esc_attr( $checkbox_html_id ); ?>" <?php echo in_array( $slug, $checked_value, true ) ? 'checked' : ''; ?> type="checkbox" name="w-filter-checkbox-<?php echo esc_attr( $source ); ?>">
								<span class="w-filter-item-name"><?php echo esc_html( $check['term_name'] ); ?></span>

								<?php if ( $item_count ) { ?>
									<span class="w-filter-item-count">( <?php echo esc_html( $checkbox_count ); ?> )</span>
								<?php } ?>
							</label>
						</div>
						<?php
					}

					// Set soft limit.
					if ( ! $quick_search && $need_soft_limit && $soft_limit && $soft_limit <= $key ) {
						?>
						</div>
						<span class="w-filter-toggle-btn"><?php esc_html_e( 'See more', 'woostify-pro' ); ?></span>
						<span class="w-filter-toggle-btn w-filter-hidden"><?php esc_html_e( 'See less', 'woostify-pro' ); ?></span>
						<?php
					}
					break;
				case 'radio':
					if ( $empty ) {
						break;
					}

					// Set first active filter.
					if ( $first_active_filter ) {
						$where_product_ids = $all_ids_in_term;
					}

					// Load data from url.
					$radio_selected_value = empty( $selected_value ) ? $get_url_params : $selected_value;
					$sql                  = "SELECT DISTINCT term_name, term, term_id, COUNT(DISTINCT product_id) as count FROM $table_name WHERE term='$source' AND term <> '' AND term_id <> 0 $where_product_ids $where_term_ids GROUP BY term_id ORDER BY $orderby";
					$output               = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore

					if ( empty( $output ) ) {
						break;
					}

					// Quick search.
					echo wp_kses( $quick_search_output, $quick_search_allow );

					$key = 0;
					foreach ( $output as $key => $radio ) {
						$radio_id      = (int) $radio['term_id'];
						$radio_html_id = uniqid( $filter_id . $radio_id );
						$radio_count   = $this->get_term_count( $filter_id, $radio_id, $radio['count'] );
						if ( $item_count && ! $radio_count ) {
							continue;
						}

						// Set limit.
						if ( $limit && $key >= $limit ) {
							break;
						}

						$term = get_term_by( 'id', $radio['term_id'], $radio['term'] );
						$slug = $term->slug;
						if ( ! empty( $slug ) ) {
							$slug_attr = 'data-slug=' . esc_attr( $slug );
						}

						// Set soft limit.
						if ( ! $quick_search && $need_soft_limit && $soft_limit && $key === $soft_limit ) {
							?>
						<div class="w-filter-item-overflow w-filter-hidden">
							<?php
						}
						?>
						<div class="w-filter-item-wrap">
							<label class="w-filter-item" for="<?php echo esc_attr( $radio_html_id ); ?>" <?php echo esc_attr( $slug_attr ); ?> data-id="<?php echo esc_attr( $radio_id ); ?>">
								<input class="w-filter-item-input" id="<?php echo esc_attr( $radio_html_id ); ?>" <?php checked( $radio_selected_value, $slug ); ?> type="radio" name="w-filter-radio-<?php echo esc_attr( $source ); ?>">
								<span class="w-filter-item-name"><?php echo esc_html( $radio['term_name'] ); ?></span>

								<?php if ( $item_count ) { ?>
									<span class="w-filter-item-count">(<?php echo esc_html( $radio_count ); ?>)</span>
								<?php } ?>
							</label>
						</div>
						<?php
					}
					// Set soft limit.
					if ( ! $quick_search && $need_soft_limit && $soft_limit && $soft_limit <= $key ) {
						?>
						</div>
						<span class="w-filter-toggle-btn"><?php esc_html_e( 'See more', 'woostify-pro' ); ?></span>
						<span class="w-filter-toggle-btn w-filter-hidden"><?php esc_html_e( 'See less', 'woostify-pro' ); ?></span>
						<?php
					}
					break;
				case 'select':
					if ( $empty ) {
						break;
					}

					// Set first active filter.
					if ( $first_active_filter ) {
						$where_product_ids = $all_ids_in_term;
					}

					// Load data from url.
					$_selected_value = empty( $selected_value ) ? $get_url_params : $selected_value;

					$sql    = "SELECT DISTINCT term_name, term, term_id, COUNT(DISTINCT product_id) as count FROM $table_name WHERE term='$source' AND term <> '' AND term_id <> 0 $where_product_ids $where_term_ids GROUP BY term_id ORDER BY $orderby";
					$output = $wpdb->get_results($sql, ARRAY_A); // phpcs:ignore

					if ( empty( $output ) ) {
						break;
					}

					?>
					<select class="w-product-filter-select-field">
						<option value=""><?php esc_html_e( 'Select', 'woostify-pro' ); ?></option>
					<?php
					foreach ( $output as $key => $select ) {
						$select_count = $this->get_term_count( $filter_id, $select['term_id'], $select['count'] );
						if ( $item_count && ! $select_count ) {
							continue;
						}

						$term = get_term_by( 'id', $select['term_id'], $select['term'] );
						$slug = $term->slug;
						if ( ! empty( $slug ) ) {
							$slug_attr = 'data-slug=' . esc_attr( $slug );
						}
						// Set limit.
						if ( $limit && $key >= $limit ) {
							echo '</select>';
							break;
						}
						?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $_selected_value, (int) $slug, true ); ?>>
							<?php
							if ( $item_count ) {
								echo esc_html( sprintf( '%1$s (%2$s)', $select['term_name'], $select_count ) );
							} else {
								echo esc_html( $select['term_name'] );
							}
							?>
							</option>
							<?php
					}
					?>
					</select>
					<?php
					break;
				case 'range-slider':
					if ( $empty ) {
						break;
					}

					// Set first active filter.
					if ( $first_active_filter ) {
						$where_product_ids = $all_ids_in_term;
					}

					// Get min, max from database.
					$min_max = "SELECT min(price), max(price) FROM $table_name WHERE price>=0 $where_product_ids";
					$min_max = $wpdb->get_results( $min_max, ARRAY_A ); // phpcs:ignore

					$min = isset( $min_max[0]['min(price)'] ) ? $min_max[0]['min(price)'] : 0;
					$max = isset( $min_max[0]['max(price)'] ) ? $min_max[0]['max(price)'] : 0;
					if ( $min === $max ) {
						break;
					}

					// Load data from url.
					$slider_value   = false;
					$get_url_params = $get_url_params ? explode( ',', $get_url_params ) : array();
					if ( ! empty( $get_url_params ) ) {
						$slider_value = sprintf( '[%1$s,%2$s]', $get_url_params[0], $get_url_params[1] );
						if ( intval( $min ) === intval( $max ) ) {
							$min = $get_url_params[0];
							$max = $get_url_params[1];
						}
					} elseif ( ! empty( $selected_value ) ) {
						$slider_value = sprintf( '[%1$s,%2$s]', $selected_value[0], $selected_value[1] );
						if ( intval( $min ) === intval( $max ) ) {
							$min = $selected_value[0];
							$max = $selected_value[1];
						}
					}

					// Data.
					$range_start = empty( $slider_value ) ? "[$min, $max]" : $slider_value;
					$range_value = array(
						'min' => intval( $min ),
						'max' => intval( $max ),
					);

					$data_empty = $product_filter->detect_empty( $data );
					if ( isset( $data['range-slider'] ) && ! $data_empty ) {
						$range_value = $data['range-slider'];
					}
					?>
					<div class="w-filter-range-slider" data-start="<?php echo esc_attr( $range_start ); ?>" data-range="<?php echo esc_attr( wp_json_encode( $range_value ) ); ?>"></div>

					<?php if ( $slider_value ) { ?>
						<button class="w-filter-range-slider-reset w-filter-item-submit"><?php esc_html_e( 'Reset', 'woostify-pro' ); ?></button>
						<?php
					}
					break;
				case 'check-range':
					if ( $empty ) {
						break;
					}

					$rang_min = get_post_meta( $filter_id, 'woostify_product_filter_check_range_min', true );
					$rang_max = get_post_meta( $filter_id, 'woostify_product_filter_check_range_max', true );
					if ( empty( $rang_min ) || empty( $rang_max ) ) {
						break;
					}

					// Load data from url.
					$checked_range_url = array();
					if ( $get_url_params ) {
						$checked_range_url = array_map(
							function( $value ) {
								return implode( ',', $value );
							},
							array_chunk(
								explode( ',', $get_url_params ),
								2
							)
						);
					}

					// Set first active filter.
					if ( $first_active_filter ) {
						$where_product_ids = $all_ids_in_term;
					}

					$checked_range = empty( $selected_value ) ? $checked_range_url : $selected_value;
					$range_data    = array_combine( $rang_min, $rang_max );
					foreach ( $range_data as $k => $v ) {
						$sql    = "SELECT DISTINCT price FROM $table_name WHERE price >= '{$k}' AND price <= '{$v}' $where_product_ids";
						$output = $wpdb->get_results($sql, ARRAY_A); // phpcs:ignore
						if ( ! count( $output ) ) {
							continue;
						}

						$data_value            = sprintf( '[%s,%s]', $k, $v );
						$check_range_id        = uniqid( $filter_id . $k );
						$compare_checked_range = in_array( $data_value, $checked_range, true );
						?>
						<label class="w-filter-item" for="<?php echo esc_attr( $check_range_id ); ?>" data-value="<?php echo esc_attr( $data_value ); ?>">
							<input type="checkbox" id="<?php echo esc_attr( $check_range_id ); ?>" <?php checked( $compare_checked_range, true ); ?>>
							<span class="w-filter-check-range-inner">
								<span class="w-filter-check-range-value"><?php echo wp_kses( wc_price( $k ), array() ); ?></span>
								<span class="w-filter-separator">-</span>
								<span class="w-filter-check-range-value"><?php echo wp_kses( wc_price( $v ), array() ); ?></span>
							</span>
						</label>
						<?php
					}
					break;
				case 'date-range':
					$date_range_from   = $product_filter->set_value( $filter_id, 'woostify_product_filter_date_range_from', __( 'From', 'woostify-pro' ) );
					$date_range_to     = $product_filter->set_value( $filter_id, 'woostify_product_filter_date_range_to', __( 'To', 'woostify-pro' ) );
					$date_range_search = $product_filter->set_value( $filter_id, 'woostify_product_filter_date_range_search', __( 'Search', 'woostify-pro' ) );

					// Load data from url.
					$get_url_params = $get_url_params ? explode( ',', $get_url_params ) : array();
					$picked_date    = empty( $selected_value ) ? $get_url_params : $selected_value;

					// Check if selected value/url params is date time value.
					if (
						! isset( $picked_date[0] ) ||
						! isset( $picked_date[1] ) ||
						! strtotime( $picked_date[0] ) ||
						! strtotime( $picked_date[1] )
					) {
						$picked_date = false;
					}
					?>
					<input class="w-filter-date-picker" data-from type="text" placeholder="<?php echo esc_attr( $date_range_from ); ?>" readonly value="<?php echo esc_attr( isset( $picked_date[0] ) ? $picked_date[0] : '' ); ?>">
					<input class="w-filter-date-picker" data-to type="text" placeholder="<?php echo esc_attr( $date_range_to ); ?>" readonly value="<?php echo esc_attr( isset( $picked_date[1] ) ? $picked_date[1] : '' ); ?>">
					<button class="w-filter-item-submit" type="button"><?php echo esc_html( $date_range_search ); ?></button>
					<?php
					break;
				case 'rating':
					if ( $empty ) {
						break;
					}

					// Set first active filter.
					if ( $first_active_filter ) {
						$where_product_ids = $all_ids_in_term;
					}

					$sql    = "SELECT DISTINCT product_id, rating FROM $table_name WHERE rating > 0 $where_product_ids";
					$output = $wpdb->get_results($sql, ARRAY_A); // phpcs:ignore
					if ( empty( $output ) ) {
						break;
					}

					// Load data from url.
					$rated_star = $get_url_params ? (int) $get_url_params : ( $selected_value ? (int) $selected_value : false );

					$rating_label    = $product_filter->set_value( $filter_id, 'woostify_product_filter_rating_label', __( 'And Up', 'woostify-pro' ) );
					$rating_selected = $product_filter->set_value( $filter_id, 'woostify_product_filter_rating_selected', __( '@N & Up', 'woostify-pro' ) );
					$five_star       = '<div class="w-filter-rating-item' . esc_attr( 5 === $rated_star ? ' selected' : '' ) . '"><span class="w-filter-rating-star"></span></div>';

					$rating      = intval( min( wp_list_pluck( $output, 'rating', null ) ) );
					$rating_star = '';
					$rating_av   = 5 - $rating;

					for ( $i = 1; $i <= $rating_av; $i++ ) {
						$rating_star .= '<div class="w-filter-rating-item' . esc_attr( $rated_star && ( 5 - $rated_star ) === $i ? ' selected' : '' ) . '"><span class="w-filter-rating-star"><span class="w-filter-rating-star-inner"></span></span><span class="w-filter-rating-label">' . $rating_label . '</span></div>';
					}

					if ( $rating_star || 5 === $rating ) {
						echo wp_kses_post( $five_star . $rating_star );
					}
					break;
				case 'search':
					$placeholder    = get_post_meta( $filter_id, 'woostify_product_filter_search_placeholder', true );
					$searched_value = $get_url_params ? $get_url_params : ( $selected_value ? $selected_value : '' );
					?>
					<div class="search-wrap">
						<input type="text" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="w-product-filter-text-field" value="<?php echo esc_attr( $searched_value ); ?>">
						<span class="w-product-filter-search-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
							<path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
						</svg>
					</span>
					</div>
					<?php
					break;
				case 'sort-order':
					$sort_order_opt = $product_filter->set_value( $filter_id, 'woostify_product_filter_sort_order', 'price-desc|price|date|rating|popularity|menu_order' );
					$sort_order_opt = explode( '|', $sort_order_opt );

					$sort_order_data = array(
						'menu_order' => __( 'Default sorting', 'woostify-pro' ),
						'popularity' => __( 'Sort by popularity', 'woostify-pro' ),
						'rating'     => __( 'Sort by average rating', 'woostify-pro' ),
						'date'       => __( 'Sort by latest', 'woostify-pro' ),
						'price'      => __( 'Sort by price: low to high', 'woostify-pro' ),
						'price-desc' => __( 'Sort by price: high to low', 'woostify-pro' ),
					);
					$sort_order_data = apply_filters( 'woostify_sort_order_field', $sort_order_data );

					// Load data from url.
					$sorted_value = $get_url_params ? $get_url_params : ( $selected_value ? $selected_value : '' );
					if ( isset( $get_params['orderby'] ) ) {
						$sorted_value = esc_html( $get_params['orderby'] );
					}

					if ( ! empty( $sort_order_opt ) && ! empty( $sort_order_data ) ) {
						?>
						<select class="w-product-filter-select-field w-filter-ordering">
						<?php
						foreach ( $sort_order_opt as $v ) {
							if ( ! isset( $sort_order_data[ $v ] ) ) {
								continue;
							}
							?>
								<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $sorted_value, $v ); ?>><?php echo esc_html( $sort_order_data[ $v ] ); ?></option>
							<?php } ?>
						</select>
							<?php
					}
					break;
			}
			$filter_content = ob_get_clean();

			$filter_icon = apply_filters( 'woostify_smart_filter_arrow_icon', 'angle-down' );

			if ( ! empty( $filter_content ) ) {
				?>
				<div class="w-product-filter <?php echo isset( $options['collapse_enabled'] ) && '1' === $options['collapse_enabled'] ? '' : 'no-collapse'; ?>" data-type="<?php echo esc_attr( $type ); ?>" data-id="<?php echo esc_attr( $filter_id ); ?>">
					<?php
					// Filter title.
					if ( ! empty( $label ) ) {
						?>
							<h6 class="widget-title">
							<?php
							echo esc_html( $label ) . woostify_fetch_svg_icon( $filter_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
							</h6>
							<?php
					}

					// Filter content.
					?>
						<div class="w-product-filter-inner">
							<div class="w-product-filter-content-wrap">
								<?php echo $filter_content; // phpcs:ignore ?>
							</div>
						</div>
				</div>
				<?php
			}
		}

		/**
		 * Render all filter
		 *
		 * @param array $attrs The shortcode attrs.
		 */
		public function render_all_filter( $attrs = array() ) {
			if ( ! $this->isset_table() ) {
				return;
			}

			$args = array(
				'post_type'      => 'product_filter',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'order'          => 'ASC',
				'orderby'        => 'menu_order',
			);

			$query = get_posts( $args );
			if ( empty( $query ) ) {
				$no_posts = '<span class="woocommerce-info">' . esc_html__( 'No thing found!', 'woostify-pro' ) . '</span>';

				echo wp_kses_post( $no_posts );
				return;
			}
			?>

			<div class="w-product-filter-all">
				<?php
				foreach ( $query as $id ) {
					$this->render_filter( $id );
				}
				?>
			</div>
			<?php
		}
	}

	Woostify_Filter_Render::init();
}
