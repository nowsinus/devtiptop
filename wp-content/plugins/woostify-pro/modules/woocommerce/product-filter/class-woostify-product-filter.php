<?php
/**
 * Woostify template builder for woocommerce
 *
 * @package Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_Product_Filter' ) ) {
	/**
	 * Class for woostify Header Footer builder.
	 */
	class Woostify_Product_Filter {
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
			$this->define_constants();

			add_action( 'init', array( $this, 'init_action' ) );
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'add_meta_boxes_product_filter', array( $this, 'add_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 2 );
			add_action( 'comment_post', array( $this, 'update_rating' ), 10, 3 );
			add_action( 'save_post', array( $this, 'save_post' ) );
			add_action( 'delete_post', array( $this, 'delete_post' ) );
			add_action( 'edited_term', array( $this, 'edit_term' ), 10, 3 );
			add_action( 'delete_term', array( $this, 'delete_term' ), 10, 3 );

			// Sortable filter.
			add_filter( 'posts_orderby', array( $this, 'posts_orderby' ), 99, 2 );
			add_action( 'wp_ajax_woostify_filter_list_sortable', array( $this, 'sortable' ) );

			// Pre get posts.
			add_action( 'pre_get_posts', array( $this, 'filter_pre_get_posts' ) );

			// Add filter remove key.
			add_filter( 'woostify_site_main_class', array( $this, 'add_filter_wrap_class' ) );
			add_action( 'woocommerce_before_shop_loop', array( $this, 'add_filter_key_remove' ), 40 );

			// Script.
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			// Product filter.
			add_action( 'wp_ajax_woostify_product_filter', array( $this, 'woostify_product_filter' ) );
			add_action( 'wp_ajax_nopriv_woostify_product_filter', array( $this, 'woostify_product_filter' ) );

			// Save admin settings.
			$woocommerce_helper = Woostify_Woocommerce_Helper::init();
			add_action( 'wp_ajax_woostify_save_smart_product_filter_options', array( $woocommerce_helper, 'save_options' ) );

			// Add Template Type column on 'woo_builder' list in admin screen.
			add_filter( 'manage_product_filter_posts_columns', array( $this, 'add_column_head' ), 10 );
			add_action( 'manage_product_filter_posts_custom_column', array( $this, 'add_column_content' ), 10, 2 );

			// Shortcode.
			add_shortcode( 'woostify_product_filter', array( $this, 'woostify_product_filter_shortcode' ) );
			// Load horizontal filter.
			add_action( 'woocommerce_before_shop_loop', array( $this, 'load_horizontal_shortcode' ), 12 );

			// Index database.
			add_action( 'wp_ajax_woostify_index_filter', array( $this, 'woostify_index_filter' ) );

			add_filter( 'body_class', array( $this, 'body_classes' ) );

			// Add shop sidebar.
			add_action( 'woostify_sidebar', array( $this, 'get_sidebar' ), 10 );
			
			add_action( 'in_admin_header', array( $this, 'woostify_hide_all_noticee_page_setting' ) );
		}

		/**
		 * Add admin setting hide all notice
		 */
		public function woostify_hide_all_noticee_page_setting() {
			$screen = get_current_screen();
			if ( $screen->id == 'woostify_page_smart-product-filter-settings' ) {
				remove_all_actions( 'user_admin_notices' );
				remove_all_actions( 'admin_notices' );
			}				
		}

		/**
		 * Posts orderby
		 *
		 * @param  string $order_by The ORDER BY clause of the query.
		 * @param  object $object The WP_Query instance (passed by reference).
		 */
		public function posts_orderby( $order_by, $object ) {
			if ( ! isset( $object->query['post_type'] ) || 'product_filter' !== $object->query['post_type'] ) {
				return $order_by;
			}

			// Disable this filter for future queries!
			remove_filter( current_filter(), __FUNCTION__ );

			global $wpdb;
			$posts_table = $wpdb->posts;

			return "$posts_table.menu_order, $posts_table.post_date DESC";
		}

		/**
		 * Sortable filter
		 */
		public function sortable() {
			check_ajax_referer( 'woostify_smart_filter_nonce', 'ajax_nonce' );

			global $wpdb;
			$ids = isset( $_POST['post_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['post_ids'] ) ) : false;
			if ( empty( $ids ) ) {
				wp_send_json_error();
			}

			$sql     = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product_filter' AND ID IN ($ids)";
			$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore
			if ( empty( $results ) ) {
				wp_send_json_success();
			}

			$results = wp_list_pluck( $results, 'ID' );
			$ids     = explode( ',', $ids );
			foreach ( $ids as $k => $id ) {
				if ( ! in_array( $id, $results, true ) ) {
					continue;
				}
				$wpdb->update( $wpdb->posts, array( 'menu_order' => $k ), array( 'ID' => $id ) ); // phpcs:ignore
				clean_post_cache( $id );
			}

			wp_send_json_success( $res );
		}

		/**
		 * Get an array of term information, including depth
		 *
		 * @param string $taxonomy The taxonomy name.
		 */
		public function get_term_depths( $taxonomy ) {
			$output  = array();
			$parents = array();

			$terms = get_terms( $taxonomy, array( 'hide_empty' => false ) );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				return $output;
			}

			// Get term parents.
			foreach ( $terms as $term ) {
				$parents[ $term->term_id ] = $term->parent;
			}

			// Build the term array.
			foreach ( $terms as $term ) {
				$output[ $term->term_id ] = array(
					'term_id'   => $term->term_id,
					'term_name' => $term->name,
					'term'      => $term->taxonomy,
					'parent_id' => $term->parent,
					'depth'     => 0,
				);

				$current_parent = intval( $term->parent );
				while ( 0 < $current_parent ) {
					$current_parent = $parents[ $current_parent ];
					$output[ $term->term_id ]['depth']++;

					// Prevent an infinite loop.
					if ( 10 < $output[ $term->term_id ]['depth'] ) {
						break;
					}
				}
			}

			return $output;
		}

		/**
		 * Table name
		 */
		public function table_name() {
			global $table_prefix;

			return "{$table_prefix}woostify_filter_index";
		}

		/**
		 * Insert database
		 *
		 * @param array $args The args.
		 */
		public function insert( $args = array() ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			global $wpdb;
			$table_name = $this->table_name();

			// Create table if not exists.
			$int       = 'BIGINT';
			$create_db = "CREATE TABLE IF NOT EXISTS $table_name (
				id BIGINT unsigned not null auto_increment,
				product_id INT unsigned,
				title VARCHAR(100),
				price DECIMAL(20),
				sku VARCHAR(50),
				rating VARCHAR(50),
				term VARCHAR(100),
				term_name VARCHAR(100),
				term_id $int unsigned default '0',
				parent_id $int unsigned default '0',
				depth $int unsigned default '0',
				variation_id $int unsigned default '0',
				PRIMARY KEY (id)
			) DEFAULT CHARSET=utf8";
			dbDelta( $create_db );

			// Default data.
			$default = array(
				'product_id'   => 0,
				'title'        => '',
				'price'        => 0,
				'sku'          => '',
				'rating'       => '',
				'term'         => '',
				'term_name'    => '',
				'term_id'      => 0,
				'parent_id'    => 0,
				'depth'        => 0,
				'variation_id' => 0,
			);

			$params = wp_parse_args( $args, $default );

			// @codingStandardsIgnoreStart
			// Insert to DB.
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO $table_name (product_id, title, price, sku, rating, term, term_name, term_id, parent_id, depth, variation_id) VALUES (%d, %s, %s, %s, %s, %s, %s, %d, %d, %d, %d )",
					$params['product_id'],
					$params['title'],
					$params['price'],
					$params['sku'],
					$params['rating'],
					$params['term'],
					$params['term_name'],
					$params['term_id'],
					$params['parent_id'],
					$params['depth'],
					$params['variation_id']
				)
			);
			// @codingStandardsIgnoreEnd
		}

		/**
		 * Update review rating when user submit review form
		 *
		 * @param int        $comment_id       The comment ID.
		 * @param int|string $comment_approved 1 if the comment is approved, 0 if not, 'spam' if spam.
		 * @param array      $commentdata      Comment data.
		 */
		public function update_rating( $comment_id, $comment_approved, $commentdata ) {
			$post_id = isset( $commentdata['comment_post_ID'] ) ? $commentdata['comment_post_ID'] : false;
			if ( 'product' !== get_post_type( $post_id ) ) {
				return;
			}

			$this->index( $post_id );
		}

		/**
		 * Update the index when posts get saved
		 *
		 * @param int $post_id The post ID.
		 */
		public function save_post( $post_id ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( false !== wp_is_post_revision( $post_id ) ) {
				return;
			}

			if ( 'auto-draft' === get_post_status( $post_id ) ) {
				return;
			}

			$post_type = get_post_type( $post_id );
			if ( ! in_array( $post_type, array( 'product', 'product_filter' ), true ) ) {
				return;
			}

			$this->index( $post_id );
		}

		/**
		 * Update the index when posts get deleted
		 *
		 * @param int $post_id The post ID.
		 */
		public function delete_post( $post_id ) {
			global $wpdb;
			$table_name = $this->table_name();

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE product_id = %d", $post_id ) ); // phpcs:ignore
		}

		/**
		 * Update the index when terms get saved
		 *
		 * @param  int    $term_id  The term id.
		 * @param  int    $tt_id    The taxonomy id.
		 * @param  string $taxonomy The taxonomy.
		 */
		public function edit_term( $term_id, $tt_id, $taxonomy ) {
			global $wpdb;

			$table_name = $this->table_name();
			$term       = get_term( $term_id, $taxonomy );

			$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET term_name = %s, parent_id = %d WHERE term_id = %d", $term->name, $term->parent, $term_id ) ); // phpcs:ignore
		}

		/**
		 * Update the index when terms get deleted
		 *
		 * @param  int    $term_id  The term id.
		 * @param  int    $tt_id    The taxonomy id.
		 * @param  string $taxonomy The taxonomy.
		 */
		public function delete_term( $term_id, $tt_id, $taxonomy ) {
			global $wpdb;
			$table_name = $this->table_name();

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE term_id = %d", $term_id ) ); // phpcs:ignore
		}

		/**
		 * Get url of filters
		 */
		public function get_filters_url() {
			global $wpdb;

			$sql  = "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'product_filter' AND post_status = 'publish'";
			$data = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore

			if ( empty( $data ) ) {
				return array();
			}

			$output = array();
			foreach ( wp_list_pluck( $data, 'ID' ) as $pid ) {
				$slug = str_replace( '-', '_', basename( get_permalink( $pid ) ) );

				$output[ $pid ] = "_$slug";
			}

			return $output;
		}

		/**
		 * Detect if has filter param on url
		 */
		public function detect_url_params() {
			$get_params = $_GET; // phpcs:ignore
			if ( empty( $get_params ) ) {
				return false;
			}

			$return      = false;
			$filters_url = array_flip( $this->get_filters_url() );
			foreach ( $get_params as $key => $value ) {
				if ( ! isset( $filters_url[ $key ] ) ) {
					continue;
				}

				$return = true;
				break;
			}

			return $return;
		}

		/**
		 * Get active params
		 */
		public function get_active_params() {
			if ( ! $this->detect_url_params() ) {
				return array();
			}

			$get_params  = $_GET; // phpcs:ignore
			$filters_url = array_flip( $this->get_filters_url() );
			$output      = array();

			foreach ( $get_params as $key => $value ) {
				$filter_id = isset( $filters_url[ $key ] ) ? $filters_url[ $key ] : false;
				if ( ! $filter_id ) {
					continue;
				}

				$data = htmlspecialchars( $value, ENT_QUOTES );
				$type = get_post_meta( $filter_id, 'woostify_product_filter_type', true );

				if ( in_array( $type, array( 'checkbox', 'visual', 'stock' ), true ) ) {
					$data = explode( ',', $data );
				} elseif ( 'check-range' === $type ) {
					$data = array_map(
						function( $value ) {
							return implode( ',', $value );
						},
						array_chunk(
							explode( ',', $data ),
							2
						)
					);
				}

				$output[ $filter_id ] = $data;
			}

			return $output;
		}

		/**
		 * Get active data, $args or $remove_key
		 *
		 * @param  string $type The type of data, $type = 'key' or 'args'.
		 * @return string with $type = 'key' or array with $type = 'args'.
		 */
		public function get_active_data( $type = 'key' ) {
			if ( ! $this->detect_url_params() ) {
				return 'key' === $type ? '' : array();
			}

			$active = $this->get_active_params();
			$parse  = $this->filter_parse_args( $active );
			$output = $parse[ $type ];

			if ( empty( $output ) ) {
				return 'key' === $type ? '' : array();
			}

			if ( 'key' === $type ) {
				$remove_icon = woostify_fetch_svg_icon( 'close' );
				$output      = '<span class="w-filter-key-remove" data-type="all">' . __( 'Clear', 'woostify-pro' ) . ' <span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>' . $output;
			}

			return $output;
		}

		/**
		 * Get all product taxonomy
		 */
		public function get_product_taxonomies() {
			$tax = get_object_taxonomies( 'product' );

			return array_diff( $tax, array( 'product_type', 'product_visibility', 'product_shipping_class' ) );
		}

		/**
		 * Get out of stock product ids
		 */
		public function get_outstock_product_ids() {
			$args = array(
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post_type'      => 'product',
				'orderby'        => 'meta_value_num',
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore
					array(
						'key'     => '_stock_status',
						'value'   => 'outofstock',
						'compare' => '==',
					),
				),
			);

			return get_posts( $args );
		}

		/**
		 * Get all product ids by term name
		 *
		 * @param string     $term_name The term name.
		 * @param string|int $term_id   The term id.
		 */
		public function get_product_ids_by_term( $term_name = '', $term_id = false ) {
			global $wpdb;
			$prefix = $wpdb->prefix;

			$filter_by_term_name = $term_name ? "AND tt.taxonomy LIKE '{$term_name}'" : '';
			$filter_by_term_id   = $term_id ? "AND tt.term_id = $term_id" : '';

			$sql = "
				SELECT DISTINCT p.ID
				FROM {$prefix}posts as p
				INNER JOIN {$prefix}term_relationships as tr ON p.ID = tr.object_id
				INNER JOIN {$prefix}term_taxonomy as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN {$prefix}terms as t ON tt.term_id = t.term_id
				WHERE p.post_type LIKE 'product'
				AND p.post_status LIKE 'publish'
				$filter_by_term_name
				$filter_by_term_id
				
			";

			$output = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore

			if ( empty( $output ) ) {
				return array();
			}

			// Exclude out of stock product.
			if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
				// Convert product id to int value.
				$product_ids = array_map( 'intval', wp_list_pluck( $output, 'ID' ) );

				return array_diff( $product_ids, $this->get_outstock_product_ids() );
			}

			return array_map( 'intval', wp_list_pluck( $output, 'ID' ) );
		}

		/**
		 * Render $args
		 *
		 * @param  array   $data    The data.
		 * @param  boolean $boolean Use this for pre_get_posts action.
		 */
		public function filter_parse_args( $data, $boolean = false ) {

			if ( empty( $data ) ) {
				return false;
			}
			$hide_outstock = get_option( 'woocommerce_hide_out_of_stock_items' );
			$attr_args     = array();
			$args          = array();

			$filter_key        = '';
			$remove_icon       = woostify_fetch_svg_icon( 'close' );
			$attributes        = array();
			$filter_outofstock = false;

			foreach ( $data as $k => $v ) {
				// Sanitize data from url params.
				if ( $boolean ) {
					$v = htmlspecialchars( $v, ENT_QUOTES );
				}

				if ( empty( $v ) ) {
					continue;
				}

				$type   = get_post_meta( $k, 'woostify_product_filter_type', true );
				$source = get_post_meta( $k, 'woostify_product_filter_data', true );
				$label  = get_post_meta( $k, 'woostify_product_filter_label', true );

				switch ( $type ) {
					case 'search':
						$produt_search_sku = $this->search_by_text( $v );
						if ( ! empty( $produt_search_sku ) ) {
							$args['post__in'] = $produt_search_sku;
						} else {
							$args['s'] = $v;
						}

						$filter_key .= '<span class="w-filter-key-remove" data-id="' . $k . '" data-type="search">' . sprintf( /* translators: keyword */__( '%1$s: %2$s', 'woostify-pro' ), $label, $v ) . '<span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>';
						break;
					case 'date-range':
						if ( $boolean ) {
							$v = explode( ',', $v );
						}

						if ( empty( $v[0] ) || empty( $v[1] ) ) {
							break;
						}

						$args['date_query'][] = array(
							'after'  => $v[0],
							'before' => $v[1],
						);

						$filter_key .= '<span class="w-filter-key-remove" data-id="' . $k . '" data-type="date-range">' . sprintf( /* translators: date range */__( '%1$s: %2$s - %3$s', 'woostify-pro' ), $label, $v[0], $v[1] ) . '<span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>';
						break;
					case 'check-range':
						$source = get_post_meta( $k, 'woostify_product_filter_check_range_query', true );

						$check_range_query = array(
							'relation' => 'OR',
						);

						if ( $boolean ) {
							$v = array_map(
								function( $value ) {
									return implode( ',', $value );
								},
								array_chunk(
									explode( ',', $v ),
									2
								)
							);
						}

						if ( is_string( $v ) ) {
							break;
						}

						foreach ( $v as $vv ) {
							$value_arr = json_decode( $vv, true );

							array_push(
								$check_range_query,
								array(
									'key'     => $source,
									'value'   => $value_arr,
									'compare' => 'BETWEEN',
									'type'    => 'NUMERIC',
								)
							);

							$value_from = wp_kses( wc_price( $value_arr[0] ), array() );
							$value_to   = wp_kses( wc_price( $value_arr[1] ), array() );

							$filter_key .= '<span class="w-filter-key-remove" data-id="' . $k . '" data-type="check-range" data-value="' . $vv . '">' . sprintf( /* translators: check range */__( '%1$s: %2$s - %3$s', 'woostify-pro' ), $label, $value_from, $value_to ) . '<span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>';
						}

						$args['meta_query'][][] = $check_range_query;
						break;
					case 'range-slider':
						$source = get_post_meta( $k, 'woostify_product_filter_range_slider_query', true );

						$args['meta_query'][] = array(
							'key'     => $source,
							'value'   => $v,
							'compare' => 'BETWEEN',
							'type'    => 'NUMERIC',
						);

						if ( is_string( $v ) ) {
							$v = explode( ',', $v );
						}

						if ( isset( $v[0] ) && isset( $v[1] ) ) {
							$slider_from = wp_kses( wc_price( $v[0] ), array() );
							$slider_to   = wp_kses( wc_price( $v[1] ), array() );

							$filter_key .= '<span class="w-filter-key-remove" data-id="' . $k . '" data-type="range-slider">' . sprintf( /* translators: range slider */__( '%1$s: %2$s - %3$s', 'woostify-pro' ), $label, $slider_from, $slider_to ) . '<span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>';
						}
						break;
					case 'rating':
						$args['meta_query'][] = array(
							'key'     => '_wc_average_rating',
							'value'   => $v,
							'compare' => '>=',
							'type'    => 'NUMERIC',
						);

						$rating_select = get_post_meta( $k, 'woostify_product_filter_rating_selected', true );
						$rating_text   = $v < 5 ? str_replace( '@N', $v, $rating_select ) : $v;
						$filter_key   .= '<span class="w-filter-key-remove" data-id="' . $k . '" data-type="rating">' . sprintf( /* translators: rating */__( '%1$s: %2$s', 'woostify-pro' ), $label, $rating_text ) . '<span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>';
						break;
					case 'sort-order':
						$args['orderby'] = 'meta_value_num';
						$args['order']   = 'DESC';

						switch ( $v ) {
							case 'rating':
								$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore
								break;
							case 'popularity':
								$args['meta_key'] = 'total_sales'; // phpcs:ignore
								break;
							case 'date':
								$args['orderby'] = 'date';
								break;
							case 'price':
								$args['meta_key'] = '_price'; // phpcs:ignore
								$args['order']    = 'ASC';
								break;
							case 'price-desc':
								$args['meta_key'] = '_price'; // phpcs:ignore
								break;
						}

						$filter_key .= '<span class="w-filter-key-remove" data-id="' . $k . '" data-type="sort-order">' . sprintf( /* translators: rating */__( '%1$s: %2$s', 'woostify-pro' ), $label, $v ) . '<span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>';
						break;
					case 'stock':
						$taxonomy_name = $source;

						if ( is_string( $v ) ) {
							$v = explode( ',', $v );
						}

						if ( empty( $v ) ) {
							break;
						}
						$args['meta_query']['relation'] = 'OR';
						if ( in_array( 'onsale', $v, true ) ) {
							$args['meta_query'][] = array(
								'key'     => '_sale_price',
								'value'   => 0,
								'compare' => '>',
								'type'    => 'numeric',
							);

							$args['meta_query'][] = array(
								'key'     => '_min_variation_sale_price',
								'value'   => 0,
								'compare' => '>',
								'type'    => 'numeric',
							);
						}

						if ( in_array( 'instock', $v, true ) ) {
							$filter_outofstock    = true;
							$args['meta_query'][] = array(
								'key'   => '_stock_status',
								'value' => 'instock',
							);
						}

						if ( in_array( 'outofstock', $v, true ) ) {
							$filter_outofstock    = false;
							$args['meta_query'][] = array(
								'key'   => '_stock_status',
								'value' => 'outofstock',
							);
						}

						if ( in_array( 'onbackorder', $v, true ) ) {
							$args['meta_query'][] = array(
								'key'   => '_stock_status',
								'value' => 'onbackorder',
							);
						}

						if ( is_array( $v ) ) {
							foreach ( $v as $vv ) {
								if ( 'instock' === $vv ) {
									$filter_key .= '<span class="w-filter-key-remove" data-id="' . $k . '" data-type="' . $type . '" data-value="instock">' . sprintf( /* translators: taxonomy name, value */__( '%1$s: %2$s', 'woostify-pro' ), $label, __( 'In Stock', 'woostify-pro' ) ) . '<span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>';
								} elseif ( 'onbackorder' === $vv ) {
									$filter_key .= '<span class="w-filter-key-remove" data-id="' . $k . '" data-type="' . $type . '" data-value="onbackorder">' . sprintf( /* translators: taxonomy name, value */__( '%1$s: %2$s', 'woostify-pro' ), $label, __( 'On Backorder', 'woostify-pro' ) ) . '<span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>';
								} elseif ( 'outofstock' === $vv ) {
									$filter_key .= '<span class="w-filter-key-remove" data-id="' . $k . '" data-type="' . $type . '" data-value="outofstock">' . sprintf( /* translators: taxonomy name, value */__( '%1$s: %2$s', 'woostify-pro' ), $label, __( 'Out of Stock', 'woostify-pro' ) ) . '<span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>';
								} else {
									$filter_key .= '<span class="w-filter-key-remove" data-id="' . $k . '" data-type="' . $type . '" data-value="onsale">' . sprintf( /* translators: taxonomy name, value */__( '%1$s: %2$s', 'woostify-pro' ), $label, __( 'On Sale', 'woostify-pro' ) ) . '<span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>';
								}
							}
						}

						break;
					case 'checkbox':
					case 'radio':
					case 'select':
					case 'visual':
						$taxonomy_name = $source;

						// Product attribute.
						if ( is_numeric( $source ) ) {
							$product_attrs = wc_get_attribute( $source );

							if ( ! empty( $product_attrs ) && ! is_wp_error( $product_attrs ) ) {
								$taxonomy_name = $product_attrs->slug;
							}
							if ( is_string( $v ) ) {
								$v = explode( ',', $v );
							}
							$term = get_terms(
								array(
									'taxonomy' => $taxonomy_name,
									'slug'     => $v,
								)
							);

							if ( ! empty( $term ) ) {
								$v = array_column( (array) $term, 'term_id' ); // phpcs:ignore
							}

							$attr_args['tax_query'][] = array(
								'taxonomy'         => $taxonomy_name,
								'terms'            => $v,
								'include_children' => true,
							);
						}

						if ( is_string( $v ) ) {
							$v = explode( ',', $v );
						}

						$term = get_terms(
							array(
								'taxonomy' => $taxonomy_name,
								'slug'     => $v,
							)
						);

						if ( ! empty( $term ) ) {
							$v = array_column( (array) $term, 'term_id' ); // phpcs:ignore
						}

						$args['tax_query'][] = array(
							'taxonomy'         => $taxonomy_name,
							'terms'            => $v,
							'include_children' => true,
						);

						if ( is_array( $v ) ) {
							foreach ( $v as $vv ) {
								$get_terms = get_term_by( 'id', $vv, $taxonomy_name );
								if ( empty( $get_terms ) || is_wp_error( $get_terms ) ) {
									continue;
								}
								$slug = $get_terms->slug;
								array_push( $attributes, $slug );

								$filter_key .= '<span class="w-filter-key-remove" data-id="' . $k . '" data-type="' . $type . '" data-value="' . $slug . '">' . sprintf( /* translators: taxonomy name, value */__( '%1$s: %2$s', 'woostify-pro' ), $label, $get_terms->name ) . '<span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>';
							}
						} else {
							$get_term = get_term_by( 'id', $v, $taxonomy_name );
							if ( empty( $get_term ) || is_wp_error( $get_term ) ) {
								break;
							}

							$slug = $get_term->slug;

							$filter_key .= '<span class="w-filter-key-remove" data-id="' . $k . '" data-type="' . $type . '" data-value="' . $slug . '">' . sprintf( /* translators: taxonomy name, value*/__( '%1$s: %2$s', 'woostify-pro' ), $label, $get_term->name ) . '<span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>';
						}
						break;
				}
			}

			if ( ! empty( $attributes ) && $filter_outofstock ) {
				$product_outofstock = $this->get_child_product_outofstock();

				if ( $product_outofstock ) {
					$args['post__not_in'] = $product_outofstock;
				}
			}

			$output = array(
				'args'      => $args,
				'key'       => $filter_key,
				'attr_args' => $attr_args,
			);

			return $output;
		}

		/**
		 * Detect if product has variation id out of stock
		 */
		public function get_child_product_outofstock() {
			global $wpdb;
			$sql  = "SELECT DISTINCT p.post_parent FROM {$wpdb->prefix}posts as p LEFT JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id WHERE p.post_status = 'publish' AND p.post_type = 'product_variation' AND pm.meta_key = '_stock_status' AND pm.meta_value = 'outofstock'";
			$data = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore

			$data = array_column( $data, 'post_parent' );

			return $data;
		}

		/**
		 * Detect if product has variation id out of stock
		 *
		 * @param  string $taxonomy The taxonomy of product attribute.
		 * @param  array  $ids      Array of product id.
		 * @param  int    $term_id  The term id.
		 */
		public function get_final_product_id_outstock( $taxonomy = '', $ids = array(), $term_id = false ) {
			if ( empty( $ids ) ) {
				return array();
			}

			$include = array();
			$exclude = array();
			foreach ( $ids as $pid ) {
				$product  = wc_get_product( $pid );
				$children = $product->get_children();

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

					if ( $term->term_id === $term_id ) {
						$stock = get_post_meta( $cid, '_stock_status', true );
						if ( 'outofstock' === $stock && ! isset( $exclude[ $pid ] ) ) {
							array_push( $exclude, $pid );
						}

						if ( 'outofstock' !== $stock && ! isset( $include[ $pid ] ) ) {
							array_push( $include, $pid );
						}
					}
				}
			}

			return array(
				'in' => $include,
				'ex' => $exclude,
			);
		}

		/**
		 * Get product ids from product attributes
		 *
		 * @param array $data The query args.
		 * @return Product id array.
		 */
		public function get_product_ids_by_attributes( $data = array() ) {
			$hide_outstock = get_option( 'woocommerce_hide_out_of_stock_items' );
			$tax_query     = isset( $data['tax_query'] ) ? $data['tax_query'] : array();
			if ( empty( $data ) || empty( $tax_query ) || 'yes' !== $hide_outstock ) {
				return array();
			}

			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);

			$include = array();
			$exclude = array();

			foreach ( $tax_query as $tax ) {
				$terms = is_string( $tax['terms'] ) ? explode( ',', $tax['terms'] ) : $tax['terms'];
				if ( empty( $terms ) ) {
					continue;
				}

				foreach ( $terms as $term ) {
					$args['tax_query'] = array( // phpcs:ignore
						array(
							'taxonomy' => $tax['taxonomy'],
							'terms'    => $term,
						),
					);

					// Query only variable product.
					$args['tax_query'][] = array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => 'variable',
					);

					$product_id = $this->get_final_product_id_outstock( $tax['taxonomy'], get_posts( $args ), (int) $term );
					if ( ! empty( $product_id['in'] ) ) {
						$include = array_merge( $include, $product_id['in'] );
					}

					if ( ! empty( $product_id['ex'] ) ) {
						$exclude = array_merge( $exclude, $product_id['ex'] );
					}
				}
			}

			return array_diff( array_unique( $exclude ), array_unique( $include ) );
		}

		/**
		 * Update WP_Query before it execute
		 *
		 * @param  obj $query The WP Query.
		 */
		public function filter_pre_get_posts( $query ) {
			// For sortable product filter on admin.
			if ( 'product_filter' === $query->get( 'post_type' ) ) {
				$query->query_vars['suppress_filters'] = false;
			}

			if ( is_admin() || ! $this->detect_url_params() || ! $query->is_main_query() ) {
				return;
			}

			$filters_url = array_flip( $this->get_filters_url() );
			$get_params  = $_GET; // phpcs:ignore
			$output      = array();
			foreach ( $get_params as $key => $value ) {
				$filter_id = isset( $filters_url[ $key ] ) ? $filters_url[ $key ] : false;
				if ( ! $filter_id ) {
					continue;
				}

				$output[ $filter_id ] = htmlspecialchars( $value, ENT_QUOTES );
			}

			$data       = $this->filter_parse_args( $output, true );
			$final_args = $data['args'];
			// Product visibility terms.
			$visibility_terms = wc_get_product_visibility_term_ids();
			$hidden_term      = $visibility_terms['exclude-from-catalog'];
			$term_ids         = array();

			// Ignore hidden product.
			if ( ! empty( $hidden_term ) ) {
				array_push( $term_ids, $hidden_term );
			}

			// Exclude out of stock.
			if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
				$outstock_term = $visibility_terms['outofstock'];
				array_push( $term_ids, $outstock_term );

				// Remove out stock variation.
				$outstock_variation = $this->get_product_ids_by_attributes( $data['attr_args'] );
				if ( ! empty( $outstock_variation ) ) {
					if ( empty( $final_args['post__in'] ) ) {
						$final_args['post__not_in'] = $outstock_variation;
					} else {
						$final_args['post__in'] = array_diff( $final_args['post__in'], $outstock_variation );
					}
				}
			}

			// Finals.
			if ( ! empty( $term_ids ) ) {
				$final_args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $term_ids,
					'operator' => 'NOT IN',
				);
			}

			foreach ( $final_args as $q => $r ) {
				if ( empty( $r ) ) {
					continue;
				}

				$query->set( $q, $r );
			}
		}

		/**
		 * Get hierarchical filter
		 */
		public function get_hierarchical_filter() {
			$data = array();
			$args = array(
				'post_type'      => 'product_filter',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);

			$get_posts = get_posts( $args );
			if ( empty( $get_posts ) ) {
				return $data;
			}

			foreach ( $get_posts as $id ) {
				$type         = get_post_meta( $id, 'woostify_product_filter_type', true );
				$name         = basename( get_permalink( $id ) );
				$source       = get_post_meta( $id, 'woostify_product_filter_data', true );
				$hierarchical = get_post_meta( $id, 'woostify_product_filter_term_hierarchical', true );

				if ( 'checkbox' !== $type || ! $hierarchical ) {
					continue;
				}

				$data[ $name ] = $source;
			}

			return $data;
		}

		/**
		 * Index hierarchical_filter if it exist
		 *
		 * @param int $post_id The post id.
		 */
		public function maybe_index_hierarchical_filter( $post_id = false ) {
			$hierarchical_filter = $this->get_hierarchical_filter();
			if ( empty( $hierarchical_filter ) ) {
				return;
			}

			foreach ( $hierarchical_filter as $name => $hf ) {
				// Get all product ids available.
				$has_product_ids = $this->get_product_ids_by_term( $hf );
				if ( empty( $has_product_ids ) || ( $post_id && ! in_array( $post_id, $has_product_ids, true ) ) ) {
					continue;
				}

				foreach ( $has_product_ids as $pid ) {

					$get_hierarchy_term = get_the_terms( $pid, $hf );
					if ( ! $get_hierarchy_term ) {
						continue;
					}

					$used_terms = array();
					$product    = wc_get_product( $pid );
					$hierarchy  = $this->get_term_depths( $hf );

					// Params.
					$params = array(
						'product_id' => $pid,
						'title'      => get_the_title( $pid ),
						'price'      => $product->get_price(),
						'sku'        => $product->get_sku(),
						'rating'     => $product->get_average_rating(),
					);

					foreach ( $get_hierarchy_term as $hf_term ) {
						// Prevent duplicate terms.
						if ( isset( $used_terms[ $hf_term->term_id ] ) ) {
							continue;
						}
						$used_terms[ $hf_term->term_id ] = true;

						// Handle hierarchical taxonomies.
						$term_info = $hierarchy[ $hf_term->term_id ];
						$depth     = $term_info['depth'];

						$hf_params              = $params;
						$hf_params['term']      = $name;
						$hf_params['term_name'] = $hf_term->name;
						$hf_params['term_id']   = $hf_term->term_id;
						$hf_params['parent_id'] = $hf_term->parent;
						$hf_params['depth']     = $depth;
						$this->insert( $hf_params );

						while ( $depth > 0 ) {
							--$depth;

							$term_id   = $term_info['parent_id'];
							$term_info = $hierarchy[ $term_id ];

							if ( ! isset( $used_terms[ $term_id ] ) ) {
								$used_terms[ $term_id ] = true;

								$depth_params              = $params;
								$depth_params['term']      = $name;
								$depth_params['term_name'] = $term_info['term_name'];
								$depth_params['term_id']   = $term_id;
								$depth_params['parent_id'] = $term_info['parent_id'];
								$depth_params['depth']     = $depth;

								$this->insert( $depth_params );
							}
						}
					}
				}
			}
		}

		/**
		 * Save product to smart filter table
		 *
		 * @param string $table_name | Smart filter table include prefix.
		 * @param array  $items | List record of table, the number of entries should be less than 200.
		 */
		private function save_products_filter_index( $table_name, $items ) {
			$result = array(
				'success' => false,
			);
			global $wpdb;
			// Default data.
			$default = array(
				'product_id'   => 0,
				'title'        => '',
				'price'        => 0,
				'sku'          => '',
				'rating'       => '',
				'term'         => '',
				'term_name'    => '',
				'term_id'      => 0,
				'parent_id'    => 0,
				'depth'        => 0,
				'variation_id' => 0,
			);
			if ( ! empty( $items ) ) {
				$column_text = '`' . implode( '`, `', array_keys( $default ) ) . '`';
				$sql         = "INSERT INTO $table_name ($column_text) VALUES ";
				$row         = array();
				// Build rows string.
				foreach ( $items as $record ) {
					$record = array_merge( $default, $record );
					$row[]  = "('" . implode( "', '", $record ) . "')";
				}
				$values = implode( ',', $row );
				$sql   .= $values;
				$result = $wpdb->query( $sql ); // phpcs:ignore
			}
			return $result;
		}

		/**
		 * Rebuild the facet index
		 *
		 * @param mixed $post_id The post ID.
		 */
		public function index( $post_id = false ) {
			$hide_outstock = get_option( 'woocommerce_hide_out_of_stock_items' );

			if ( $post_id ) {
				// Hide out of stock product, if checked.
				$stock_status = get_post_meta( $post_id, '_stock_status', true );
				if ( 'yes' === $hide_outstock && 'outofstock' === $stock_status ) {
					return;
				}

				// When 'save_post' with 'post_type' is 'product_filter'.
				if ( 'product_filter' === get_post_type( $post_id ) ) {
					$type         = get_post_meta( $post_id, 'woostify_product_filter_type', true );
					$hierarchical = get_post_meta( $post_id, 'woostify_product_filter_term_hierarchical', true );

					if ( 'checkbox' !== $type || ! $hierarchical ) {
						return;
					}

					$this->maybe_index_hierarchical_filter();
					return;
				}
			}

			global $wpdb;
			$table_prefix = $wpdb->prefix;
			$table_name   = $this->table_name();

			// Get products and filters.
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);

			// Ignore hidden product.
			$args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'exclude-from-catalog',
				'operator' => 'NOT IN',
			);

			// Exclude out of stock.
			if ( 'yes' === $hide_outstock ) {
				$product_visibility_terms = wc_get_product_visibility_term_ids();
				$exclude_product          = $product_visibility_terms['outofstock'];

				if ( ! empty( $exclude_product ) ) {
					$args['tax_query'][] = array(
						'taxonomy' => 'product_visibility',
						'field'    => 'term_taxonomy_id',
						'terms'    => $exclude_product,
						'operator' => 'NOT IN',
					);
				}
			}

			// Remove old table.
			$sql        = "DROP TABLE IF EXISTS $table_name";
			$variations = '';
			if ( $post_id ) {
				// Delete old row from table.
				$sql = "DELETE FROM $table_name WHERE product_id = $post_id";

				// Query by post id.
				$args['p'] = $post_id;

				// Query by product variations.
				$variations = " AND p.post_parent = $post_id";
			}

			// Product ids.
			$product_ids = get_posts( $args );
			if ( empty( $product_ids ) ) {
				return;
			}

			// Update wpdb first.
			$wpdb->query( $sql ); // phpcs:ignore

			// Update DB structure.
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$int       = 'BIGINT';
			$create_db = "CREATE TABLE IF NOT EXISTS $table_name (
				id BIGINT unsigned not null auto_increment,
				product_id INT unsigned,
				title VARCHAR(100),
				price DECIMAL(20),
				sku VARCHAR(50),
				rating VARCHAR(50),
				term VARCHAR(100),
				term_name VARCHAR(100),
				term_id $int unsigned default '0',
				parent_id $int unsigned default '0',
				depth $int unsigned default '0',
				variation_id $int unsigned default '0',
				PRIMARY KEY (id)
			) DEFAULT CHARSET=utf8";
			dbDelta( $create_db );
			// END Update DB structure.

			// Index product variation, for seach filter.
			$sql = "
				SELECT DISTINCT 
					ID, 
					post_parent
				FROM {$table_prefix}posts as p
				WHERE 
					p.post_type = 'product_variation'
					AND p.post_status = 'publish'
					$variations
			";
			// Prepare tmp variables.
			$prepare_products      = array();
			$_product_ids          = array();
			$p_meta                = array();
			$product_variation_ids = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore
			if ( ! empty( $product_variation_ids ) ) {
				foreach ( $product_variation_ids as $k ) {
					$_product_ids[] = $k['ID'];
				}
				$str_products = implode( ', ', $_product_ids );
				$sql          = "SELECT meta_id, meta_key, meta_value, post_id FROM {$table_prefix}postmeta pm WHERE meta_key IN ('_stock_status', '_sku') AND post_id IN ($str_products)";
				unset( $str_products );
				$_metas       = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore

				if ( ! empty( $_metas ) ) {
					foreach ( $_metas as $_meta ) {
						if ( empty( $p_meta[ $_meta['post_id'] ] ) ) {
							$p_meta[ $_meta['post_id'] ] = array();
						}
						$p_meta[ $_meta['post_id'] ][ $_meta['meta_key'] ] = $_meta['meta_value']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					}
					unset( $_metas );
				}

				foreach ( $product_variation_ids as $k ) {
					$product_id   = $k['ID'];
					$stock_status = $p_meta[ $product_id ]['_stock_status'];
					if ( 'yes' === $hide_outstock && 'outofstock' === $stock_status ) {
						continue;
					}

					// Params.
					$prepare_products[] = array(
						'parent_id'    => $k['post_parent'],
						'product_id'   => $product_id,
						'variation_id' => $product_id,
						'sku'          => ( ! empty( $p_meta[ $product_id ]['_sku'] ) ? $p_meta[ $product_id ]['_sku'] : '' ),
					);
				}
				unset( $_product_ids );
				unset( $p_meta );
				unset( $product_variation_ids );
			}

			// Default data.
			$default = array(
				'product_id'   => 0,
				'title'        => '',
				'price'        => 0,
				'sku'          => '',
				'rating'       => '',
				'term'         => '',
				'term_name'    => '',
				'term_id'      => 0,
				'parent_id'    => 0,
				'depth'        => 0,
				'variation_id' => 0,
			);

			$save_chunk_number = 100;
			if ( ! empty( $prepare_products ) ) {
				$column_text = '`' . implode( '`, `', array_keys( $default ) ) . '`';
				$chunks_arr  = array_chunk( $prepare_products, $save_chunk_number );
				foreach ( $chunks_arr as $chunk ) {
					$sql = "INSERT INTO $table_name ($column_text) VALUES ";
					$row = array();
					foreach ( $chunk as $record ) {
						$record = array_merge( $default, $record );
						$row[]  = "('" . implode( "', '", $record ) . "')";
					}
					$values = implode( ',', $row );
					$sql   .= $values;
					$wpdb->query( $sql ); // phpcs:ignore
				}

				unset( $prepare_products );
				unset( $chunks_arr );
			}

			// Index product when save_post fire.
			$product_terms = $this->get_product_taxonomies();
			$indexed_ids   = array();
			if ( $post_id ) {
				if ( ! empty( $product_terms ) ) {
					foreach ( $product_terms as $pt ) {
						$get_terms = get_the_terms( $post_id, $pt );
						if ( empty( $get_terms ) || is_wp_error( $get_terms ) ) {
							continue;
						}
						foreach ( $get_terms as $gt ) {
							$product = wc_get_product( $post_id );

							// Params.
							$params = array(
								'product_id' => $post_id,
								'title'      => get_the_title( $post_id ),
								'price'      => $product->get_price(),
								'sku'        => $product->get_sku(),
								'rating'     => $product->get_average_rating(),
								'term'       => $pt,
								'term_name'  => $gt->name,
								'term_id'    => $gt->term_id,
								'parent_id'  => $gt->parent_id,
							);

							$this->insert( $params );
						}
					}
				}

				// Index hierarchical filter.
				$this->maybe_index_hierarchical_filter( $post_id );
			} else { // Index all products.
				$page = ! empty( $_GET['page'] ) ? $_GET['page'] : 0; // phpcs:ignore 
				$this->maybe_index_hierarchical_filter();

				// General data.
				$save_chunk_number = 250;
				$post_per_page     = 5000;
				$start             = ( $post_per_page * $page - 1 );
				$start             = $start < 0 ? 0 : $start;
				// Prepare SQL.
				$sql           = "SELECT ID, post_title FROM
						{$table_prefix}posts as p
						WHERE 
							p.post_type = 'product'
							AND p.post_status = 'publish'
						LIMIT $start, $post_per_page
				";
				$products      = array();
				$list_products = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore
				foreach ( $list_products as $k ) {
					$products[ $k['ID'] ] = array(
						'product_id' => $k['ID'],
						'title'      => str_replace( "'", "\\'", $k['post_title'] ),
					);

				}
				$sql    = "SELECT meta_id, meta_key, meta_value, post_id FROM {$table_prefix}postmeta pm WHERE meta_key IN ('_price', '_sku', '_wc_average_rating')";
				$_metas       = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore

				$meta_keys = array(
					'_price'             => 'price',
					'_sku'               => 'sku',
					'_wc_average_rating' => 'rating',
					'ID'                 => 'product_id',
					'post_title'         => 'title',
				);
				if ( ! empty( $_metas ) ) {
					foreach ( $_metas as $_meta ) {
						if ( ! empty( $products[ $_meta['post_id'] ] ) ) {
							$key = $meta_keys[ $_meta['meta_key'] ];
							// Assign to products.
							$products[ $_meta['post_id'] ][ $key ] = $_meta['meta_value'];
						}
					}
					unset( $_metas );
				}

				$product_term_refs = array();
				// SQL.
				$sql             = "SELECT tt.term_taxonomy_id, tt.taxonomy, tt.term_id, tt.parent, wt.name
					FROM {$table_prefix}term_taxonomy tt
					INNER JOIN {$table_prefix}terms wt ON wt.term_id = tt.term_id
				";
				$results       = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore
				$term_taxonomies = array();
				if ( ! empty( $results ) ) {
					foreach ( $results as $tax ) {
						$term_taxonomies[ $tax['term_taxonomy_id'] ] = array(
							'term'      => $tax['taxonomy'],
							'term_name' => str_replace( "'", "\\'", $tax['name'] ),
							'term_id'   => $tax['term_id'],
							'parent_id' => $tax['parent'],
						);
					}
					unset( $results );
				}

				// SQL.
				$sql = "SELECT *
					FROM {$table_prefix}term_relationships tr 
					ORDER BY tr.term_order
				";
				// Get result.
				$results       = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore

				// Prepare and Save products.
				$prepare_products = array();
				if ( ! empty( $results ) ) {
					foreach ( $results as $ref ) {
						$product_id       = $ref['object_id'];
						$term_taxonomy_id = $ref['term_taxonomy_id'];
						if ( ! empty( $products[ $product_id ] ) && ! empty( $term_taxonomies[ $term_taxonomy_id ] ) ) {
							$prepare_products[] = $products[ $product_id ] + $term_taxonomies[ $term_taxonomy_id ];
						}
						if ( count( $prepare_products ) >= $save_chunk_number ) {
							$this->save_products_filter_index( $table_name, $prepare_products );
							$prepare_products = array();
						}
					}
					unset( $results );
					unset( $term_taxonomies );
					if ( ! empty( $prepare_products ) ) {
						$this->save_products_filter_index( $table_name, $prepare_products );
					}
				}
			}
			// Update time.
			update_option( 'woostify_product_filter_last_indexed', current_time( 'mysql' ), 'no' );
		}

		/**
		 * Add shortcode
		 *
		 * @param array $atts The atts.
		 */
		public function woostify_product_filter_shortcode( $atts ) {
			$defaults = array(
				'id'     => false,
				'layout' => 'vertical',
			);

			$atts   = shortcode_atts( $defaults, $atts );
			$render = Woostify_Filter_Render::init();

			ob_start();
			if ( ! $atts['id'] ) {
				$render->render_all_filter( $atts );
				return ob_get_clean();
			}

			$render->render_filter( $atts['id'] );
			return ob_get_clean();
		}

		/**
		 * Define constant
		 */
		public function define_constants() {
			if ( ! defined( 'WOOSTIFY_PRO_PRODUCT_FILTER' ) ) {
				define( 'WOOSTIFY_PRO_PRODUCT_FILTER', WOOSTIFY_PRO_VERSION );
			}
		}

		/**
		 * Init
		 */
		public function init_action() {
			if ( ! is_blog_installed() || post_type_exists( 'product_filter' ) ) {
				return;
			}

			// Register prodyuct_filter post type.
			$args = array(
				'label'               => _x( 'Smart Product Filter', 'post type label', 'woostify-pro' ),
				'singular_name'       => _x( 'Smart Product Filter', 'post type singular name', 'woostify-pro' ),
				'supports'            => array( 'title' ),
				'rewrite'             => array( 'slug' => 'product-filter' ),
				'add_new_item'        => __( 'Add New Filter', 'woostify-pro' ),
				'show_in_rest'        => false,
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_admin_bar'   => true,
				'show_in_nav_menus'   => true,
				'can_export'          => true,
				'has_archive'         => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'capability_type'     => 'post',
			);
			register_post_type( 'product_filter', $args );

			// Flush rewrite rules.
			if ( ! get_option( 'woostify_product_filter_flush_rewrite_rules' ) ) {
				flush_rewrite_rules();
				update_option( 'woostify_product_filter_flush_rewrite_rules', true );
			}
		}

		/**
		 * Add size guide admin menu
		 */
		public function add_admin_menu() {
			$text  = '<svg width="1em" height="1em" viewBox="0 0 16 16" class="woostify-admin-sub-menu-icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M1.5 1.5A.5.5 0 0 0 1 2v4.8a2.5 2.5 0 0 0 2.5 2.5h9.793l-3.347 3.346a.5.5 0 0 0 .708.708l4.2-4.2a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 8.3H3.5A1.5 1.5 0 0 1 2 6.8V2a.5.5 0 0 0-.5-.5z"/></svg>';
			$text .= '<span class="woostify-admin-sub-menu-text">';
			$text .= esc_html__( 'Settings', 'woostify-pro' );
			$text .= '</span>';

			add_submenu_page( 'woostify-welcome', esc_html__( 'Smart Product Filter', 'woostify-pro' ), esc_html__( 'Smart Product Filter', 'woostify-pro' ), 'manage_options', 'edit.php?post_type=product_filter', '' );
			add_submenu_page( 'woostify-welcome', esc_html__( 'Settings', 'woostify-pro' ), $text, 'manage_options', 'smart-product-filter-settings', array( $this, 'add_settings_page' ) );
			
			global $submenu;
			if ( $submenu['woostify-welcome'] ) {
				foreach ( $submenu['woostify-welcome'] as $key => $value ) {
					if ( $value[2] == 'edit.php?post_type=product_filter' ) {
						$submenu['woostify-welcome'][$key][4] = "callback setting";
					}
				}
			}
		}

		/**
		 * Get options
		 */
		public function get_options() {
			$options = array();

			// General.
			$options['layout'] = get_option( 'woostify_smart_product_filter_layout', 'vertical' );

			// Style.
			$options['enable_remove_filter_button'] = get_option( 'woostify_smart_product_filter_enable_remove_filter_button', '1' );
			$options['scroll_enabled']              = get_option( 'woostify_smart_product_filter_scroll_enabled' );
			$options['scroll_height']               = get_option( 'woostify_smart_product_filter_scroll_height' );
			$options['checkbox_background']         = get_option( 'woostify_smart_product_filter_checkbox_background' );
			$options['radio_background']            = get_option( 'woostify_smart_product_filter_radio_background' );
			$options['radio_icon_color']            = get_option( 'woostify_smart_product_filter_radio_icon_color' );
			$options['input_text_color']            = get_option( 'woostify_smart_product_filter_input_text_color' );
			$options['input_background_color']      = get_option( 'woostify_smart_product_filter_input_background_color' );
			$options['input_border_style']          = get_option( 'woostify_smart_product_filter_input_border_style' );
			$options['input_border_width']          = get_option( 'woostify_smart_product_filter_input_border_width' );
			$options['input_border_color']          = get_option( 'woostify_smart_product_filter_input_border_color' );
			$options['general_layout']              = get_option( 'woostify_smart_product_filter_layout', 'vertical' );
			$options['title_size']                  = get_option( 'woostify_smart_product_filter_heading_size' );
			$options['title_color']                 = get_option( 'woostify_smart_product_filter_heading_color' );
			$options['general_text_size']           = get_option( 'woostify_smart_product_filter_text_size' );
			$options['general_text_color']          = get_option( 'woostify_smart_product_filter_text_color' );
			$options['general_button_width']        = get_option( 'woostify_smart_product_filter_button_width' );
			$options['general_button_width_unit']   = get_option( 'woostify_smart_product_filter_button_width_unit', 'px' );
			$options['general_button_height']       = get_option( 'woostify_smart_product_filter_button_height' );
			$options['general_button_radius']       = get_option( 'woostify_smart_product_filter_button_radius' );
			$options['general_button_color']        = get_option( 'woostify_smart_product_filter_button_color' );
			$options['general_button_bg_color']     = get_option( 'woostify_smart_product_filter_button_bg_color' );
			$options['general_button_border_style'] = get_option( 'woostify_smart_product_filter_button_border_style' );
			$options['general_button_border_width'] = get_option( 'woostify_smart_product_filter_button_border_width' );
			$options['general_button_border_color'] = get_option( 'woostify_smart_product_filter_button_border_color' );
			$options['general_item_count']          = get_option( 'woostify_smart_product_filter_item_count', '1' );
			$options['general_item_count_color']    = get_option( 'woostify_smart_product_filter_item_count_color' );

			// Style.
			$options['active_filter_color']         = get_option( 'woostify_smart_product_filter_active_filter_color' );
			$options['active_filter_bg']            = get_option( 'woostify_smart_product_filter_active_filter_bg' );
			$options['active_filter_border_radius'] = get_option( 'woostify_smart_product_filter_active_filter_border_radius' );
			$options['active_filter_border_style']  = get_option( 'woostify_smart_product_filter_active_filter_border_style' );
			$options['active_filter_border_width']  = get_option( 'woostify_smart_product_filter_active_filter_border_width' );
			$options['active_filter_border_color']  = get_option( 'woostify_smart_product_filter_active_filter_border_color' );
			$options['collapse_enabled']            = get_option( 'woostify_smart_product_filter_collapse_enabled', '1' );
			$options['checkbox_background']         = get_option( 'woostify_smart_product_filter_checkbox_background' );
			$options['radio_background']            = get_option( 'woostify_smart_product_filter_radio_background' );
			$options['radio_icon_color']            = get_option( 'woostify_smart_product_filter_radio_icon_color' );
			$options['input_text_color']            = get_option( 'woostify_smart_product_filter_input_text_color' );
			$options['input_background_color']      = get_option( 'woostify_smart_product_filter_input_background_color' );
			$options['input_border_style']          = get_option( 'woostify_smart_product_filter_input_border_style' );
			$options['input_border_width']          = get_option( 'woostify_smart_product_filter_input_border_width' );
			$options['input_border_color']          = get_option( 'woostify_smart_product_filter_input_border_color' );

			// Range slider.
			$options['rs_primary_color']   = get_option( 'woostify_smart_product_filter_range_slider_primary_color', '#3a3a3a' );
			$options['rs_secondary_color'] = get_option( 'woostify_smart_product_filter_range_slider_seconary_color', '#dddddd' );
			$options['rs_handle']          = get_option( 'woostify_smart_product_filter_range_slider_handle', 'circle' );

			// visual.
			$options['visual_item_border_style'] = get_option( 'woostify_smart_product_filter_visual_item_border_style', 'none' );
			$options['visual_item_border_width'] = get_option( 'woostify_smart_product_filter_visual_item_border_width' );
			$options['visual_item_border_color'] = get_option( 'woostify_smart_product_filter_visual_item_border_color' );

			$options['visual_activated_item_border_style'] = get_option( 'woostify_smart_product_filter_visual_activated_item_border_style', 'none' );
			$options['visual_activated_item_border_width'] = get_option( 'woostify_smart_product_filter_visual_activated_item_border_width' );
			$options['visual_activated_item_border_color'] = get_option( 'woostify_smart_product_filter_visual_activated_item_border_color' );

			$options['visual_color_width']              = get_option( 'woostify_smart_product_filter_visual_color_width' );
			$options['visual_color_height']             = get_option( 'woostify_smart_product_filter_visual_color_height' );
			$options['visual_image_width']              = get_option( 'woostify_smart_product_filter_visual_image_width' );
			$options['visual_image_width_unit']         = get_option( 'woostify_smart_product_filter_visual_image_width_unit' );
			$options['visual_label_color']              = get_option( 'woostify_smart_product_filter_visual_label_color' );
			$options['visual_label_bg_color']           = get_option( 'woostify_smart_product_filter_visual_label_bg_color' );
			$options['visual_activated_label_color']    = get_option( 'woostify_smart_product_filter_visual_activated_label_color' );
			$options['visual_activated_label_bg_color'] = get_option( 'woostify_smart_product_filter_visual_activated_label_bg_color' );

			return $options;
		}

		/**
		 * Create Settings page
		 */
		public function add_settings_page() {
			$options = $this->get_options();
			?>
			<div class="woostify-options-wrap woostify-featured-setting woostify-smart-product-filter-product-setting" data-id="smart-product-filter" data-nonce="<?php echo esc_attr( wp_create_nonce( 'woostify-smart-product-filter-setting-nonce' ) ); ?>">

				<?php Woostify_Admin::get_instance()->woostify_save_option_messages(); ?>

				<?php Woostify_Admin::get_instance()->woostify_welcome_screen_header(); ?>

				<div class="wrap woostify-settings-box">
					<div class="woostify-welcome-container">
						<div class="woostify-notices-wrap">
							<h2 class="notices" style="display:none;"></h2>
						</div>
						<div class="woostify-settings-content">
							<div class="woostify-settings-section-head">
								<h4 class="woostify-settings-section-title">
									<?php esc_html_e( 'Smart Product Filter', 'woostify-pro' ); ?>
									<a class="woostify-settings-section-callback-link" href="<?php echo esc_url( get_admin_url() . 'post-new.php?post_type=product_filter' ); ?>"><?php esc_html_e( 'Add New Filter', 'woostify-pro' ); ?></a>
								</h4>
							</div>

							<div class="woostify-settings-section-content woostify-settings-section-tab">
								<div class="woostify-setting-tab-head">
									<a href="#general" class="tab-head-button"><?php esc_html_e( 'General', 'woostify-pro' ); ?></a>
									<a href="#style" class="tab-head-button"><?php esc_html_e( 'Style', 'woostify-pro' ); ?></a>
								</div>

								<div class="woostify-setting-tab-content-wrapper">
									<?php // General. ?>
									<table class="form-table woostify-setting-tab-content" data-tab="general">
										<?php // layout. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Layout', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_smart_product_filter_layout">
													<option value="vertical" <?php echo selected( $options['layout'], 'vertical' ); ?>><?php esc_html_e( 'Vertical', 'woostify-pro' ); ?></option>
													<option value="horizontal" <?php echo selected( $options['layout'], 'horizontal' ); ?>><?php esc_html_e( 'Horizontal', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>
										<?php // Shortcode. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Shortcode', 'woostify-pro' ); ?>:</th>
											<td>
												<input type="text" value="[woostify_product_filter]" readonly class="w-filter-shortcode">
												<p class="woostify-setting-description"><?php esc_html_e( 'Shortcode to display all filter', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<?php // Index data. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Index Data', 'woostify-pro' ); ?>:</th>
											<td>
												<div class="index-button-wrap">
													<span class="filter-index-button button button-primary"><?php esc_html_e( 'Index', 'woostify-pro' ); ?></span>
													<span class="spinner"></span>
												</div>
											</td>
										</tr>

										<?php $db_info = $this->get_db_info(); // Get fb infor. ?>
										<tr>
											<th><?php esc_html_e( 'Last Update', 'woostify-pro' ); ?>:</th>
											<td>
												<span class="last-index"><?php echo esc_html( isset( $db_info['time'] ) ? $db_info['time'] : '' ); ?></span>
											</td>
										</tr>

										<tr>
											<th><?php esc_html_e( 'Total Product Index', 'woostify-pro' ); ?>:</th>
											<td>
												<span class="index-count"><?php echo esc_html( isset( $db_info['total'] ) ? $db_info['total'] : 0 ); ?></span>
											</td>
										</tr>
									</table>

									<?php // Style. ?>
									<table class="form-table woostify-setting-tab-content" data-tab="style">
										<tr>
											<th scope="row"><?php esc_html_e( 'Enable Active Filter Button', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_enable_remove_filter_button" type="checkbox" <?php checked( $options['enable_remove_filter_button'], '1' ); ?> value="<?php echo isset( $options['enable_remove_filter_button'] ) ? esc_attr( $options['enable_remove_filter_button'] ) : '1'; ?>">
											</td>
										</tr>
										<tr>
											<th scope="row"><?php esc_html_e( 'Collapse Enabled', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_collapse_enabled" type="checkbox" <?php checked( $options['collapse_enabled'], '1' ); ?> value="<?php echo isset( $options['collapse_enabled'] ) ? esc_attr( $options['collapse_enabled'] ) : '1'; ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Scroll Enabled', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_scroll_enabled" type="checkbox" <?php checked( $options['scroll_enabled'], '1' ); ?> value="<?php echo isset( $options['scroll_enabled'] ) ? esc_attr( $options['scroll_enabled'] ) : ''; ?>">
											</td>
										</tr>

										<tr showon="woostify_smart_product_filter_scroll_enabled:1">
											<th scope="row"><?php esc_html_e( 'Scroll Height', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_scroll_height" type="number" min="0" step="1" value="<?php echo isset( $options['scroll_height'] ) ? esc_attr( $options['scroll_height'] ) : ''; ?>">
												<p class="woostify-setting-description"><?php esc_html_e( 'Unit: px', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Item Count', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_item_count" type="checkbox" <?php checked( $options['general_item_count'], '1' ); ?> value="<?php echo isset( $options['general_item_count'] ) ? esc_attr( $options['general_item_count'] ) : ''; ?>">
											</td>
										</tr>

										<tr showon="woostify_smart_product_filter_item_count:1">
											<th scope="row"><?php esc_html_e( 'Item Count Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_item_count_color" type="text" value="<?php echo esc_attr( $options['general_item_count_color'] ); ?>">
											</td>
										</tr>

										<!-- Active Filter -->
										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Active Filter', 'woostify-pro' ); ?></th>
										</tr>
										<!-- Color -->
										<tr>
											<th scope="row"><?php esc_html_e( 'Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_active_filter_color" type="text" value="<?php echo isset( $options['active_filter_color'] ) ? esc_attr( $options['active_filter_color'] ) : ''; ?>">
											</td>
										</tr>
										<!-- Background -->
										<tr>
											<th scope="row"><?php esc_html_e( 'Background', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_active_filter_bg" type="text" value="<?php echo isset( $options['active_filter_bg'] ) ? esc_attr( $options['active_filter_bg'] ) : ''; ?>">
											</td>
										</tr>
										<!-- Border -->
										<tr>
											<th scope="row"><?php esc_html_e( 'Border Radius', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_active_filter_border_radius" type="number" value="<?php echo isset( $options['active_filter_border_radius'] ) ? esc_attr( $options['active_filter_border_radius'] ) : ''; ?>" step="1" min="0">
												<p class="woostify-setting-description"><?php esc_html_e( 'Unit: px', 'woostify-pro' ); ?></p>
											</td>
										</tr>
										<tr>
											<th scope="row"><?php esc_html_e( 'Border Style', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_smart_product_filter_active_filter_border_style">
													<option value="none" <?php echo isset( $options['active_filter_border_style'] ) ? selected( $options['active_filter_border_style'], 'none' ) : ''; ?>><?php esc_html_e( 'Default', 'woostify-pro' ); ?></option>
													<option value="solid" <?php echo isset( $options['active_filter_border_style'] ) ? selected( $options['active_filter_border_style'], 'solid' ) : ''; ?>><?php esc_html_e( 'Solid', 'woostify-pro' ); ?></option>
													<option value="double" <?php echo isset( $options['active_filter_border_style'] ) ? selected( $options['active_filter_border_style'], 'double' ) : ''; ?>><?php esc_html_e( 'Double', 'woostify-pro' ); ?></option>
													<option value="dotted" <?php echo isset( $options['active_filter_border_style'] ) ? selected( $options['active_filter_border_style'], 'dotted' ) : ''; ?>><?php esc_html_e( 'Dotted', 'woostify-pro' ); ?></option>
													<option value ="dashed" <?php echo isset( $options['active_filter_border_style'] ) ? selected( $options['active_filter_border_style'], 'dashed' ) : ''; ?>><?php esc_html_e( 'Dashed', 'woostify-pro' ); ?></option>
													<option value ="groove" <?php echo isset( $options['active_filter_border_style'] ) ? selected( $options['general_button_border_style'], 'groove' ) : ''; ?>><?php esc_html_e( 'Groove', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>
										<tr showon="woostify_smart_product_filter_active_filter_border_style!:none">
											<th scope="row"><?php esc_html_e( 'Border Width', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_active_filter_border_width" type="number" value="<?php echo isset( $options['active_filter_border_width'] ) ? esc_attr( $options['active_filter_border_width'] ) : ''; ?>" step="1" min="0">
												<p class="woostify-setting-description"><?php esc_html_e( 'Unit: px', 'woostify-pro' ); ?></p>
											</td>
										</tr>
										<tr showon="woostify_smart_product_filter_active_filter_border_style!:none">
											<th scope="row"><?php esc_html_e( 'Border Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_active_filter_border_color" type="text" value="<?php echo isset( $options['active_filter_border_color'] ) ? esc_attr( $options['active_filter_border_color'] ) : ''; ?>">
											</td>
										</tr>
										<!-- End Active Filter -->

										<!-- Filter Title -->
										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Filter Title', 'woostify-pro' ); ?></th>
										</tr>

										<?php // Color. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_heading_color" type="text" value="<?php echo isset( $options['title_color'] ) ? esc_attr( $options['title_color'] ) : ''; ?>">
											</td>
										</tr>

										<?php // Font size. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Font Size', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_heading_size" type="number" value="<?php echo isset( $options['title_size'] ) ? esc_attr( $options['title_size'] ) : ''; ?>">
												<p class="woostify-setting-description"><?php esc_html_e( 'Unit: px', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Text', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_text_color" type="text" value="<?php echo esc_attr( $options['general_text_color'] ); ?>">
											</td>
										</tr>

										<?php // Font size. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Font Size', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_text_size" type="number" value="<?php echo esc_attr( $options['general_text_size'] ); ?>">
												<p class="woostify-setting-description"><?php esc_html_e( 'Unit: px', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Button', 'woostify-pro' ); ?></th>
										</tr>

										<?php // Button field. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Width', 'woostify-pro' ); ?>:</th>
											<td>
												<div class="setting-field-unit">
													<input name="woostify_smart_product_filter_button_width" type="number" value="<?php echo esc_attr( $options['general_button_width'] ); ?>">

													<select name="woostify_smart_product_filter_button_width_unit" class="setting-field-unit-select">
														<option value ="px" <?php selected( $options['general_button_width_unit'], 'px' ); ?>><?php esc_html_e( 'PX', 'woostify-pro' ); ?></option>
														<option value ="em" <?php selected( $options['general_button_width_unit'], 'em' ); ?>><?php esc_html_e( 'EM', 'woostify-pro' ); ?></option>
														<option value ="%" <?php selected( $options['general_button_width_unit'], '%' ); ?>><?php esc_html_e( '%', 'woostify-pro' ); ?></option>
													</select>
												</div>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Height', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_button_height" type="number" value="<?php echo esc_attr( $options['general_button_height'] ); ?>">
												<p class="woostify-setting-description"><?php esc_html_e( 'Unit: px', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Text Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_button_color" type="text" value="<?php echo esc_attr( $options['general_button_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Background Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_button_bg_color" type="text" value="<?php echo esc_attr( $options['general_button_bg_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Border Radius', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_button_radius" type="number" value="<?php echo isset( $options['general_button_radius'] ) ? esc_attr( $options['general_button_radius'] ) : ''; ?>" step="1" min="0">
												<p class="woostify-setting-description"><?php esc_html_e( 'Unit: px', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Border Style', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_smart_product_filter_button_border_style">
													<option value="none" <?php echo isset( $options['general_button_border_style'] ) ? selected( $options['general_button_border_style'], 'none' ) : ''; ?>><?php esc_html_e( 'Default', 'woostify-pro' ); ?></option>
													<option value="solid" <?php echo isset( $options['general_button_border_style'] ) ? selected( $options['general_button_border_style'], 'solid' ) : ''; ?>><?php esc_html_e( 'Solid', 'woostify-pro' ); ?></option>
													<option value="double" <?php echo isset( $options['general_button_border_style'] ) ? selected( $options['general_button_border_style'], 'double' ) : ''; ?>><?php esc_html_e( 'Double', 'woostify-pro' ); ?></option>
													<option value="dotted" <?php echo isset( $options['general_button_border_style'] ) ? selected( $options['general_button_border_style'], 'dotted' ) : ''; ?>><?php esc_html_e( 'Dotted', 'woostify-pro' ); ?></option>
													<option value ="dashed" <?php echo isset( $options['general_button_border_style'] ) ? selected( $options['general_button_border_style'], 'dashed' ) : ''; ?>><?php esc_html_e( 'Dashed', 'woostify-pro' ); ?></option>
													<option value ="groove" <?php echo isset( $options['general_button_border_style'] ) ? selected( $options['general_button_border_style'], 'groove' ) : ''; ?>><?php esc_html_e( 'Groove', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>

										<tr showon="woostify_smart_product_filter_button_border_style!:none">
											<th scope="row"><?php esc_html_e( 'Border Width', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_button_border_width" type="number" value="<?php echo isset( $options['general_button_border_width'] ) ? esc_attr( $options['general_button_border_width'] ) : ''; ?>" step="1" min="0">
												<p class="woostify-setting-description"><?php esc_html_e( 'Unit: px', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr showon="woostify_smart_product_filter_button_border_style!:none">
											<th scope="row"><?php esc_html_e( 'Border Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_button_border_color" type="text" value="<?php echo esc_attr( $options['general_button_border_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Checkboxes', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Background', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_checkbox_background" type="text" value="<?php echo isset( $options['checkbox_background'] ) ? esc_attr( $options['checkbox_background'] ) : ''; ?>">
											</td>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Radio', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Background', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_radio_background" type="text" value="<?php echo isset( $options['radio_background'] ) ? esc_attr( $options['radio_background'] ) : ''; ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Icon Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_radio_icon_color" type="text" value="<?php echo isset( $options['radio_icon_color'] ) ? esc_attr( $options['radio_icon_color'] ) : ''; ?>">
											</td>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Input', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_input_text_color" type="text" value="<?php echo isset( $options['input_text_color'] ) ? esc_attr( $options['input_text_color'] ) : ''; ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Background Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_input_background_color" type="text" value="<?php echo isset( $options['input_background_color'] ) ? esc_attr( $options['input_background_color'] ) : ''; ?>">
											</td>
										</tr>

										<?php // Border. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Border Style', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_smart_product_filter_input_border_style">
													<option value="none" <?php echo isset( $options['input_border_style'] ) ? selected( $options['input_border_style'], 'none' ) : ''; ?>><?php esc_html_e( 'Default', 'woostify-pro' ); ?></option>
													<option value="solid" <?php echo isset( $options['input_border_style'] ) ? selected( $options['input_border_style'], 'solid' ) : ''; ?>><?php esc_html_e( 'Solid', 'woostify-pro' ); ?></option>
													<option value="double" <?php echo isset( $options['input_border_style'] ) ? selected( $options['input_border_style'], 'double' ) : ''; ?>><?php esc_html_e( 'Double', 'woostify-pro' ); ?></option>
													<option value="dotted" <?php echo isset( $options['input_border_style'] ) ? selected( $options['input_border_style'], 'dotted' ) : ''; ?>><?php esc_html_e( 'Dotted', 'woostify-pro' ); ?></option>
													<option value ="dashed" <?php echo isset( $options['input_border_style'] ) ? selected( $options['input_border_style'], 'dashed' ) : ''; ?>><?php esc_html_e( 'Dashed', 'woostify-pro' ); ?></option>
													<option value ="groove" <?php echo isset( $options['input_border_style'] ) ? selected( $options['input_border_style'], 'groove' ) : ''; ?>><?php esc_html_e( 'Groove', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>

										<tr showon="woostify_smart_product_filter_input_border_style!:none">
											<th scope="row"><?php esc_html_e( 'Border Width', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_input_border_width" type="number" value="<?php echo isset( $options['input_border_width'] ) ? esc_attr( $options['input_border_width'] ) : ''; ?>" step="1" min="0">
												<p class="woostify-setting-description"><?php esc_html_e( 'Unit: px', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr showon="woostify_smart_product_filter_input_border_style!:none">
											<th scope="row"><?php esc_html_e( 'Border Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_input_border_color" type="text" value="<?php echo isset( $options['input_border_color'] ) ? esc_attr( $options['input_border_color'] ) : ''; ?>">
											</td>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Range Slider', 'woostify-pro' ); ?></th>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Primary Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_range_slider_primary_color" type="text" value="<?php echo esc_attr( $options['rs_primary_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'Secondary Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_range_slider_seconary_color" type="text" value="<?php echo esc_attr( $options['rs_secondary_color'] ); ?>">
											</td>
										</tr>

										<tr>
											<th scope="row"><?php esc_html_e( 'UI Handle', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_smart_product_filter_range_slider_handle">
													<option value ="circle" <?php selected( $options['rs_handle'], 'circle' ); ?>><?php esc_html_e( 'Circle', 'woostify-pro' ); ?></option>
													<option value ="squares" <?php selected( $options['rs_handle'], 'squares' ); ?>><?php esc_html_e( 'Squares', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-separator"></th>
										</tr>

										<tr>
											<th colspan="2" class="table-setting-heading"><?php esc_html_e( 'Visual', 'woostify-pro' ); ?></th>
										</tr>

										<?php // Border. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Border Style', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_smart_product_filter_visual_item_border_style">
													<option value="none" <?php echo isset( $options['visual_item_border_style'] ) ? selected( $options['visual_item_border_style'], 'none' ) : ''; ?>><?php esc_html_e( 'Default', 'woostify-pro' ); ?></option>
													<option value="solid" <?php echo isset( $options['visual_item_border_style'] ) ? selected( $options['visual_item_border_style'], 'solid' ) : ''; ?>><?php esc_html_e( 'Solid', 'woostify-pro' ); ?></option>
													<option value="double" <?php echo isset( $options['visual_item_border_style'] ) ? selected( $options['visual_item_border_style'], 'double' ) : ''; ?>><?php esc_html_e( 'Double', 'woostify-pro' ); ?></option>
													<option value="dotted" <?php echo isset( $options['visual_item_border_style'] ) ? selected( $options['visual_item_border_style'], 'dotted' ) : ''; ?>><?php esc_html_e( 'Dotted', 'woostify-pro' ); ?></option>
													<option value ="dashed" <?php echo isset( $options['visual_item_border_style'] ) ? selected( $options['visual_item_border_style'], 'dashed' ) : ''; ?>><?php esc_html_e( 'Dashed', 'woostify-pro' ); ?></option>
													<option value ="groove" <?php echo isset( $options['visual_item_border_style'] ) ? selected( $options['visual_item_border_style'], 'groove' ) : ''; ?>><?php esc_html_e( 'Groove', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>

										<tr showon="woostify_smart_product_filter_visual_item_border_style!:none">
											<th scope="row"><?php esc_html_e( 'Border Width', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_visual_item_border_width" type="number" value="<?php echo isset( $options['visual_item_border_width'] ) ? esc_attr( $options['visual_item_border_width'] ) : ''; ?>" step="1" min="0">
												<p class="woostify-setting-description"><?php esc_html_e( 'Unit: px', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr showon="woostify_smart_product_filter_visual_item_border_style!:none">
											<th scope="row"><?php esc_html_e( 'Border Color', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_visual_item_border_color" type="text" value="<?php echo isset( $options['visual_item_border_color'] ) ? esc_attr( $options['visual_item_border_color'] ) : ''; ?>">
											</td>
										</tr>

										<?php // Active Border. ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Border Style ( Activated )', 'woostify-pro' ); ?>:</th>
											<td>
												<select name="woostify_smart_product_filter_visual_activated_item_border_style">
													<option value="none" <?php echo isset( $options['visual_activated_item_border_style'] ) ? selected( $options['visual_activated_item_border_style'], 'none' ) : ''; ?>><?php esc_html_e( 'Default', 'woostify-pro' ); ?></option>
													<option value="solid" <?php echo isset( $options['visual_activated_item_border_style'] ) ? selected( $options['visual_activated_item_border_style'], 'solid' ) : ''; ?>><?php esc_html_e( 'Solid', 'woostify-pro' ); ?></option>
													<option value="double" <?php echo isset( $options['visual_activated_item_border_style'] ) ? selected( $options['visual_activated_item_border_style'], 'double' ) : ''; ?>><?php esc_html_e( 'Double', 'woostify-pro' ); ?></option>
													<option value="dotted" <?php echo isset( $options['visual_activated_item_border_style'] ) ? selected( $options['visual_activated_item_border_style'], 'dotted' ) : ''; ?>><?php esc_html_e( 'Dotted', 'woostify-pro' ); ?></option>
													<option value ="dashed" <?php echo isset( $options['visual_activated_item_border_style'] ) ? selected( $options['visual_activated_item_border_style'], 'dashed' ) : ''; ?>><?php esc_html_e( 'Dashed', 'woostify-pro' ); ?></option>
													<option value ="groove" <?php echo isset( $options['visual_activated_item_border_style'] ) ? selected( $options['visual_activated_item_border_style'], 'groove' ) : ''; ?>><?php esc_html_e( 'Groove', 'woostify-pro' ); ?></option>
												</select>
											</td>
										</tr>

										<tr showon="woostify_smart_product_filter_visual_activated_item_border_style!:none">
											<th scope="row"><?php esc_html_e( 'Border Width ( Activated )', 'woostify-pro' ); ?>:</th>
											<td>
												<input name="woostify_smart_product_filter_visual_activated_item_border_width" type="number" value="<?php echo isset( $options['visual_activated_item_border_width'] ) ? esc_attr( $options['visual_activated_item_border_width'] ) : ''; ?>" step="1" min="0">
												<p class="woostify-setting-description"><?php esc_html_e( 'Unit: px', 'woostify-pro' ); ?></p>
											</td>
										</tr>

										<tr showon="woostify_smart_product_filter_visual_activated_item_border_style!:none">
											<th scope="row"><?php esc_html_e( 'Border Color ( Activated )', 'woostify-pro' ); ?>:</th>
											<td>
												<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_visual_activated_item_border_color" type="text" value="<?php echo isset( $options['visual_activated_item_border_color'] ) ? esc_attr( $options['visual_activated_item_border_color'] ) : ''; ?>">
											</td>
										</tr>

										<tr>
											<th colspan="2" style="padding: 0">
												<div class="woostify-setting-tab-head">
													<a href="#style_visual_color" class="tab-head-button active"><?php esc_html_e( 'Color', 'woostify-pro' ); ?></a>
													<a href="#style_visual_image" class="tab-head-button"><?php esc_html_e( 'Image', 'woostify-pro' ); ?></a>
													<a href="#style_visual_label" class="tab-head-button"><?php esc_html_e( 'Label', 'woostify-pro' ); ?></a>
												</div>
												<div class="woostify-setting-tab-content-wrapper">
													<table class="form-table woostify-setting-tab-content active" data-tab="style_visual_color">
														<tbody>
															<tr>
																<th scope="row"><?php esc_html_e( 'Width', 'woostify-pro' ); ?>:</th>
																<td>
																	<input name="woostify_smart_product_filter_visual_color_width" type="number" value="<?php echo isset( $options['visual_color_width'] ) ? esc_attr( $options['visual_color_width'] ) : ''; ?>">
																	<p class="woostify-setting-description"><?php esc_html_e( 'Unit: px', 'woostify-pro' ); ?></p>
																</td>
															</tr>
															<tr>
																<th scope="row"><?php esc_html_e( 'Height', 'woostify-pro' ); ?>:</th>
																<td>
																	<input name="woostify_smart_product_filter_visual_color_height" type="number" value="<?php echo isset( $options['visual_color_height'] ) ? esc_attr( $options['visual_color_height'] ) : ''; ?>">
																	<p class="woostify-setting-description"><?php esc_html_e( 'Unit: px', 'woostify-pro' ); ?></p>
																</td>
															</tr>
														</tbody>
													</table>
													<table class="form-table woostify-setting-tab-content" data-tab="style_visual_image">
														<tbody>
														<tr>
															<th scope="row"><?php esc_html_e( 'Width', 'woostify-pro' ); ?>:</th>
															<td>
																<div class="setting-field-unit">
																	<input name="woostify_smart_product_filter_visual_image_width" type="number" value="<?php echo isset( $options['visual_image_width'] ) ? esc_attr( $options['visual_image_width'] ) : ''; ?>">

																	<select name="woostify_smart_product_filter_visual_image_width_unit" class="setting-field-unit-select">
																		<option value ="px" <?php echo isset( $options['visual_image_width_unit'] ) ? selected( $options['visual_image_width_unit'], 'px' ) : ''; ?>><?php esc_html_e( 'PX', 'woostify-pro' ); ?></option>
																		<option value ="%" <?php echo isset( $options['visual_image_width_unit'] ) ? selected( $options['visual_image_width_unit'], '%' ) : ''; ?>><?php esc_html_e( '%', 'woostify-pro' ); ?></option>
																	</select>
																</div>
															</td>
														</tr>
														</tbody>
													</table>
													<table class="form-table woostify-setting-tab-content" data-tab="style_visual_label">
														<tbody>
														<tr>
															<th scope="row"><?php esc_html_e( 'Text Color', 'woostify-pro' ); ?>:</th>
															<td>
																<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_visual_label_color" type="text" value="<?php echo isset( $options['visual_label_color'] ) ? esc_attr( $options['visual_label_color'] ) : ''; ?>">
															</td>
														</tr>
														<tr>
															<th scope="row"><?php esc_html_e( 'Background Color', 'woostify-pro' ); ?>:</th>
															<td>
																<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_visual_label_bg_color" type="text" value="<?php echo isset( $options['visual_label_bg_color'] ) ? esc_attr( $options['visual_label_bg_color'] ) : ''; ?>">
															</td>
														</tr>
														<tr>
															<th scope="row"><?php esc_html_e( 'Text Color ( Activated )', 'woostify-pro' ); ?>:</th>
															<td>
																<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_visual_activated_label_color" type="text" value="<?php echo isset( $options['visual_activated_label_color'] ) ? esc_attr( $options['visual_activated_label_color'] ) : ''; ?>">
															</td>
														</tr>
														<tr>
															<th scope="row"><?php esc_html_e( 'Background Color ( Activated )', 'woostify-pro' ); ?>:</th>
															<td>
																<input class="woostify-admin-color-picker" name="woostify_smart_product_filter_visual_activated_label_bg_color" type="text" value="<?php echo isset( $options['visual_activated_label_bg_color'] ) ? esc_attr( $options['visual_activated_label_bg_color'] ) : ''; ?>">
															</td>
														</tr>
														</tbody>
													</table>
												</div>
											</th>
										</tr>
									</table>
								</div>
							</div>

							<div class="woostify-settings-section-footer position-sticky">
								<span class="save-options button button-primary"><?php esc_html_e( 'Save', 'woostify-pro' ); ?></span>
								<span class="spinner"></span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Metabox
		 */
		public function add_meta_box() {
			add_meta_box(
				'woostify_product_filter',
				__( 'Settings', 'woostify-pro' ),
				array( $this, 'add_meta_box_callback' ),
				null,
				'normal',
				'high'
			);
		}

		/**
		 * Set default value for post meta
		 *
		 * @param int    $id  The post id.
		 * @param string $str The meta name.
		 * @param string $val The default value.
		 */
		public function set_value( $id = null, $str = '', $val = '' ) {
			return metadata_exists( 'post', $id, $str ) ? get_post_meta( $id, $str, true ) : $val;
		}

		/**
		 * Metabox callback
		 *
		 * @param object $post The post.
		 */
		public function add_meta_box_callback( $post ) {
			wp_nonce_field( 'woostify_product_filter_metabox_nonce', 'woostify_product_filter_nonce_value' );
			$product_attr = wc_get_attribute_taxonomies();
			$post_id      = $post->ID;
			$filter_label = get_post_meta( $post_id, 'woostify_product_filter_label', true );
			$filter_type  = get_post_meta( $post_id, 'woostify_product_filter_type', true );
			$filter_data  = get_post_meta( $post_id, 'woostify_product_filter_data', true );

			// Check range.
			$filter_check_range_min   = get_post_meta( $post_id, 'woostify_product_filter_check_range_min', true );
			$filter_check_range_max   = get_post_meta( $post_id, 'woostify_product_filter_check_range_max', true );
			$filter_check_range_query = $this->set_value( $post_id, 'woostify_product_filter_check_range_query', '_price' );

			// Range slider.
			$filter_range_slider_query = $this->set_value( $post_id, 'woostify_product_filter_range_slider_query', '_price' );

			// Term condition data.
			$term_condition_select = get_post_meta( $post_id, 'woostify_product_filter_term_condition_select', true );
			$term_condition_field  = get_post_meta( $post_id, 'woostify_product_filter_term_condition_field', true );

			// Sort order.
			$sort_by = $this->set_value( $post_id, 'woostify_product_filter_sort_by', 'count' );

			// Product category data.
			$term_hierarchical   = intval( get_post_meta( $post_id, 'woostify_product_filter_term_hierarchical', true ) );
			$hierarchical_expand = intval( get_post_meta( $post_id, 'woostify_product_filter_term_hierarchical_expand', true ) );

			// Search.
			$search_placeholder = $this->set_value( $post_id, 'woostify_product_filter_search_placeholder', __( 'Type to search...', 'woostify-pro' ) );

			// Quick search.
			$quick_search        = get_post_meta( $post_id, 'woostify_product_filter_quick_search', true );
			$quick_search_holder = get_post_meta( $post_id, 'woostify_product_filter_quick_search_holder', true );

			// Date range.
			$date_range_placeholder_from   = $this->set_value( $post_id, 'woostify_product_filter_date_range_from', __( 'From', 'woostify-pro' ) );
			$date_range_placeholder_to     = $this->set_value( $post_id, 'woostify_product_filter_date_range_to', __( 'To', 'woostify-pro' ) );
			$date_range_placeholder_search = $this->set_value( $post_id, 'woostify_product_filter_date_range_search', __( 'Search', 'woostify-pro' ) );

			// Rating.
			$rating_label    = $this->set_value( $post_id, 'woostify_product_filter_rating_label', __( 'And Up', 'woostify-pro' ) );
			$rating_selected = $this->set_value( $post_id, 'woostify_product_filter_rating_selected', __( '@N & Up', 'woostify-pro' ) );

			// Sort order.
			$sort_order = $this->set_value( $post_id, 'woostify_product_filter_sort_order', 'menu_order|price-desc|price|date|rating|popularity' );

			// Limit.
			$soft_limit = get_post_meta( $post_id, 'woostify_product_filter_soft_limit', true );
			$limit      = get_post_meta( $post_id, 'woostify_product_filter_limit', true );

			// Stock option.
			$stock            = get_post_meta( $post_id, 'woostify_product_filter_stock', 'onsale|instock|outofstock|onbackorder' );
			$stock            = $this->set_value( $post_id, 'woostify_product_filter_stock', 'onsale|instock|outofstock|onbackorder' );
			$show_oufofstock  = get_post_meta( $post_id, 'woostify_product_filter_oufofstock', true );
			$show_instock     = get_post_meta( $post_id, 'woostify_product_filter_instock', true );
			$show_onbackorder = get_post_meta( $post_id, 'woostify_product_filter_onbackorder', true );
			?>

			<table class="form-table admin-product-filter" data-type="<?php echo esc_attr( $filter_type ); ?>">
				<tbody>
					<tr class="woostify-filter-item">
						<th><?php esc_html_e( 'Filter Label', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container">
								<input name="woostify_product_filter_label" type="text" placeholder="<?php esc_attr_e( 'Enter filter label', 'woostify-pro' ); ?>" value="<?php echo esc_attr( $filter_label ); ?>">
							</div>
						</td>
					</tr>

					<tr class="woostify-filter-item">
						<th><?php esc_html_e( 'Filter Type', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container">
								<select class="woostify-filter-value" name="woostify_product_filter_type">
									<option value=""><?php esc_html_e( 'Select filter type', 'woostify-pro' ); ?></option>
									<option value="radio" <?php selected( $filter_type, 'radio' ); ?>><?php esc_html_e( 'Radio', 'woostify-pro' ); ?></option>
									<option value="search" <?php selected( $filter_type, 'search' ); ?>><?php esc_html_e( 'Search', 'woostify-pro' ); ?></option>
									<option value="select" <?php selected( $filter_type, 'select' ); ?>><?php esc_html_e( 'Select', 'woostify-pro' ); ?></option>
									<option value="rating" <?php selected( $filter_type, 'rating' ); ?>><?php esc_html_e( 'Rating', 'woostify-pro' ); ?></option>
									<option value="range-slider" <?php selected( $filter_type, 'range-slider' ); ?>><?php esc_html_e( 'Range Slider', 'woostify-pro' ); ?></option>
									<option value="checkbox" <?php selected( $filter_type, 'checkbox' ); ?>><?php esc_html_e( 'Checkbox', 'woostify-pro' ); ?></option>
									<option value="check-range" <?php selected( $filter_type, 'check-range' ); ?>><?php esc_html_e( 'Check range', 'woostify-pro' ); ?></option>
									<option value="date-range" <?php selected( $filter_type, 'date-range' ); ?>><?php esc_html_e( 'Date range', 'woostify-pro' ); ?></option>
									<option value="sort-order" <?php selected( $filter_type, 'sort-order' ); ?>><?php esc_html_e( 'Sort order', 'woostify-pro' ); ?></option>
									<?php if ( defined( 'WOOSTIFY_PRO_VARIATION_SWATCHES' ) ) { ?>
									<option value="visual" <?php selected( $filter_type, 'visual' ); ?>><?php esc_html_e( 'Visual', 'woostify-pro' ); ?></option>
									<?php } ?>
									<option value="stock" <?php selected( $filter_type, 'stock' ); ?>><?php esc_html_e( 'Stock', 'woostify-pro' ); ?></option>
								</select>
							</div>
						</td>
					</tr>

					<?php
					// For sort order.
					$stock_data = array(
						'onsale'      => __( 'On Sale', 'woostify-pro' ),
						'instock'     => __( 'In Stock', 'woostify-pro' ),
						'outofstock'  => __( 'Out of Stock', 'woostify-pro' ),
						'onbackorder' => __( 'On Backorder', 'woostify-pro' ),
					);
					$stock_data = apply_filters( 'woostify_sort_order_field', $stock_data );
					?>

					<tr class="woostify-filter-item<?php echo esc_attr( 'stock' === $filter_type ? '' : ' hidden' ); ?>" data-type="stock">
						<th><?php esc_html_e( 'Stock', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container woostify-multi-selection">
								<input class="woostify-multi-select-value" name="woostify_product_filter_stock" type="hidden" value="<?php echo esc_attr( $stock ); ?>">

								<?php // Selected value. ?>
								<div class="woostify-multi-select-selection">
									<div class="woostify-multi-selection-inner">
										<?php
										$stock = explode( '|', $stock );
										if ( ! empty( $stock ) ) {
											foreach ( $stock as $s ) {
												if ( ! isset( $stock_data[ $s ] ) ) {
													continue;
												}
												?>
												<span class="woostify-multi-select-id" data-id="<?php echo esc_attr( $s ); ?>">
													<?php echo esc_html( $stock_data[ $s ] ); ?>
													<i class="woostify-multi-remove-id dashicons dashicons-no-alt"></i>
												</span>
												<?php
											}
										}
										?>
									</div>

									<input data-search="immediate" type="text" class="woostify-multi-select-search" placeholder="<?php esc_attr_e( 'Please enter 1 or more characters', 'woostify-pro' ); ?>">
								</div>

								<?php // Dropdown select. ?>
								<div class="woostify-multi-select-dropdown">
									<?php
									if ( ! empty( $stock_data ) ) {
										foreach ( $stock_data as $k => $v ) {
											$class = in_array( $k, $stock, true ) ? 'woostify-multi-select-id disabled' : 'woostify-multi-select-id';
											?>
											<span class="<?php echo esc_attr( $class ); ?>" data-id="<?php echo esc_attr( $k ); ?>">
												<?php echo esc_html( $v ); ?>
											</span>
											<?php
										}
									}
									?>
								</div>
							</div>
						</td>
					</tr>

					<?php
					// For Radio, Select, Checkbox field.
					$class_condition = '';

					if ( 'stock' === $filter_type ) {
						$class_condition .= ' stock';
					}

					if ( 'visual' === $filter_type ) {
						$class_condition = ' product-attr-only';
					}
					?>

					<tr class="woostify-filter-item<?php echo esc_attr( in_array( $filter_type, array( 'radio', 'select', 'checkbox', 'visual' ), true ) ? '' : ' hidden' ); ?>" data-type="radio|select|checkbox|visual">
						<th><?php esc_html_e( 'Data Source', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container">
								<div class="w-filter-field">
									<select name="woostify_product_filter_data" class="<?php echo esc_attr( $class_condition ); ?>">
										<option value=""><?php esc_html_e( 'Select data source', 'woostify-pro' ); ?></option>
										<option class ="product-taxonomy" value="product_cat" <?php selected( $filter_data, 'product_cat' ); ?>><?php esc_html_e( 'Product Category', 'woostify-pro' ); ?></option>
										<option class ="product-taxonomy" value="product_tag" <?php selected( $filter_data, 'product_tag' ); ?>><?php esc_html_e( 'Product Tag', 'woostify-pro' ); ?></option>
										<option class ="product-taxonomy product-stock" value="product_stock" <?php selected( $filter_data, 'product_stock' ); ?>><?php esc_html_e( 'Product Stock', 'woostify-pro' ); ?></option>
										<?php
										$product_attr_name_arr = array();
										if ( ! empty( $product_attr ) ) {
											foreach ( $product_attr as $tax ) {
												if ( taxonomy_exists( wc_attribute_taxonomy_name( $tax->attribute_name ) ) ) {
													array_push( $product_attr_name_arr, wc_attribute_taxonomy_name( $tax->attribute_name ) );
													?>
													<option value="<?php echo esc_attr( $tax->attribute_id ); ?>" <?php selected( $filter_data, $tax->attribute_id ); ?>><?php esc_html_e( 'Product attribute', 'woostify-pro' ); ?>: <?php echo esc_html( $tax->attribute_label ); ?></option>
													<?php
												}
											}
										}

										$taxonomys = get_object_taxonomies( array(
											'product',
										) );

										$taxonomy_no_display = array(
											'product_cat',
											'product_tag',
											'product_type',
											'product_visibility',
											'product_shipping_class',
										);	

										$taxonomy_no_display = array_merge($product_attr_name_arr, $taxonomy_no_display);
								
										if ( !empty( $taxonomys ) ) {
											foreach ($taxonomys as $key => $tax) {
												$taxonomy = get_taxonomy($tax);
												if ( taxonomy_exists( $tax ) ) {
													if( !in_array($tax, $taxonomy_no_display) ){
														?>
														<option class ="product-taxonomy <?php echo esc_attr( $tax ); ?>" value="<?php echo esc_attr( $tax ); ?>" <?php selected( $filter_data, $tax ); ?>>
															<?php esc_html_e( $taxonomy->label, 'woostify-pro' ); ?>
														</option>
														<?php
													}
												}
											}
										}
										?>
									</select>
								</div>

								<div class="w-filter-item-pack w-filter-hierarchical-data<?php echo esc_attr( 'product_cat' === $filter_data ? '' : ' hidden' ); ?>">
									<div class="w-filter-field">
										<label for="w-filter-source-term-hierarchical">
											<input type="checkbox" <?php checked( $term_hierarchical, 1 ); ?> value="<?php echo esc_attr( $term_hierarchical ); ?>" id="w-filter-source-term-hierarchical" name="woostify_product_filter_term_hierarchical">
											<span><?php esc_html_e( 'Hierarchical', 'woostify-pro' ); ?></span>
										</label>
									</div>

									<div class="w-filter-field<?php echo esc_attr( 1 !== $term_hierarchical ? ' hidden' : '' ); ?>">
										<label for="w-filter-source-term-hierarchical-expand">
											<input type="checkbox" <?php checked( $hierarchical_expand, 1 ); ?> value="<?php echo esc_attr( $hierarchical_expand ); ?>" id="w-filter-source-term-hierarchical-expand" name="woostify_product_filter_term_hierarchical_expand">
											<span><?php esc_html_e( 'Expand by default', 'woostify-pro' ); ?></span>
										</label>
									</div>
								</div>
							</div>
						</td>
					</tr>

					<?php // Quick search. ?>
					<tr class="woostify-filter-item<?php echo esc_attr( in_array( $filter_type, array( 'radio', 'checkbox' ), true ) ? '' : ' hidden' ); ?>" data-type="radio|checkbox">
						<th><?php esc_html_e( 'Quick Search', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container">
								<div class="w-filter-field">
									<label for="woostify_product_filter_quick_search">
										<input type="checkbox" id="woostify_product_filter_quick_search" name="woostify_product_filter_quick_search" value="<?php echo esc_attr( $quick_search ); ?>" <?php checked( $quick_search, '1' ); ?>>
										<span><?php esc_html_e( 'This option will add a field for quick search', 'woostify-pro' ); ?></span>
									</label>
								</div>

								<div class="w-filter-field" data-require="woostify_product_filter_quick_search:1">
									<label for="woostify_product_filter_quick_search_holder">
										<input type="text" id="woostify_product_filter_quick_search_holder" value="<?php echo esc_attr( $quick_search_holder ); ?>" name="woostify_product_filter_quick_search_holder" placeholder="<?php esc_attr_e( 'Placeholder text', 'woostify-pro' ); ?>">
									</label>
								</div>
							</div>
						</td>
					</tr>

					<?php
					// Soft limit.
					$soft_limit_class[] = 'woostify-filter-item soft-limit';
					$soft_limit_class[] = in_array( $filter_type, array( 'checkbox', 'radio' ), true ) ? '' : 'hidden';
					$soft_limit_class[] = $term_hierarchical ? 'soft-limit-hidden' : '';
					?>
					<tr showon="woostify_product_filter_quick_search!:1" class="<?php echo esc_attr( implode( ' ', array_filter( $soft_limit_class ) ) ); ?>" data-type="checkbox|radio">
						<th><?php esc_html_e( 'Soft Limit', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container">
								<div class="w-filter-field">
									<input type="text" name="woostify_product_filter_soft_limit" value="<?php echo esc_attr( $soft_limit ); ?>" class="short-input">
									<p class="w-filter-desc"><?php esc_html_e( 'Show a toggle link after this many choices (empty for no limit)', 'woostify-pro' ); ?></p>
								</div>
							</div>
						</td>
					</tr>

					<?php // Limit. ?>
					<tr showon="woostify_product_filter_quick_search!:1" class="woostify-filter-item<?php echo esc_attr( in_array( $filter_type, array( 'radio', 'select', 'checkbox' ), true ) ? '' : ' hidden' ); ?>" data-type="radio|select|checkbox">
						<th><?php esc_html_e( 'Total', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container">
								<div class="w-filter-field">
									<input type="text" name="woostify_product_filter_limit" value="<?php echo esc_attr( $limit ); ?>" class="short-input">
									<p class="w-filter-desc"><?php esc_html_e( 'The maximum number of choices to show (empty for no limit)', 'woostify-pro' ); ?></p>
								</div>
							</div>
						</td>
					</tr>

					<?php // Condition. ?>
					<tr class="woostify-filter-item<?php echo esc_attr( in_array( $filter_type, array( 'radio', 'select', 'checkbox' ), true ) ? '' : ' hidden' ); ?>" data-type="radio|select|checkbox">
						<th><?php esc_html_e( 'Condition', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container">
								<div class="w-filter-item-pack w-filter-condition-data">
									<div class="w-filter-field">
										<select class="w-filter-condition-select" name="woostify_product_filter_term_condition_select">
											<option value=""><?php esc_html_e( 'Off', 'woostify-pro' ); ?></option>
											<option <?php selected( $term_condition_select, 'exclude' ); ?> value="exclude"><?php esc_html_e( 'Exclude these values', 'woostify-pro' ); ?></option>
											<option <?php selected( $term_condition_select, 'include' ); ?> value="include"><?php esc_html_e( 'Show only these values', 'woostify-pro' ); ?></option>
										</select>

										<p class="w-filter-desc"><?php esc_html_e( 'Include or exclude certain values', 'woostify-pro' ); ?></p>
									</div>

									<div class="w-filter-field w-filter-condition-field<?php echo esc_attr( ! $term_condition_select ? ' hidden' : '' ); ?>">
										<textarea placeholder="<?php esc_attr_e( 'Enter some values. Each name on a line.', 'woostify-pro' ); ?>" name="woostify_product_filter_term_condition_field"><?php echo esc_html( $term_condition_field ); ?></textarea>
									</div>
								</div>
							</div>
						</td>
					</tr>

					<?php // Sort by. ?>
					<tr class="woostify-filter-item<?php echo esc_attr( in_array( $filter_type, array( 'radio', 'select', 'checkbox' ), true ) ? '' : ' hidden' ); ?>" data-type="radio|select|checkbox">
						<th><?php esc_html_e( 'Sort by', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container">
								<div class="w-filter-item-pack w-filter-condition-data">
									<div class="w-filter-field">
										<select class="w-filter-condition-select" name="woostify_product_filter_sort_by">
											<option <?php selected( $sort_by, 'count' ); ?> value="count"><?php esc_html_e( 'Highest Count', 'woostify-pro' ); ?></option>
											<option <?php selected( $sort_by, 'term_name' ); ?> value="term_name"><?php esc_html_e( 'Term Name', 'woostify-pro' ); ?></option>
											<option <?php selected( $sort_by, 'term_order' ); ?> value="term_order"><?php esc_html_e( 'Term Order', 'woostify-pro' ); ?></option>
										</select>
									</div>
								</div>
							</div>
						</td>
					</tr>

					<?php // Range slider. ?>
					<tr class="woostify-filter-item<?php echo esc_attr( 'range-slider' === $filter_type ? '' : ' hidden' ); ?>" data-type="range-slider">
						<th><?php esc_html_e( 'Range Slider', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container">
								<div class="w-filter-field">
									<span class="w-filter-label"><?php esc_html_e( 'Query field key', 'woostify-pro' ); ?></span>
									<input type="text" class="w-filter-item-query-value" value="<?php echo esc_attr( $filter_range_slider_query ); ?>" readonly name="woostify_product_filter_range_slider_query">
								</div>
							</div>
						</td>
					</tr>

					<?php // Check range. ?>
					<tr class="woostify-filter-item<?php echo esc_attr( 'check-range' === $filter_type ? '' : ' hidden' ); ?>" data-type="check-range">
						<th><?php esc_html_e( 'Price Range', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container w-filter-check-range">
								<div class="w-filter-container-inner">
									<?php
									if ( empty( $filter_check_range_min ) ) {
										?>
										<div class="w-filter-item-pack w-filter-has-flex-item">
											<span class="w-filter-range-item-remove dashicons dashicons-no-alt"></span>

											<div class="w-filter-field">
												<span class="w-filter-label"><?php esc_html_e( 'Min value', 'woostify-pro' ); ?></span>
												<input class="w-filter-required" required type="number" name="woostify_product_filter_check_range_min[]" value="0">
											</div>

											<div class="w-filter-field">
												<span class="w-filter-label"><?php esc_html_e( 'Max value', 'woostify-pro' ); ?></span>
												<input class="w-filter-required" required type="number" name="woostify_product_filter_check_range_max[]" value="20">
											</div>
										</div>
										<?php
									} else {
										foreach ( $filter_check_range_min as $k => $v ) {
											?>
											<div class="w-filter-item-pack w-filter-has-flex-item">
												<span class="w-filter-range-item-remove dashicons dashicons-no-alt"></span>

												<div class="w-filter-field">
													<span class="w-filter-label"><?php esc_html_e( 'Min value', 'woostify-pro' ); ?></span>
													<input class="w-filter-required" required type="number" name="woostify_product_filter_check_range_min[]" value="<?php echo esc_attr( $v ); ?>">
												</div>

												<div class="w-filter-field">
													<span class="w-filter-label"><?php esc_html_e( 'Max value', 'woostify-pro' ); ?></span>
													<input class="w-filter-required" required type="number" name="woostify_product_filter_check_range_max[]" value="<?php echo esc_attr( isset( $filter_check_range_max[ $k ] ) ? $filter_check_range_max[ $k ] : 0 ); ?>">
												</div>
											</div>
											<?php
										}
									}
									?>
								</div>

								<div class="w-filter-field">
									<span class="w-filter-label"><?php esc_html_e( 'Query field key', 'woostify-pro' ); ?></span>
									<input type="text" class="w-filter-item-query-value" value="<?php echo esc_attr( $filter_check_range_query ); ?>" readonly name="woostify_product_filter_check_range_query">
								</div>

								<button class="w-filter-range-item-add button button-primary button-large" type="button"><?php esc_html_e( 'Add new option', 'woostify-pro' ); ?></button>
							</div>
						</td>
					</tr>

					<?php // For search type. ?>
					<tr class="woostify-filter-item<?php echo esc_attr( 'search' === $filter_type ? '' : ' hidden' ); ?>" data-type="search">
						<th><?php esc_html_e( 'Search Placeholder', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container">
								<input type="text" name="woostify_product_filter_search_placeholder" value="<?php echo esc_attr( $search_placeholder ); ?>">
							</div>
						</td>
					</tr>

					<?php // For date range type. ?>
					<tr class="woostify-filter-item<?php echo esc_attr( 'date-range' === $filter_type ? '' : ' hidden' ); ?>" data-type="date-range">
						<th><?php esc_html_e( 'Date Range Placeholder', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container w-filter-has-flex-item">
								<div class="w-filter-field">
									<span class="w-filter-label"><?php esc_html_e( 'From', 'woostify-pro' ); ?></span>
									<input type="text" name="woostify_product_filter_date_range_from" value="<?php echo esc_attr( $date_range_placeholder_from ); ?>">
								</div>

								<div class="w-filter-field">
									<span class="w-filter-label"><?php esc_html_e( 'To', 'woostify-pro' ); ?></span>
									<input type="text" name="woostify_product_filter_date_range_to" value="<?php echo esc_attr( $date_range_placeholder_to ); ?>">
								</div>

								<div class="w-filter-field">
									<span class="w-filter-label"><?php esc_html_e( 'Search', 'woostify-pro' ); ?></span>
									<input type="text" name="woostify_product_filter_date_range_search" value="<?php echo esc_attr( $date_range_placeholder_search ); ?>">
								</div>
							</div>
						</td>
					</tr>

					<?php // For rating type. ?>
					<tr class="woostify-filter-item<?php echo esc_attr( 'rating' === $filter_type ? '' : ' hidden' ); ?>" data-type="rating">
						<th><?php esc_html_e( 'Rating Placeholder', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container w-filter-has-flex-item">
								<div class="w-filter-field">
									<span class="w-filter-label"><?php esc_html_e( 'Rating Label', 'woostify-pro' ); ?></span>
									<input type="text" name="woostify_product_filter_rating_label" value="<?php echo esc_attr( $rating_label ); ?>">
								</div>

								<div class="w-filter-field">
									<span class="w-filter-label"><?php esc_html_e( 'Rating Selected', 'woostify-pro' ); ?></span>
									<input type="text" name="woostify_product_filter_rating_selected" value="<?php echo esc_attr( $rating_selected ); ?>">
									<p class="w-filter-desc"><?php esc_html_e( '@N is rating number', 'woostify-pro' ); ?></p>
								</div>
							</div>
						</td>
					</tr>

					<?php
					// For sort order.
					$sort_order_data = array(
						'menu_order' => __( 'Default sorting', 'woostify-pro' ),
						'popularity' => __( 'Sort by popularity', 'woostify-pro' ),
						'rating'     => __( 'Sort by average rating', 'woostify-pro' ),
						'date'       => __( 'Sort by latest', 'woostify-pro' ),
						'price'      => __( 'Sort by price: low to high', 'woostify-pro' ),
						'price-desc' => __( 'Sort by price: high to low', 'woostify-pro' ),
					);
					$sort_order_data = apply_filters( 'woostify_sort_order_field', $sort_order_data );
					?>

					<tr class="woostify-filter-item<?php echo esc_attr( 'sort-order' === $filter_type ? '' : ' hidden' ); ?>" data-type="sort-order">
						<th><?php esc_html_e( 'Sort Order', 'woostify-pro' ); ?>:</th>
						<td>
							<div class="w-filter-container woostify-multi-selection">
								<input class="woostify-multi-select-value" name="woostify_product_filter_sort_order" type="hidden" value="<?php echo esc_attr( $sort_order ); ?>">

								<?php // Selected value. ?>
								<div class="woostify-multi-select-selection">
									<div class="woostify-multi-selection-inner">
										<?php
										$sort_order = explode( '|', $sort_order );
										if ( ! empty( $sort_order ) ) {
											foreach ( $sort_order as $s ) {
												if ( ! isset( $sort_order_data[ $s ] ) ) {
													continue;
												}
												?>
												<span class="woostify-multi-select-id" data-id="<?php echo esc_attr( $s ); ?>">
													<?php echo esc_html( $sort_order_data[ $s ] ); ?>
													<i class="woostify-multi-remove-id dashicons dashicons-no-alt"></i>
												</span>
												<?php
											}
										}
										?>
									</div>

									<input data-search="immediate" type="text" class="woostify-multi-select-search" placeholder="<?php esc_attr_e( 'Please enter 1 or more characters', 'woostify-pro' ); ?>">
								</div>

								<?php // Dropdown select. ?>
								<div class="woostify-multi-select-dropdown">
									<?php
									if ( ! empty( $sort_order_data ) ) {
										foreach ( $sort_order_data as $k => $v ) {
											$class = in_array( $k, $sort_order, true ) ? 'woostify-multi-select-id disabled' : 'woostify-multi-select-id';
											?>
											<span class="<?php echo esc_attr( $class ); ?>" data-id="<?php echo esc_attr( $k ); ?>">
												<?php echo esc_html( $v ); ?>
											</span>
											<?php
										}
									}
									?>
								</div>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Save metabox
		 *
		 * @param int    $post_id The post ID.
		 * @param object $post    The post.
		 */
		public function save_meta_box( $post_id, $post ) {
			$nonce = isset( $_POST['woostify_product_filter_nonce_value'] ) ? sanitize_text_field( wp_unslash( $_POST['woostify_product_filter_nonce_value'] ) ) : false;
			if (
				wp_is_post_revision( $post_id ) ||
				! current_user_can( 'edit_post', $post_id ) ||
				'product_filter' !== $post->post_type ||
				! $nonce ||
				! wp_verify_nonce( $nonce, 'woostify_product_filter_metabox_nonce' )
			) {
				return;
			}

			foreach ( $_POST as $key => $value ) {
				$save = false;
				switch ( $key ) {
					case 'woostify_product_filter_check_range_min':
					case 'woostify_product_filter_check_range_max':
						$save = array_map( 'sanitize_text_field', wp_unslash( $value ) );
						break;
					case 'woostify_product_filter_term_condition_field':
						$save = wp_kses_post( wp_unslash( $value ) );
						break;
					default:
						$save = sanitize_text_field( wp_unslash( $value ) );
						break;
				}

				update_post_meta( $post_id, $key, $save );
			}

			// Checkbox value == '0' not isset on $_POST, set to '0'.
			if ( ! isset( $_POST['woostify_product_filter_term_hierarchical'] ) ) {
				update_post_meta( $post_id, 'woostify_product_filter_term_hierarchical', '0' );
			}
			if ( ! isset( $_POST['woostify_product_filter_term_hierarchical_expand'] ) ) {
				update_post_meta( $post_id, 'woostify_product_filter_term_hierarchical_expand', '0' );
			}
			if ( ! isset( $_POST['woostify_product_filter_quick_search'] ) ) {
				update_post_meta( $post_id, 'woostify_product_filter_quick_search', '0' );
			}
		}

		/**
		 * Add filter wrap class
		 *
		 * @param  string $class The classname.
		 */
		public function add_filter_wrap_class( $class ) {
			return 'site-main w-result-filter';
		}

		/**
		 * Add filter key remove
		 */
		public function add_filter_key_remove() {
			if ( class_exists( 'Woostify_Woo_Builder' ) ) {
				$woo_builder = \Woostify_Woo_Builder::init();

				if ( $woo_builder->product_page_woobuilder( 'single' ) || $woo_builder->shop_archive_woobuilder() ) {
					return;
				}
			}
			?>

			<div class="w-filter-key"></div>
			<?php
		}

		/**
		 * Search product by sku, title, term name
		 *
		 * @param string $value The keyword value.
		 */
		public function search_by_text( $value ) {
			global $wpdb;
			$table_name = $this->table_name();
			$value      = sanitize_text_field( esc_html( $value ) );

			$sql    = "SELECT DISTINCT product_id FROM $table_name WHERE sku LIKE '%$value%' OR title LIKE '%$value%' OR term_name LIKE '%$value%'";
			$output = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore

			if ( empty( $output ) ) {
				return array();
			}

			return wp_list_pluck( $output, 'product_id', null );
		}

		/**
		 * Detect data empty or not
		 *
		 * @param  array  $data The data value.
		 * @param  string $s    The search param.
		 */
		public function detect_empty( $data = array(), $s = '' ) {
			$empty = true;

			foreach ( $data as $k => $v ) {
				if ( ! empty( $v ) ) {
					$empty = false;
					break;
				}
			}

			return empty( $s ) ? $empty : true;
		}

		/**
		 * Column head
		 *
		 * @param      array $defaults  The defaults.
		 */
		public function add_column_head( $defaults ) {
			$order = array();
			$title = 'title';
			foreach ( $defaults as $key => $value ) {
				$order[ $key ] = $value;

				if ( $key === $title ) {
					$order['product_filter_type']      = __( 'Type', 'woostify-pro' );
					$order['product_filter_data']      = __( 'Data Source', 'woostify-pro' );
					$order['product_filter_shortcode'] = __( 'Shortcode', 'woostify-pro' );
				}
			}

			return $order;
		}

		/**
		 * Column content
		 *
		 * @param      string $column_name  The column name.
		 * @param      int    $post_id      The post id.
		 */
		public function add_column_content( $column_name, $post_id ) {
			$type  = get_post_meta( $post_id, 'woostify_product_filter_type', true );
			$data  = get_post_meta( $post_id, 'woostify_product_filter_data', true );
			$range = get_post_meta( $post_id, 'woostify_product_filter_range_slider_query', true );

			switch ( $column_name ) {
				case 'product_filter_type':
					$type = $type ? str_replace( '-', ' ', $type ) : '-';
					?>
					<span><?php echo esc_html( ucfirst( $type ) ); ?></span>
					<?php
					break;
				case 'product_filter_data':
					if ( in_array( $type, array( 'rating', 'sort-order', 'search', 'date-range' ), true ) ) {
						echo '-';
						return;
					}

					$title = '-';
					$taxonomys = get_object_taxonomies( array(
						'product',
					) );

					if( in_array( $data, $taxonomys ) ){
						$taxonomy = get_taxonomy( $data );	
						$title = __( $taxonomy->label, 'woostify-pro' );
					}

					if( is_numeric( $data ) ){
						$product_attrs = wc_get_attribute( $data );
						if ( ! empty( $product_attrs ) && ! is_wp_error( $product_attrs ) ) {
							$title = sprintf( /* translators: Product attribute label */ __( 'Product attribute: %s', 'woostify-pro' ), $product_attrs->name );
						}
					}

					// For range_slider filter.
					if ( 'range-slider' === $type && $range ) {
						$title = str_replace( '-', ' ', $range );
						$title = ucfirst( trim( $title ) );
					}
					?>
					<span><?php echo esc_html( $title ); ?></span>
					<?php
					break;
				case 'product_filter_shortcode':
					?>
					<span class="w-filter-shortcode"><code>[woostify_product_filter id=<?php echo esc_attr( $post_id ); ?>]</code></span>
					<?php
					break;
			}
		}

		/**
		 * Admin scripts
		 */
		public function admin_enqueue_assets() {
			$screen            = get_current_screen();
			$is_edit_filter    = isset( $screen->post_type ) && 'product_filter' === $screen->post_type;
			$is_filter_setting = isset( $screen->id ) && false !== strpos( $screen->id, 'page_smart-product-filter-settings' );

			if ( $is_edit_filter || $is_filter_setting ) {
				$item_node  = '<div class="w-filter-item-pack w-filter-has-flex-item">';
				$item_node .= '<span class="w-filter-range-item-remove dashicons dashicons-no-alt"></span>';
				$item_node .= '<div class="w-filter-field">';
				$item_node .= '<span class="w-filter-label">' . esc_html__( 'Min value', 'woostify-pro' ) . '</span>';
				$item_node .= '<input class="w-filter-required" required type="number" name="woostify_product_filter_check_range_min[]" value="0">';
				$item_node .= '</div>';
				$item_node .= '<div class="w-filter-field">';
				$item_node .= '<span class="w-filter-label">' . esc_html__( 'Max value', 'woostify-pro' ) . '</span>';
				$item_node .= '<input class="w-filter-required" required type="number" name="woostify_product_filter_check_range_max[]" value="20">';
				$item_node .= '</div>';
				$item_node .= '</div>';

				// All style.
				wp_enqueue_style(
					'woostify-product-filter',
					WOOSTIFY_PRO_MODULES_URI . 'woocommerce/product-filter/assets/css/backend.css',
					array(),
					WOOSTIFY_PRO_VERSION
				);

				// Sortable.
				wp_register_script(
					'sortable',
					WOOSTIFY_PRO_MODULES_URI . 'woocommerce/product-filter/assets/js/lib/sortable' . woostify_suffix() . '.js',
					array(),
					WOOSTIFY_PRO_VERSION,
					true
				);

				// Backend script.
				wp_enqueue_script(
					'woostify-product-filter',
					WOOSTIFY_PRO_MODULES_URI . 'woocommerce/product-filter/assets/js/backend' . woostify_suffix() . '.js',
					array( 'sortable' ),
					WOOSTIFY_PRO_VERSION,
					true
				);

				// Data.
				$data = array(
					'item_node'           => $item_node,
					'ajax_nonce'          => wp_create_nonce( 'woostify_index_filter' ),
					'ajax_sortable_nonce' => wp_create_nonce( 'woostify_smart_filter_nonce' ),
					'index_text'          => esc_html__( 'Index', 'woostify-pro' ),
					'indexing_text'       => esc_html__( 'Indexing', 'woostify-pro' ),
					'indexed_text'        => esc_html__( 'Indexed', 'woostify-pro' ),
					'indexed_success'     => esc_html__( 'Indexed Successfully', 'woostify-pro' ),
				);

				wp_localize_script(
					'woostify-product-filter',
					'woostify_product_filter',
					$data
				);
			}
		}

		/**
		 * Enqueue styles and scripts.
		 */
		public function enqueue_assets() {
			// General style.
			wp_enqueue_style(
				'woostify-product-filter',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/product-filter/assets/css/product-filter.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);

			wp_add_inline_style( 'woostify-product-filter', $this->get_css() );

			// Date picker lib.
			wp_enqueue_style(
				'tiny-datepicker',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/product-filter/assets/css/tiny-date-picker.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);

			wp_enqueue_script(
				'tiny-datepicker',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/product-filter/assets/js/lib/tiny-date-picker' . woostify_suffix() . '.js',
				array(),
				WOOSTIFY_PRO_VERSION,
				true
			);

			$days = array(
				esc_html_x( 'Sun', 'Day of the week', 'woostify-pro' ),
				esc_html_x( 'Mon', 'Day of the week', 'woostify-pro' ),
				esc_html_x( 'Tue', 'Day of the week', 'woostify-pro' ),
				esc_html_x( 'Web', 'Day of the week', 'woostify-pro' ),
				esc_html_x( 'Thu', 'Day of the week', 'woostify-pro' ),
				esc_html_x( 'Fri', 'Day of the week', 'woostify-pro' ),
				esc_html_x( 'Sat', 'Day of the week', 'woostify-pro' ),
			);

			$months = array(
				esc_html_x( 'January', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'February', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'March', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'April', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'May', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'June', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'July', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'August', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'September', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'October', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'November', 'Month of the year', 'woostify-pro' ),
				esc_html_x( 'December', 'Month of the year', 'woostify-pro' ),
			);

			wp_localize_script(
				'tiny-datepicker',
				'woostify_datepicker_data',
				array(
					'today'  => __( 'Today', 'woostify-pro' ),
					'clear'  => __( 'Clear', 'woostify-pro' ),
					'close'  => __( 'Close', 'woostify-pro' ),
					'days'   => $days,
					'months' => $months,
				)
			);

			// Range slider lib.
			wp_enqueue_style(
				'nouislider',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/product-filter/assets/css/nouislider.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);

			wp_enqueue_script(
				'nouislider',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/product-filter/assets/js/lib/nouislider' . woostify_suffix() . '.js',
				array(),
				WOOSTIFY_PRO_VERSION,
				true
			);

			// General filter script.
			wp_enqueue_script(
				'woostify-product-filter',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/product-filter/assets/js/product-filter' . woostify_suffix() . '.js',
				array(),
				WOOSTIFY_PRO_VERSION,
				true
			);

			$data = array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'ajax_nonce'    => wp_create_nonce( 'woostify_product_filter' ),
				'term_id'       => isset( get_queried_object()->term_id ) ? get_queried_object()->term_id : false,
				'taxonomy'      => isset( get_queried_object()->taxonomy ) ? get_queried_object()->taxonomy : false,
				'filters_url'   => $this->get_filters_url(),
				'active_params' => $this->get_active_params(),
				'remove_key'    => $this->get_active_data(),
				'expand_icon'   => '[+]',
				'collapse_icon' => '[-]',
			);

			wp_localize_script(
				'woostify-product-filter',
				'woostify_product_filter',
				apply_filters( 'woostify_product_filter_data', $data )
			);
		}

		/**
		 * Get css
		 */
		public function get_css() {
			$options = $this->get_options();

			$style['.w-product-filter .widget-title'] = array(
				'color'     => $options['title_color'],
				'font-size' => $options['title_size'] ? $options['title_size'] . 'px' : '',
			);

			$style['.w-product-filter-inner'] = array(
				'font-size' => $options['general_text_size'] ? $options['general_text_size'] . 'px' : '',
				'color'     => $options['general_text_color'],
			);

			if ( '1' !== $options['enable_remove_filter_button'] ) {
				$style['.w-result-filter .w-filter-key']['display'] = 'none';
			}

			if ( '1' === $options['scroll_enabled'] ) {
				$style['.w-product-filter[data-type="checkbox"] .w-product-filter-inner .w-product-filter-content-wrap, .w-product-filter[data-type="check-range"] .w-product-filter-inner .w-product-filter-content-wrap, .w-product-filter[data-type="radio"] .w-product-filter-inner .w-product-filter-content-wrap']['max-height'] = $options['scroll_height'] . 'px';
				$style['.w-product-filter[data-type="checkbox"] .w-product-filter-inner .w-product-filter-content-wrap, .w-product-filter[data-type="check-range"] .w-product-filter-inner .w-product-filter-content-wrap, .w-product-filter[data-type="radio"] .w-product-filter-inner .w-product-filter-content-wrap']['overflow-y'] = 'auto';
			}

			$style['.w-product-filter-inner button'] = array(
				'color'            => $options['general_button_color'],
				'background-color' => $options['general_button_bg_color'],
				'width'            => $options['general_button_width'] ? $options['general_button_width'] . $options['general_button_width_unit'] : '',
				'height'           => $options['general_button_height'] ? $options['general_button_height'] . 'px' : '',
				'border-radius'    => $options['general_button_radius'] ? $options['general_button_radius'] . 'px' : '',
			);

			if ( 'none' !== $options['general_button_border_style'] ) {
				$style['.w-product-filter-inner button']['border-style'] = $options['general_button_border_style'];
				$style['.w-product-filter-inner button']['border-width'] = $options['general_button_border_width'] . 'px';
				$style['.w-product-filter-inner button']['border-color'] = $options['general_button_border_color'];
			}

			$style['.w-filter-key .w-filter-key-remove'] = array(
				'color'            => $options['active_filter_color'],
				'background-color' => $options['active_filter_bg'],
				'border-radius'    => $options['active_filter_border_radius'] ? $options['active_filter_border_radius'] . 'px' : '',
			);

			if ( 'none' !== $options['active_filter_border_style'] ) {
				$style['.w-filter-key .w-filter-key-remove']['border-style'] = $options['active_filter_border_style'];
				$style['.w-filter-key .w-filter-key-remove']['border-width'] = $options['active_filter_border_width'] . 'px';
				$style['.w-filter-key .w-filter-key-remove']['border-color'] = $options['active_filter_border_color'];
			}

			// Item count.
			$style['.w-filter-item-count'] = array(
				'color' => $options['general_item_count_color'],
			);

			// checkbox.
			$style['.w-filter-item input[type="checkbox"]'] = array(
				'background-color' => $options['checkbox_background'],
			);

			// radio.
			$style['.w-filter-item input[type="radio"]']              = array(
				'background-color' => $options['radio_background'],
			);
			$style['.w-product-filter [type="radio"]:checked:before'] = array(
				'background' => $options['radio_icon_color'],
			);

			// input.
			$style['.w-product-filter-inner input:not([type=checkbox]):not([type=radio])'] = array(
				'color'      => $options['input_text_color'],
				'background' => $options['input_background_color'],
			);
			if ( 'none' !== $options['input_border_style'] ) {
				$style['.w-product-filter-inner input:not([type=checkbox]):not([type=radio])']['border-style'] = $options['input_border_style'];
				$style['.w-product-filter-inner input:not([type=checkbox]):not([type=radio])']['border-width'] = $options['input_border_width'] . 'px';
				$style['.w-product-filter-inner input:not([type=checkbox]):not([type=radio])']['border-color'] = $options['input_border_color'];
			}

			// Range slider.
			$style['.w-filter-range-slider .noUi-handle, .w-filter-range-slider .noUi-connect'] = array(
				'background-color' => $options['rs_primary_color'],
			);

			$style['.w-filter-range-slider .noUi-active, .w-filter-range-slider .noUi-connects'] = array(
				'background-color' => $options['rs_secondary_color'],
			);

			if ( 'squares' === $options['rs_handle'] ) {
				$style['.w-filter-range-slider .noUi-handle'] = array(
					'border-radius' => '2px',
				);
			}

			// Visual.
			if ( 'none' !== $options['visual_item_border_style'] ) {
				$style['.w-product-filter[data-type="visual"] .w-filter-item'] = array(
					'border-style' => $options['visual_item_border_style'],
					'border-width' => $options['visual_item_border_width'] . 'px',
					'border-color' => $options['visual_item_border_color'],
				);
			}
			if ( 'none' !== $options['visual_activated_item_border_style'] ) {
				$style['.w-product-filter[data-type="visual"] .w-filter-item.selected']        = array(
					'border-color' => 'transparent',
				);
				$style['.w-product-filter[data-type="visual"] .w-filter-item.selected:before'] = array(
					'border-style' => $options['visual_activated_item_border_style'],
					'border-width' => $options['visual_activated_item_border_width'] . 'px',
					'border-color' => $options['visual_activated_item_border_color'],
				);
			}
			$style['.w-product-filter[data-type="visual"] .w-filter-item.w-filter-swatch-color'] = array(
				'width'  => $options['visual_color_width'] . 'px',
				'height' => $options['visual_color_height'] . 'px',
			);

			$style['.w-product-filter[data-type="visual"] .w-filter-item.w-filter-swatch-image'] = array();
			if ( $options['visual_image_width'] ) {
				$style['.w-product-filter[data-type="visual"] .w-filter-item.w-filter-swatch-image']['width']     = $options['visual_image_width'] . $options['visual_image_width_unit'];
				$style['.w-product-filter[data-type="visual"] .w-filter-item.w-filter-swatch-image']['height']    = 'auto';
				$style['.w-product-filter[data-type="visual"] .w-filter-item.w-filter-swatch-image img']['width'] = '100%';
			}

			$style['.w-product-filter[data-type="visual"] .w-filter-item.w-filter-swatch-label']                          = array(
				'background' => $options['visual_label_bg_color'],
			);
			$style['.w-product-filter[data-type="visual"] .w-filter-item.w-filter-swatch-label.selected']                 = array(
				'background' => $options['visual_activated_label_bg_color'],
			);
			$style['.w-product-filter[data-type="visual"] .w-filter-item.w-filter-swatch-label .w-visual-label']          = array(
				'color' => $options['visual_label_color'],
			);
			$style['.w-product-filter[data-type="visual"] .w-filter-item.w-filter-swatch-label.selected .w-visual-label'] = array(
				'color' => $options['visual_activated_label_color'],
			);

			// End style.

			$parse_css = '';
			foreach ( $style as $selector => $properties ) {
				if ( empty( $properties ) ) {
					continue;
				}

				$temp_parse_css   = $selector . '{';
				$properties_added = 0;

				foreach ( $properties as $property => $value ) {
					if ( in_array( $value, array( '', 'px', 'em', '%' ), true ) || false === $value ) {
						continue;
					}

					$properties_added++;
					$temp_parse_css .= $property . ':' . $value . ';';
				}

				$temp_parse_css .= '}';

				if ( $properties_added > 0 ) {
					$parse_css .= $temp_parse_css;
				}
			}

			return $parse_css;
		}

		/**
		 * Get database info
		 */
		public function get_db_info() {
			$isset_table = Woostify_Filter_Render::init()->isset_table();
			if ( ! $isset_table ) {
				return;
			}

			// Get total product index.
			global $wpdb;
			$table_name = $this->table_name();
			$sql        = "SELECT DISTINCT product_id FROM $table_name WHERE product_id <> 0 and variation_id=0";
			$get_count  = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore

			// Data.
			$res['time']  = get_option( 'woostify_product_filter_last_indexed' );
			$res['total'] = empty( $get_count ) ? 0 : count( $get_count );

			if ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
				$res['timer']   = timer_stop();
				$res['request'] = get_num_queries();
				$res['mem']     = ceil( memory_get_usage() / 1024 / 1024 );
			}
			return $res;
		}

		/**
		 * Index database
		 */
		public function woostify_index_filter() {
			check_ajax_referer( 'woostify_index_filter', 'ajax_nonce' );

			// Index database.
			$this->index();

			return wp_send_json_success( $this->get_db_info() );
		}

		/**
		 * Render template
		 *
		 * @param array $template_args The template args.
		 */
		public function render_template( $template_args ) {
			if ( empty( $template_args['list_filter'] ) ) {
				return;
			}

			$data        = $template_args['data'];
			$product_ids = $template_args['product_ids'];
			$render      = Woostify_Filter_Render::init();
			$template    = array();
			$i           = 0;

			// Check empty query result.
			$empty = empty( $product_ids ) ? true : false;

			// If data value empty, remove product ids.
			if ( $this->detect_empty( $data, $template_args['s'] ) ) {
				$product_ids = array();
			}

			foreach ( $template_args['list_filter'] as $filter_id => $filter_type ) {
				$i++;
				$selected_value = array_key_exists( $filter_id, $data ) ? $data[ $filter_id ] : false;
				ob_start();
				$render->render_filter( $filter_id, $product_ids, $selected_value, $data, $empty, $template_args['is_tax'], $template_args['taxonomy'] );
				$template[ $i ]['id']       = $filter_id;
				$template[ $i ]['type']     = get_post_meta( $filter_id, 'woostify_product_filter_type', true );
				$template[ $i ]['value']    = $selected_value;
				$template[ $i ]['template'] = ob_get_clean();
			}

			return $template;
		}

		/**
		 * Product filter
		 */
		public function woostify_product_filter() {
			$start = microtime( true );
			check_ajax_referer( 'woostify_product_filter', 'ajax_nonce' );

			global $wpdb, $wp_query;

			$default_orderby = get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );

			$per_page     = empty( $_POST['per_page'] ) ? woostify_products_per_page() : intval( wp_unslash( $_POST['per_page'] ) );
			$paged        = isset( $_POST['paged'] ) ? intval( wp_unslash( $_POST['paged'] ) ) : ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 );
			$term_id      = isset( $_POST['term_id'] ) ? intval( wp_unslash( $_POST['term_id'] ) ) : false;
			$taxonomy     = isset( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : false;
			$search_param = isset( $_POST['search_param'] ) ? sanitize_text_field( wp_unslash( $_POST['search_param'] ) ) : '';
			$list_filter  = isset( $_POST['list_filter'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['list_filter'] ) ), true ) : array();
			$data         = isset( $_POST['data'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['data'] ) ), true ) : array();
			$no_posts     = '<span class="woocommerce-info">' . esc_html__( 'No posts found!', 'woostify-pro' ) . '</span>';
			$remove_icon  = woostify_fetch_svg_icon( 'close' );
			$orderby      = ! empty( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : $default_orderby;

			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'paged'          => $paged,
				'fields'         => 'ids',
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			);

			if ( 'menu_order' !== $orderby ) {
				$args['orderby'] = 'meta_value_num';
				$args['order']   = 'DESC';

				switch ( $orderby ) {
					case 'rating':
						$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore
						break;
					case 'popularity':
						$args['meta_key'] = 'total_sales'; // phpcs:ignore
						break;
					case 'date':
						$args['orderby'] = 'date';
						break;
					case 'price':
						$args['meta_key'] = '_price'; // phpcs:ignore
						$args['order']    = 'ASC';
						break;
					case 'price-desc':
						$args['meta_key'] = '_price'; // phpcs:ignore
						break;
				}
			}

			if ( ! empty( $search_param ) ) {
				$args['s'] = $search_param;
			}

			// Parse daa.
			$parse_args = $this->filter_parse_args( $data );

			if ( ! $parse_args ) {
				$parse_args         = array();
				$parse_args['args'] = array();
			}

			$args = wp_parse_args( $parse_args['args'], $args );

			// Remove out of stock variation.
			$outstock = get_option( 'woocommerce_hide_out_of_stock_items' );
			if ( isset( $parse_args['attr_args'] ) ) {
				$outstock_variation = $this->get_product_ids_by_attributes( $parse_args['attr_args'] );
				if ( ! empty( $outstock_variation ) && 'yes' === $outstock ) {
					if ( empty( $args['post__in'] ) ) {
						$args['post__not_in'] = $outstock_variation;
					} else {
						$args['post__in'] = array_diff( $args['post__in'], $outstock_variation );
					}
				}
			}

			// Product visibility terms.
			$visibility_terms = wc_get_product_visibility_term_ids();
			$hidden_term      = $visibility_terms['exclude-from-catalog'];
			$term_ids         = array();

			// Ignore hidden product.
			if ( ! empty( $hidden_term ) ) {
				array_push( $term_ids, $hidden_term );
			}

			// Exclude out of stock.
			if ( 'yes' === $outstock ) {
				$outstock_term = $visibility_terms['outofstock'];
				array_push( $term_ids, $outstock_term );
			}

			// Finals.
			if ( ! empty( $term_ids ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $term_ids,
					'operator' => 'NOT IN',
				);
			}

			// Add clear all filter.
			$filter_key = '';
			if ( isset( $parse_args['key'] ) ) {
				$filter_key = $parse_args['key'];
				if ( $filter_key ) {
					$remove_icon = apply_filters( 'woostify_product_filter_remove_icon', '<svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>' );
					$filter_key  = '<span class="w-filter-key-remove" data-type="all">' . __( 'Clear', 'woostify-pro' ) . ' <span class="w-filter-key-remove-icon">' . $remove_icon . '</span></span>' . $filter_key;
				}
			}

			// Result for archive products page.
			$is_tax = false;
			if ( $taxonomy && $term_id ) {
				$is_tax = $term_id;

				$args['tax_query'][] = array(
					'taxonomy' => $taxonomy,
					'terms'    => $term_id,
				);
			}

			$products = new WP_Query( $args );
			$wp_query = $products; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			// Get found product ids.
			$args['posts_per_page'] = -1;
			$found_product_ids      = get_posts( $args );
			$product_ids            = empty( $found_product_ids ) ? array() : $found_product_ids;

			// Render template.
			$template_args   = array(
				'list_filter' => $list_filter,
				'data'        => $data,
				'product_ids' => $product_ids,
				'is_tax'      => $is_tax,
				's'           => $search_param,
				'taxonomy'    => $taxonomy,
			);
			$res['template'] = $this->render_template( $template_args );

			if ( $products->have_posts() ) {
				// Find current.
				$current = max( 1, $paged );

				// Pagination.
				ob_start();
				echo paginate_links( // phpcs:ignore
					array(
						'base'      => esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) ),
						'format'    => '',
						'add_args'  => false,
						'current'   => $current,
						'total'     => ceil( $products->found_posts / $per_page ),
						'prev_text' => esc_html__( 'Prev', 'woostify-pro' ),
						'next_text' => esc_html__( 'Next', 'woostify-pro' ),
						'type'      => 'list',
						'end_size'  => 3,
						'mid_size'  => 3,
					)
				);
				$res['pagination'] = ob_get_clean();

				// Result count.
				ob_start();
				$result_args = array(
					'total'    => $products->found_posts,
					'per_page' => $per_page,
					'current'  => $current,
				);

				wc_get_template( 'loop/result-count.php', $result_args );
				$res['result_count'] = wp_kses( ob_get_clean(), array() );

				// Order loop
				// Refrence function woocommerce_catalog_ordering.
				ob_start();
				$show_default_orderby    = 'menu_order' === apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby', 'menu_order' ) );
				$catalog_orderby_options = apply_filters(
					'woocommerce_catalog_orderby',
					array(
						'menu_order' => __( 'Default sorting', 'woocommerce' ),
						'popularity' => __( 'Sort by popularity', 'woocommerce' ),
						'rating'     => __( 'Sort by average rating', 'woocommerce' ),
						'date'       => __( 'Sort by latest', 'woocommerce' ),
						'price'      => __( 'Sort by price: low to high', 'woocommerce' ),
						'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ),
					)
				);

				if ( wc_get_loop_prop( 'is_search' ) ) {
					$catalog_orderby_options = array_merge( array( 'relevance' => __( 'Relevance', 'woocommerce' ) ), $catalog_orderby_options );

					unset( $catalog_orderby_options['menu_order'] );
				}

				if ( ! $show_default_orderby ) {
					unset( $catalog_orderby_options['menu_order'] );
				}

				if ( ! wc_review_ratings_enabled() ) {
					unset( $catalog_orderby_options['rating'] );
				}

				if ( ! array_key_exists( $orderby, $catalog_orderby_options ) ) {
					$orderby = current( array_keys( $catalog_orderby_options ) );
				}

				wc_get_template(
					'loop/orderby.php',
					array(
						'catalog_orderby_options' => $catalog_orderby_options,
						'orderby'                 => $orderby,
						'show_default_orderby'    => $show_default_orderby,
					)
				);
				$res['ordering'] = ob_get_clean();
				// End Order loop.

				// Content.
				ob_start();
				if ( $products->have_posts() ) {
					while ( $products->have_posts() ) {
						$products->the_post();

						wc_get_template_part( 'content', 'product' );
					}
				}

				$res['content'] = ob_get_clean();

				wp_reset_postdata();
			} else {
				ob_start();
				echo wp_kses_post( $no_posts );
				$res['content']      = ob_get_clean();
				$res['result_count'] = wp_kses( $no_posts, array() );
			}

			// Response.
			$res['filtered'] = $filter_key;
			$res['time']     = round( microtime( true ) - $start, 4 );

			// Response.
			wp_send_json_success( $res );
		}

		/**
		 * Load horizontal layout shortcode
		 */
		public function load_horizontal_shortcode() {
			$options      = $this->get_options();
			$body_classes = get_body_class();
			if ( ! is_active_sidebar( 'sidebar-shop' ) || 'horizontal' !== $options['layout'] || in_array( 'woobuilder-active', $body_classes, true ) ) {
				return;
			}
			?>

			<div class="filter-area filter-horizontal">
				<?php dynamic_sidebar( 'sidebar-shop' ); ?>
			</div>
			<?php
		}

		/**
		 * Adds custom classes to the array of body classes.
		 *
		 * @param array $classes Classes for the body element.
		 *
		 * @return array
		 */
		public function body_classes( $classes ) {
			$options = $this->get_options();
			if ( 'horizontal' === $options['layout'] ) {
				$classes[] = 'w-pro-smart-filter-layout-horizontal';
			}
			return array_filter( $classes );
		}

		/**
		 * Include sidebar to shop no sidebar layout
		 */
		public function get_sidebar() {
			// All theme options.
			$options        = woostify_options( false );
			$filter_options = $this->get_options();

			// Metabox options.
			$metabox_sidebar = woostify_get_metabox( false, 'site-sidebar' );

			// Customize options.
			$sidebar_shop = 'default' !== $metabox_sidebar ? $metabox_sidebar : $options['sidebar_shop'];

			if ( ( 'horizontal' === $filter_options['layout'] && woostify_is_product_archive() && 'full' === $sidebar_shop ) || ( ! empty( $_POST['action'] ) && $_POST['action'] === 'woostify_product_filter' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.PHP.YodaConditions.NotYoda
				get_sidebar( 'shop' );
			}
		}

		/**
		 * Table stock name
		 */
		public function table_stock_name() {
			global $table_prefix;

			return "{$table_prefix}woostify_filter_stock_index";
		}

		/**
		 * Insert Stock database
		 *
		 * @param array $args The args.
		 */
		public function insert_stock( $args = array() ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			global $wpdb;
			$table_name = $this->table_stock_name();

			// Create table if not exists.
			$int       = 'BIGINT';
			$create_db = "CREATE TABLE IF NOT EXISTS $table_name (
				id BIGINT unsigned not null auto_increment,
				product_id INT unsigned,
				stock_status VARCHAR(100),
				onsale INT unsigned default '0',
				PRIMARY KEY (id)
			) DEFAULT CHARSET=utf8";
			dbDelta( $create_db );

			// Default data.
			$default = array(
				'product_id'   => 0,
				'stock_status' => 'instock',
				'onsale'       => 0,
			);

			$params = wp_parse_args( $args, $default );

			// @codingStandardsIgnoreStart
			// Insert to DB.
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO $table_name (product_id, stock_status) VALUES (%d, %s, %d )",
					$params['product_id'],
					$params['stock_status'],
					$params['onsale'],
				)
			);
			// @codingStandardsIgnoreEnd
		}

		/**
		 * Get products stock
		 *
		 * @param  string $stock_status The stock status.
		 */
		public function get_products_stock( $stock_status = 'outofstock' ) {
			$args = array(
				'post_type'           => 'product',
				'ignore_sticky_posts' => 1,
				'post_status'         => 'publish',
				'meta_query'          => array( // phpcs:ignore
					array(
						'key'   => '_stock_status',
						'value' => $stock_status,
					),
				),
			);

			$products = new \WP_Query( $args );

			if ( ! $products->have_posts() ) {
				return false;
			}

			return $products;
		}

		/**
		 * Index stock
		 */
		public function index_stock() {
			$product_outofstock = $this->get_products_stock();
			$product_backorder  = $this->get_products_stock( 'onbackorder' );

			while ( $product_outofstock->have_posts() ) {
				$product_outofstock->the_post();
				$args = array(
					'product_id'   => get_the_ID(),
					'stock_status' => 'outofstock',
					'onsale'       => 0,
				);

				$this->insert_stock( $args );
			}
			while ( $product_backorder->have_posts() ) {
				$product_backorder->the_post();
				$args = array(
					'product_id'   => get_the_ID(),
					'stock_status' => 'onbackorder',
					'onsale'       => 0,
				);

				$this->insert_stock( $args );
			}

		}
	}

	Woostify_Product_Filter::init();
}