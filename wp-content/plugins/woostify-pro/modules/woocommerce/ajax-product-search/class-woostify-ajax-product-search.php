<?php
/**
 * Woostify Ajax Product Search Class
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'Woostify_Ajax_Product_Search' ) ) :

	/**
	 * Woostify Ajax Product Search Class
	 */
	class Woostify_Ajax_Product_Search {

		/**
		 * Instance Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 * Total Product Reindex
		 *
		 * @var total_product
		 */
		protected $total_product;

		/**
		 * Last update time Reindex
		 *
		 * @var update_time
		 */
		protected $update_time;

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
			$this->define_constants();

			$woocommerce_helper = Woostify_Woocommerce_Helper::init();

			$this->includes();

			add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
			add_filter( 'woostify_customizer_css', array( $this, 'inline_styles' ), 35 );
			add_filter( 'woostify_options_admin_submenu_label', array( $this, 'woostify_options_admin_submenu_label' ) );

			// For mobile search form.
			add_action( 'woostify_site_search_end', array( $this, 'add_search_results' ) );

			// For dialog search form.
			add_action( 'woostify_dialog_search_content_end', array( $this, 'add_search_results' ) );

			// Save settings.
			add_action( 'wp_ajax_woostify_save_ajax_search_product_options', array( $woocommerce_helper, 'save_options' ) );

			// Ajax for front end.
			add_action( 'wp_ajax_ajax_product_search', array( $this, 'ajax_product_search' ) );
			add_action( 'wp_ajax_nopriv_ajax_product_search', array( $this, 'ajax_product_search' ) );

			// Add Setting url.
			add_action( 'admin_menu', array( $this, 'add_setting_url' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			add_action( 'woostify-search', array( $this, 'ajax_product_search' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_style' ) );

			// Ajax reindex.
			add_action( 'wp_ajax_index_data', array( $this, 'index_data' ) );
			add_action( 'wp_ajax_nopriv_index_data', array( $this, 'index_data' ) );
			add_action( 'wp_ajax_index_data_next', array( $this, 'index_data_next' ) );
			add_action( 'wp_ajax_nopriv_index_data_next', array( $this, 'index_data_next' ) );
			// Ajax admin.
			add_action( 'wp_ajax_find_custom_field', array( $this, 'find_custom_field' ) );
			add_action( 'wp_ajax_nopriv_find_custom_field', array( $this, 'find_custom_field' ) );
			add_action( 'delete_post', array( $this, 'delete_product' ), 10 );
			add_action( 'wp_trash_post', array( $this, 'delete_product' ) );
			add_action( 'untrash_post', array( $this, 'untrash_post' ) );
			add_action( 'init', array( $this, 'session_user' ) );
			add_action( 'admin_notices', array( $this, 'admin_notice_index' ) );
			add_action( 'updated_post_meta', array( $this, 'product_meta_save' ), 10, 4 );
			add_action( 'post_updated', array( $this, 'update_table' ), 10, 3 );
			add_action( 'transition_post_status', array( $this, 'status_transitions' ), 10, 3 );
			add_action( 'edited_product_cat', array( $this, 'update_category' ), 10, 2 );
			add_action( 'edited_product_tag', array( $this, 'update_tag' ), 10, 2 );
			add_action( 'created_product_cat', array( $this, 'create_category' ), 10, 2 );
			add_action( 'created_product_tag', array( $this, 'create_tag' ), 10, 2 );
			add_action( 'save_post_product', array( $this, 'save_product' ), 20, 2 );
			add_action( 'update_option', array( $this, 'enable_archive_attribute' ), 10, 3 );

		}

		/**
		 *  Include function
		 */
		public function includes() {
			require_once WOOSTIFY_PRO_PATH . 'modules/woocommerce/ajax-product-search/includes/helper.php';
			require_once WOOSTIFY_PRO_PATH . 'modules/woocommerce/ajax-product-search/includes/class-query.php';
			require_once WOOSTIFY_PRO_PATH . 'modules/woocommerce/ajax-product-search/includes/class-woocommerce.php';
			require_once WOOSTIFY_PRO_PATH . 'modules/woocommerce/ajax-product-search/includes/class-products-render.php';
		}


		/**
		 *  Add admin Style
		 */
		public function load_admin_style() {
			wp_enqueue_script(
				'woostify_reindex',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/ajax-product-search/js/reindex' . woostify_suffix() . '.js',
				array( 'jquery', 'suggest' ),
				WOOSTIFY_PRO_VERSION,
				true
			);

			wp_enqueue_style(
				'woostify-search-admin',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/ajax-product-search/css/admin.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);
			$admin_vars = array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'woostify_nonce' ),
			);

			wp_localize_script(
				'woostify_reindex',
				'admin',
				$admin_vars
			);
		}

		/**
		 * Define constant
		 */
		public function define_constants() {
			if ( ! defined( 'WOOSTIFY_AJAX_PRODUCT_SEARCH' ) ) {
				define( 'WOOSTIFY_AJAX_PRODUCT_SEARCH', WOOSTIFY_PRO_VERSION );
			}
		}

		/**
		 * Adds search results.
		 */
		public function add_search_results() {
			$total_product = (int) get_option( 'woostify_ajax_search_product_total', '-1' );
			?>
					<div class="search-results-wrapper">
						<div class="ajax-search-results"></div>
						<?php if ( -1 != $total_product ) : //phpcs:ignore ?>
							<div class="total-result">
								<div class="total-result-wrapper">
								</div>
							</div>
						<?php endif ?>
					</div>
			<?php
		}

		/**
		 * Sets up.
		 */
		public function scripts() {
			$addon_options = $this->get_options();

			// Style.
			wp_enqueue_style(
				'woostify-ajax-product-search',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/ajax-product-search/css/style.css',
				array(),
				WOOSTIFY_PRO_VERSION
			);

			/**
			 * Script
			 */
			wp_enqueue_script(
				'woostify-ajax-product-search',
				WOOSTIFY_PRO_MODULES_URI . 'woocommerce/ajax-product-search/js/script' . woostify_suffix() . '.js',
				array( 'jquery' ),
				WOOSTIFY_PRO_VERSION,
				true
			);

			$data = array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'ajax_error'    => __( 'Sorry, something went wrong. Please refresh this page and try again!', 'woostify-pro' ),
				'ajax_nonce'    => wp_create_nonce( 'ajax_product_search' ),
				'url'           => WOOSTIFY_PRO_MODULES_URI . 'woocommerce/ajax-product-search/includes/search.php',
				'code_snippets' => false,
				'no_product'    => __( 'No products found!', 'woostify-pro' ),
				'products'      => __( 'Products', 'woostify-pro' ),
				'product'       => __( 'Product', 'woostify-pro' ),
				'categories'    => __( 'Categories', 'woostify-pro' ),
				'tags'          => __( 'Tags', 'woostify-pro' ),
				'attributes'    => __( 'Attributes', 'woostify-pro' ),
			);

			$term = get_terms( 'product_cat' );

			if ( class_exists( 'Code_Snippet' ) && $this->check_snippets_code_woocommerce() > 0 ) {
				$data['code_snippets'] = true;
			}

			if ( defined( 'ICL_SITEPRESS_VERSION' ) && defined( 'ICL_LANGUAGE_CODE' ) ) {
				$data['lang'] = ICL_LANGUAGE_CODE;
			}

			if ( '1' === $addon_options['filter'] && $term ) {
				$select  = '<div class="ajax-category-filter-box">';
				$select .= '<select class="ajax-product-search-category-filter">';
				$select .= '<option value="">' . esc_html__( 'All', 'woostify-pro' ) . '</option>';
				foreach ( $term as $k ) {
					$select .= '<option value="' . esc_attr( $k->term_id ) . '">' . esc_html( $k->name ) . '</option>';
				}
				$select .= '</select>';
				$select .= '</div>';

				$data['select'] = $select;
			}

			wp_localize_script(
				'woostify-ajax-product-search',
				'woostify_ajax_product_search_data',
				$data
			);
		}

		/**
		 * Add dynamic style to theme customize styles
		 *
		 * @param string $styles Customize styles.
		 *
		 * @return string
		 */
		public function inline_styles( $styles ) {
			$addon_options = $this->get_options();

			$styles .= '
			/* AJAX Product Search */
				.aps-highlight {
					color: ' . esc_attr( $addon_options['highlight_color'] ) . ';
				}
			';

			return $styles;
		}

		/**
		 * Update First submenu for Welcome screen.
		 */
		public function check_snippets_code_woocommerce() {
			global $wpdb;
			$table   = $wpdb->prefix . 'snippets';
			$sql     = "SELECT COUNT( 'id' ) FROM $table WHERE code LIKE '%get_price_html%' ";
			$results = $wpdb->get_var( $sql ); //phpcs:ignore
			return $results;
		}

		/**
		 * Update First submenu for Welcome screen.
		 */
		public function woostify_options_admin_submenu_label() {
			return true;
		}

		/**
		 * Highlight keyword
		 *
		 * @param      string $str     The string.
		 * @param      string $keyword The keyword.
		 *
		 * @return     string  Highlight string
		 */
		public function highlight_keyword( $str, $keyword ) {
			$str     = html_entity_decode( trim( $str ) );
			$keyword = wp_specialchars_decode( trim( $keyword ) );

			return str_ireplace( $keyword, '<span class="aps-highlight">' . $keyword . '</span>', $str );
		}

		/**
		 * Strip all ' ', '-', '_' character
		 *
		 * @param      string $str The string.
		 */
		public function strip_all_char( $str ) {
			$str = strtolower( $str );
			$str = str_replace( ' ', '', $str );
			$str = str_replace( '-', '', $str );
			$str = str_replace( '_', '', $str );

			return $str;
		}


		/**
		 * Get price default when use dynamic.
		 *
		 * @param (string) $string | title sub string.
		 * @param (string) $key | searck key item.
		 * @param (string) $title | product title.
		 */
		public function search_key( $string, $key, $title ) {
			$position     = stripos( $string, $key );
			$sub_key      = substr( $string, $position +1 ); //phpcs:ignore
			$pre_key      = substr( $string, 0, $position +1 ); //phpcs:ignore
			$count        = substr_count( strtolower( $string ), strtolower( $key ) );
			$positionNext = stripos( $pre_key, $key ); //phpcs:ignore
			$length       = strlen( $key );
			$text         = substr( $pre_key, $positionNext, $length ); //phpcs:ignore
			$name         = str_ireplace( $key, '-----' . $text . '+++++', $pre_key );
			if ( $count > 1 ) {
				$name .= $this->search_key( $sub_key, $key, $title );
			}
			if ( $count == 1 ) { //phpcs:ignore
				$name .= $sub_key;
			}

			return $name;
		}

		/**
		 * Get price default when use dynamic.
		 *
		 * @param (int) $string | product id.
		 * @param (int) $keyword | product id.
		 */
		public function highlight( $string, $keyword ) {
			$str     = html_entity_decode( trim( $string ) );
			$string  = $str;
			$keyword = wp_specialchars_decode( trim( $keyword ) );
			$args    = explode( ' ', $keyword );
			if ( $keyword == $string ) { //phpcs:ignore
				$name = '<span class="aps-highlight">' . $keyword . '</span>';
				return $name;
			}
			if ( count( $args ) > 1 ) {
				foreach ( $args as $key ) {
					$count    = substr_count( strtolower( $str ), strtolower( $key ) );
					$position = stripos( $str, $key );
					$length   = strlen( $key );
					$text     = substr( $str, $position, $length );
					$sub_key  = substr( $str, $position +1 ); //phpcs:ignore
					$pre_key  = substr( $str, 0, $position +1 ); //phpcs:ignore

					if ( $count > 1 ) {
						$pre  = str_ireplace( $key, '-----' . $text . '+++++', $pre_key );
						$t    = $this->search_key( $sub_key, $key, $str );
						$name =  $pre . $t; //phpcs:ignore
						$str  = $name;
					} else {
						$name = str_ireplace( $key, '-----' . $text . '+++++', $str );
						$str  = $name;
					}
				}

				$name = str_ireplace( '-----', '<span class="aps-highlight">', $name );
				$name = str_ireplace( '+++++', '</span>', $name );

				return $name;
			}

			$length   = strlen( $keyword );
			$position = stripos( $string, $keyword );
			$key      = substr( $string, $position, $length );
			$name     = str_ireplace( $keyword, '<span class="aps-highlight">' . $key . '</span>', $str );

			return $name;
		}

		/**
		 * Ajax search product
		 */
		public function ajax_product_search() {
			check_ajax_referer( 'ajax_product_search', 'ajax_nonce' );
			$addon_options = $this->get_options();

			$response = array();

			if ( ! isset( $_GET['keyword'] ) ) {
				wp_send_json_error();
			}
			$keyword           = sanitize_text_field( wp_unslash( $_GET['keyword'] ) );
			$cat_id            = isset( $_GET['cat_id'] ) ? sanitize_text_field( wp_unslash( $_GET['cat_id'] ) ) : array();
			$lang              = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : array();
			$parse_title       = $this->parse_title( $keyword );
			$parse_description = $this->parse_description( $keyword, $addon_options['search_description'] );
			$short_description = $this->parse_short_description( $keyword, $addon_options['search_short_description'] );
			$sql               = '';

			global $wpdb;

			$sql .= "SELECT tproduct.id FROM {$wpdb->prefix}woostify_product_index as tproduct";
			if ( $addon_options['out_stock'] ) {
				$sql .= " INNER JOIN {$wpdb->prefix}postmeta as meta ON tproduct.id = meta.post_id";
			}
			if ( $cat_id ) {
				$sql .= " INNER JOIN {$wpdb->prefix}woostify_tax_index as ttax ON tproduct.id = ttax.product_id";
			}

			$sql .= " WHERE tproduct.status = 'enable'";

			if ( $cat_id ) {
				$sql .= " AND ttax.tax_id = $cat_id";
			}

			$sql .= " AND ( tproduct.id LIKE '%$keyword%'";

			if ( $addon_options['search_by_title'] ) {
				$sql .= " OR $parse_title";
			}

			if ( $addon_options['search_by_sku'] ) {
				$sql .= " OR tproduct.sku LIKE '%$keyword%' OR tproduct.sku_variations LIKE '%$keyword%'";
			}

			if ( $addon_options['search_description'] ) {
				$sql .= " OR $parse_description";
			}

			if ( $addon_options['search_short_description'] ) {
				$sql .= " OR $short_description";
			}

			$sql .= ' )';

			if ( $lang ) {
				$sql .= " AND tproduct.lang = '$lang'";
			}

			if ( $addon_options['out_stock'] ) {
				$sql .= " AND meta.meta_value = 'instock'";
			}

			$sql .= " AND tproduct.status = 'enable'";

			$sql = apply_filters( 'woostify_ajax_search_product_sql', $sql ); //phpcs:ignore

			if ( $addon_options['search_by_sku'] ) {
				$sql .= " UNION SELECT tproduct.id FROM {$wpdb->prefix}woostify_product_index as tproduct LEFT JOIN {$wpdb->prefix}woostify_sku_index as tsku ON tproduct.id = tsku.product_id LEFT JOIN {$wpdb->prefix}postmeta as trule ON tproduct.id = trule.post_id WHERE tsku.sku LIKE '%$keyword%' AND meta_key = '_pricing_rules'";

				if ( $lang ) {
					$sql .= " AND tproduct.lang = '$lang'";
				}
				if ( $addon_options['out_stock'] ) {
					$sql .= " AND trule.meta_value = 'instock'";
				}

				$sql .= " AND tproduct.status = 'enable'";
			}
			$total_product_found = count( $wpdb->get_results( $sql ) ); //phpcs:ignore

			if ( -1 != $addon_options['total_product'] ) { //phpcs:ignore
				$sql .= " LIMIT $total_product";
			}
			$list_products = $wpdb->get_col( $sql ); //phpcs:ignore
			$products      = array();
			foreach ( $list_products as $product_id ) { //phpcs:ignore
				$product    = wc_get_product( $product_id );
				$image      = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'thumbnail' );
				$image_src  = $image ? $image[0] : wc_placeholder_img_src();
				$title      = get_the_title( $product_id );
				$price      = $product ? $product->get_price_html() : '';
				$sku        = $product ? $product->get_sku() : '';
				$in_title   = false !== strpos( $this->strip_all_char( $title ), $this->strip_all_char( $keyword ) );
				$highlight  = $addon_options['search_by_title'] && $in_title ? $this->highlight( $title, $keyword ) : $title;
				$products[] = array(
					'name_hightline' => $highlight,
					'name'           => $title,
					'sku_hightline'  => $sku,
					'sku'            => $sku,
					'html_price'     => $price,
					'image'          => $image_src,
					'url'            => get_permalink( $product_id ),
				);

			}

			$data = array(
				'product_found' => $total_product_found,
				'products'      => $products,
				'not_found'     => $_SESSION['no_product'],
				'label'         => $_SESSION['label'],
				'categories'    => false,
			);

			wp_send_json_success( $data );

		}

		/**
		 * Parse Title.
		 *
		 * @param (string) $keyword | Keyword search product title.
		 * @return (string) sql search title.
		 */
		protected function parse_title( $keyword ) {
			$key      = $keyword;
			$keywords = explode( ' ', $key );
			$length   = count( $keywords );
			if ( $length == 1 ) { //phpcs:ignore
				$sql = "tproduct.name LIKE '%$key%'";
				return $sql;
			}
			$sql = '( ';
			foreach ( $keywords as $index => $key ) {
				if ( $index == 0 ) { //phpcs:ignore
					$sql .= "tproduct.name LIKE '%$key%'";
				} else {
					$sql .= " AND tproduct.name LIKE '%$key%'";
				}
			}
			$sql .= ')';
			return $sql;
		}

		/**
		 * Parse search product description.
		 *
		 * @param (string) $keyword | Keyword search product description.
		 * @param (string) $search_description | Keyword search product description.
		 * @return (string) sql search product description.
		 */
		protected function parse_description( $keyword, $search_description ) {
			$key      = $keyword;
			$keywords = explode( ' ', $key );
			$length   = count( $keywords );
			if ( $search_description ) {
				if ( $length == 1 ) { //phpcs:ignore
					$sql = "tproduct.description LIKE '%$key%'";
					return $sql;
				}
				$sql = '( ';
				foreach ( $keywords as $index => $key ) {
					if ( $index == 0 ) { //phpcs:ignore
						$sql .= "tproduct.description LIKE '%$key%'";
					} else {
						$sql .= " AND tproduct.description LIKE '%$key%'";
					}
				}
				$sql .= ')';
			}
			return $sql;
		}

		/**
		 * Parse search product description.
		 *
		 * @param (string) $keyword | Keyword search product description.
		 * @param (string) $search_short_description sql search product description.
		 */
		protected function parse_short_description( $keyword, $search_short_description ) {
			$key      = $keyword;
			$keywords = explode( ' ', $key );
			$length   = count( $keywords );
			if ( $search_short_description ) {
				if ( $length == 1 ) { //phpcs:ignore
					$sql = "tproduct.short_description LIKE '%$key%'";
					return $sql;
				}
				$sql = '( ';
				foreach ( $keywords as $index => $key ) {
					if ( $index == 0 ) { //phpcs:ignore
						$sql .= "tproduct.short_description LIKE '%$key%'";
					} else {
						$sql .= " AND tproduct.short_description LIKE '%$key%'";
					}
				}
				$sql .= ')';
			}
			return $sql;
		}

		/**
		 * Order product found.
		 *
		 * @param (string) $keyword | Keyword search product description.
		 * @return (string) sql order.
		 */
		public function orderby( $keyword ) {
			$keywords = explode( ' ', $keyword );
			if ( count( $keywords ) == 1 ) { //phpcs:ignore
				return " ORDER BY ( CASE WHEN name LIKE '%$keyword%' THEN 1 WHEN sku LIKE '%$keyword%' THEN 2 ELSE 3 END )";
			}

			$sql = " ORDER BY ( CASE WHEN tproduct.name LIKE '%$keyword%' THEN 1 WHEN";
			foreach ( $keywords as $index => $key ) {
				if ( $index > 0 ) {
					$sql .= ' AND';
				}
				$sql .= " tproduct.name LIKE '%$key%'";
			}

			$sql .= ' THEN 2 ELSE 3 END )';

			return $sql;
		}

		/**
		 * Add submenu
		 *
		 * @see  add_submenu_page()
		 */
		public function add_setting_url() {
			$position = 5;
			$sub_menu = add_submenu_page( 'woostify-welcome', 'Settings', __( 'Ajax Product Search', 'woostify-pro' ), 'manage_options', 'ajax-search-product-settings', array( $this, 'add_settings_page' ), $position );
		}

		/**
		 * Register settings
		 */
		public function register_settings() {
			register_setting( 'ajax-search-product-settings', 'woostify_ajax_search_product_category_filter' );
			register_setting( 'ajax-search-product-settings', 'woostify_ajax_search_product_search_category' );
			register_setting( 'ajax-search-product-settings', 'woostify_ajax_search_product_search_tag' );
			register_setting( 'ajax-search-product-settings', 'woostify_ajax_search_product_attribute' );
			register_setting( 'ajax-search-product-settings', 'woostify_ajax_search_product_remove_out_stock_product' );
			register_setting( 'ajax-search-product-settings', 'woostify_ajax_search_product_total' );
			register_setting( 'ajax-search-product-settings', 'woostify_ajax_search_product_by_title' );
			register_setting( 'ajax-search-product-settings', 'woostify_ajax_search_product_by_sku' );
			register_setting( 'ajax-search-product-settings', 'woostify_ajax_search_product_by_description' );
			register_setting( 'ajax-search-product-settings', 'woostify_ajax_search_product_by_short_description' );
			register_setting( 'ajax-search-product-settings', 'woostify_ajax_search_product_highlight_color' );
		}

		/**
		 * Get addon option
		 */
		public function get_options() {
			$options                                = array();
			$options['filter']                      = get_option( 'woostify_ajax_search_product_category_filter', '0' );
			$options['search_tag']                  = get_option( 'woostify_ajax_search_product_search_tag', '0' );
			$options['search_attribute']            = get_option( 'woostify_ajax_search_product_attribute', '0' );
			$options['out_stock']                   = get_option( 'woostify_ajax_search_product_remove_out_stock_product', '0' );
			$options['total_product']               = get_option( 'woostify_ajax_search_product_total', '-1' );
			$options['search_by_title']             = get_option( 'woostify_ajax_search_product_by_title', '1' );
			$options['search_by_sku']               = get_option( 'woostify_ajax_search_product_by_sku', '1' );
			$options['highlight_color']             = get_option( 'woostify_ajax_search_product_highlight_color', '#ff0000' );
			$options['search_description']          = get_option( 'woostify_ajax_search_product_by_description', '1' );
			$options['search_short_description']    = get_option( 'woostify_ajax_search_product_by_short_description', '1' );
			$options['search_category']             = get_option( 'woostify_ajax_search_product_search_category', '0' );
			$options['search_product_custom_field'] = get_option( 'woostify_ajax_search_product_custom_field', '' );

			return $options;
		}

		/**
		 * Add Settings page
		 */
		public function add_settings_page() {
			$options       = $this->get_options();
			$index         = new Woostify_Index_Table();
			$custom_fields = $this->get_custom_field();
			?>

			<div class="woostify-options-wrap woostify-featured-setting woostify-ajax-search-product-setting" data-id="ajax-search-product" data-nonce="<?php echo esc_attr( wp_create_nonce( 'woostify-ajax-search-product-setting-nonce' ) ); ?>">

				<?php Woostify_Admin::get_instance()->woostify_save_option_messages(); ?>

				<?php Woostify_Admin::get_instance()->woostify_welcome_screen_header(); ?>

				<div class="wrap woostify-settings-box">
					<div class="woostify-welcome-container">
						<div class="woostify-notices-wrap">
							<h2 class="notices" style="display:none;"></h2>
						</div>
						<div class="woostify-settings-content">
							<h4 class="woostify-settings-section-title"><?php esc_html_e( 'Ajax Product Search', 'woostify-pro' ); ?></h4>

							<div class="woostify-settings-section-content">
								<table class="form-table">
									<tr>
										<th><?php esc_html_e( 'Filter', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_ajax_search_product_category_filter">
												<input name="woostify_ajax_search_product_category_filter" type="checkbox" id="woostify_ajax_search_product_category_filter" <?php checked( $options['filter'], '1' ); ?> value="<?php echo esc_attr( $options['filter'] ); ?>">
												<?php esc_html_e( 'Display category filter', 'woostify-pro' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Search Caterory', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_ajax_search_product_search_category">
												<input name="woostify_ajax_search_product_search_category" type="checkbox" id="woostify_ajax_search_product_search_category" <?php checked( $options['search_category'], '1' ); ?> value="<?php echo esc_attr( $options['search_category'] ); ?>">
												<?php esc_html_e( 'Show search in category', 'woostify-pro' ); ?>
											</label>
										</td>
									</tr>

									<tr>
										<th><?php esc_html_e( 'Search Tag', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_ajax_search_product_search_tag">
												<input name="woostify_ajax_search_product_search_tag" type="checkbox" id="woostify_ajax_search_product_search_tag" <?php checked( $options['search_tag'], '1' ); ?> value="<?php echo esc_attr( $options['search_tag'] ); ?>">
												<?php esc_html_e( 'Show search in tag', 'woostify-pro' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Search Attributes', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_ajax_search_product_attribute">
												<input name="woostify_ajax_search_product_attribute" type="checkbox" id="woostify_ajax_search_product_attribute" <?php checked( $options['search_attribute'], '1' ); ?> value="<?php echo esc_attr( $options['search_attribute'] ); ?>">
												<?php esc_html_e( 'Show search in attributes', 'woostify-pro' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Search in custom fields', 'woostify-pro' ); ?>:</th>
										<td>
											<div class="woostify-add-custom-field">
												<div class="custom-field-wrapper">
													<div class="woostify-custom-field-control">
														<?php
														// Draw list selected.
														if ( ! empty( $options['search_product_custom_field'] ) ) {
															$custom_field_selected = explode( ',', $options['search_product_custom_field'] );
															foreach ( $custom_field_selected as $field ) {
																?>
																<span class="woostify-auto-complete-key">
																	<span class   ="woostify-title">
																		<?php echo esc_html( $field ); ?>
																	</span>
																	<span class="btn-woostify-auto-complete-delete ion-close" data-item="<?php echo esc_html( $field ); ?>"></span>
																</span>
																<?php
															}
														}

														?>
														<input type="text" id="woostify_add_custom_field" >
														<input type="hidden" id="woostify_ajax_search_product_custom_field" name="woostify_ajax_search_product_custom_field" value="<?php echo esc_attr( $options['search_product_custom_field'] ); ?>">
													</div>

													<div class="list-custom-field">
														<ul class="custom-fields">
															<?php foreach ( $custom_fields as $value ) : ?>
																<li class="field-item" data-value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></li>
															<?php endforeach ?>
														</ul>
													</div>
												</div>

											</div>

											<div class="note">
												<span class="search-note"><?php echo esc_html__( 'Select the custom fields you want to add to the search scope', 'woostify-pro' ); ?></span>
											</div>
										</td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Out stock product', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_ajax_search_product_remove_out_stock_product">
												<input name="woostify_ajax_search_product_remove_out_stock_product" type="checkbox" id="woostify_ajax_search_product_remove_out_stock_product" <?php checked( $options['out_stock'], '1' ); ?> value="<?php echo esc_attr( $options['out_stock'] ); ?>">
												<?php esc_html_e( 'Remove Out of stock products in search results', 'woostify-pro' ); ?>
											</label>
										</td>
									</tr>

									<tr>
										<th><?php esc_html_e( 'Limit result', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_ajax_search_product_total">
												<input name="woostify_ajax_search_product_total" type="number" id="woostify_ajax_search_product_total" value="<?php echo esc_attr( $options['total_product'] ); ?>">
											</label>
											<p class="woostify-setting-description"><?php esc_html_e( 'Enter -1 to show all the products', 'woostify-pro' ); ?></p>
										</td>
									</tr>

									<tr>
										<th><?php esc_html_e( 'Search by', 'woostify-pro' ); ?>:</th>
										<td class="must-choose-one-option">
											<p>
												<label for="woostify_ajax_search_product_by_title">
													<input name="woostify_ajax_search_product_by_title" type="checkbox" id="woostify_ajax_search_product_by_title" <?php checked( $options['search_by_title'], '1' ); ?> value="<?php echo esc_attr( $options['search_by_title'] ); ?>">
													<?php esc_html_e( 'Product title', 'woostify-pro' ); ?>
												</label>
											</p>

											<p>
												<label for="woostify_ajax_search_product_by_sku">
													<input name="woostify_ajax_search_product_by_sku" type="checkbox" id="woostify_ajax_search_product_by_sku" <?php checked( $options['search_by_sku'], '1' ); ?> value="<?php echo esc_attr( $options['search_by_sku'] ); ?>">
													<?php esc_html_e( 'Product sku', 'woostify-pro' ); ?>
												</label>
											</p>
											<p>
												<label for="woostify_ajax_search_product_by_description">
													<input name="woostify_ajax_search_product_by_description" type="checkbox" id="woostify_ajax_search_product_by_description" <?php checked( $options['search_description'], '1' ); ?> value="<?php echo esc_attr( $options['search_description'] ); ?>">
													<?php esc_html_e( 'Product description', 'woostify-pro' ); ?>
												</label>
											</p>
											<p>
												<label for="woostify_ajax_search_product_by_short_description">
													<input name="woostify_ajax_search_product_by_short_description" type="checkbox" id="woostify_ajax_search_product_by_short_description" <?php checked( $options['search_short_description'], '1' ); ?> value="<?php echo esc_attr( $options['search_short_description'] ); ?>">
													<?php esc_html_e( 'Product Short Description', 'woostify-pro' ); ?>
												</label>
											</p>
										</td>
									</tr>

									<tr>
										<th><?php esc_html_e( 'Highlight color', 'woostify-pro' ); ?>:</th>
										<td>
											<label for="woostify_ajax_search_product_highlight_color">
												<input class="woostify-admin-color-picker" name="woostify_ajax_search_product_highlight_color" type="text" id="woostify_ajax_search_product_highlight_color" value="<?php echo esc_attr( $options['highlight_color'] ); ?>">
											</label>
										</td>
									</tr>

									<tr>
										<th><?php esc_html_e( 'Index Data', 'woostify-pro' ); ?>:</th>
										<td>
											<div class="index-data">
												<button class="button button-primary btn-index-data"><?php esc_html_e( 'Index data', 'woostify-pro' ); ?></button>
												<span class="progress"></span>
											</div>
											<div class="woostify-progress-bar hidden">
												<div class="woostify-progress-bar-background">
													<div class="woostify-progress-bar-value"></div>
												</div>
											</div>
										</td>
									</tr>
									<?php if ( $index->total_product() && $index->last_index() ) : ?>
										<tr>
											<th><?php esc_html_e( 'Last Update', 'woostify-pro' ); ?>:</th>
											<td>
												<span class="last-index"> <?php echo esc_html( $index->last_index() ); ?></span>
											</td>
										</tr>

										<tr>
											<th><?php esc_html_e( 'Total Product Index', 'woostify-pro' ); ?>:</th>
											<td>
												<span class="index-total-product"><?php echo esc_html( $index->total_product() ); ?></span>
											</td>
										</tr>
									<?php endif ?>
								</table>
							</div>

							<div class="woostify-settings-section-footer">
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
		 * Ajax index data
		 */
		public function index_data() {
			check_ajax_referer( 'woostify_nonce' );
			global $wpdb;
			$index      = new Woostify_Index_Table();
			$table_name = $wpdb->prefix . 'woostify_product_index';
			$table_tax  = $wpdb->prefix . 'woostify_tax_index';
			$table_sku  = $wpdb->prefix . 'woostify_sku_index';
			$table_stw  = $wpdb->prefix . 'woostify_stopwords';
			$table_cat  = $wpdb->prefix . 'woostify_category_index';
			$table_tag  = $wpdb->prefix . 'woostify_tag_index';
			$table_attr = $wpdb->prefix . $index::DB_ATTRIBUTE;
			// drop the table from the database.
			$wpdb->query( "DROP TABLE IF EXISTS $table_name" ); // phpcs:ignore
			$wpdb->query( "DROP TABLE IF EXISTS $table_tax" ); // phpcs:ignore
			$wpdb->query( "DROP TABLE IF EXISTS $table_sku" ); // phpcs:ignore
			$wpdb->query( "DROP TABLE IF EXISTS $table_stw" ); // phpcs:ignore
			$wpdb->query( "DROP TABLE IF EXISTS $table_cat" ); // phpcs:ignore
			$wpdb->query( "DROP TABLE IF EXISTS $table_tag" ); // phpcs:ignore
			$wpdb->query( "DROP TABLE IF EXISTS $table_attr" ); // phpcs:ignore
			$index->create_table();
			$index->create_table_tax();
			$index->sku_table();
			$index->create_table_category();
			$index->create_table_tag();
			$index->create_table_attribute();
			$data_installed = $index->install_data();

			$results['message']       = __( 'Index data Complete', 'woostify' );
			$results['total_product'] = $index->total_product();
			$results['time']          = $index->last_index();
			$results['index_size']    = $data_installed['index_size'];

			if ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
				$results['per']     = $data_installed['per_page'];
				$results['timer']   = timer_stop();
				$results['request'] = get_num_queries();
				$results['mem']     = ceil( memory_get_usage() / 1024 / 1024 );
			}

			wp_send_json_success( $results );
			wp_die();
		}

		/**
		 * Update Index tabel
		 *
		 * @param (int|string) $post_id | Post Id.
		 * @param (object)     $post | Post.
		 * @param (boolean)    $update     | True or False.
		 */
		public function update_table( $post_id, $post, $update ) {
			// If an old book is being updated, exit.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'woostify_product_index';
			$table_tax  = $wpdb->prefix . 'woostify_tax_index';
			$table_sku  = $wpdb->prefix . 'woostify_sku_index';
			$lang       = get_locale();
			if ( 'product' == get_post_type( $post_id ) ) { // phpcs:ignore
				$product = wc_get_product( $post_id );
				$terms   = get_the_terms( $post_id, 'product_cat' );
				$status  = 'enable';
				if ( isset( $_POST['_visibility'] ) ) { // phpcs:ignore
					$visibility = $_POST['_visibility']; // phpcs:ignore
					if ( 'catalog' == $visibility || 'hidden' == $visibility ) { // phpcs:ignore
						$status = 'disable';
					}

					$wpdb->update( // phpcs:ignore
						$table_name,
						array(
							'status' => $status,
						),
						array(
							'id' => $post_id,
						)
					);
				}

				if ( isset( $_POST['tax_input'] ) ) { // phpcs:ignore
					$product_cat = $_POST['tax_input']['product_cat']; // phpcs:ignore
					if ( ! empty( $terms ) ) {
						foreach ( $terms as $term ) {
							$term_id = $term->term_id;
							if ( $update ) {
								$sql = "SELECT id FROM $table_tax WHERE product_id = $post_id AND tax_id = $term_id";
								$id  = $wpdb->get_var( $sql ); //phpcs:ignore
								if ( $id ) {
									$wpdb->delete( // phpcs:ignore
										$table_tax,
										array(
											'id' => $id,
										)
									);
								}
							}
						}
					}

					foreach ( $product_cat as $cat_id ) {
						$time       = current_time( 'mysql' );
						$parentcats = get_ancestors( $cat_id, 'product_cat' );
						if ( ! empty( $parentcats ) ) {
							foreach ( $parentcats as $cat ) {
								$wpdb->insert( //phpcs:ignore
									$table_tax,
									array(
										'tax_id'       => $cat,
										'product_id'   => $post_id,
										'lang'         => $lang,
										'created_date' => current_time( 'mysql' ),
									)
								);
							}
						}
						$query = "INSERT INTO $table_tax ( tax_id, product_id, lang, created_date ) VALUES ( '$cat_id', '$post_id', '$lang', '$time' )";
						$wpdb->query( $query ); //phpcs:ignore
					}
				}
			}
		}

		/**
		 * Product meta save
		 *
		 * @param (int)    $meta_id | Meta Id.
		 * @param (int)    $post_id | Post Id.
		 * @param (string) $meta_key | True or False.
		 * @param (mixed)  $meta_value | Value of meta key.
		 */
		public function product_meta_save( $meta_id, $post_id, $meta_key, $meta_value ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'woostify_product_index';
			$table_tax  = $wpdb->prefix . 'woostify_tax_index';
			$table_sku  = $wpdb->prefix . 'woostify_sku_index';
			$lang       = get_locale();
			$time       = current_time( 'mysql' );

			if ( 'product' == get_post_type( $post_id ) ) { // phpcs:ignore

				if ( $meta_key == '_regular_price' ) { // phpcs:ignore

					update_post_meta( $post_id, '_regular_price', $meta_value );

					$product = wc_get_product( $post_id );
					$product = wc_get_product( $post_id );
					$product->set_regular_price( $meta_value );
					$product->set_price( $meta_value );
					$product->save();
					$wpdb->update( // phpcs:ignore
						$table_name,
						array(
							'html_price' => $product->get_price_html(),
							'price'      => $meta_value,
						),
						array(
							'id' => $post_id,
						)
					);
				}

				if ( $meta_key == '_sale_price' ) { // phpcs:ignore
					update_post_meta( $post_id, '_sale_price', $meta_value );
					$product = wc_get_product( $post_id );
					$product->set_sale_price( $meta_value );
					$product->set_price( $meta_value );
					$product->save();
					$wpdb->update( // phpcs:ignore
						$table_name,
						array(
							'html_price' => $product->get_price_html(),
							'price'      => $product->get_price(),
						),
						array(
							'id' => $post_id,
						)
					);
				}

				if ( '_sku' == $meta_key ) { // phpcs:ignore
					$wpdb->update( // phpcs:ignore
						$table_name,
						array(
							'id'  => $post_id,
							'sku' => $meta_value,
						),
						array(
							'id' => $post_id,
						)
					);
				}

				$product = wc_get_product( $post_id );

				if ( 'variable' == $product->get_type() ) { //phpcs:ignore
					$sku_array = array();
					foreach ( $product->get_visible_children( false ) as $child_id ) {
						$variation = wc_get_product( $child_id );
						if ( $variation && $variation->get_sku() ) {
							$sku_array[] = $variation->get_sku();
							$sql         = "SELECT id FROM $table_sku WHERE product_id = $post_id AND sku = '{$variation->get_sku()}'";
							$id          = $wpdb->get_var( $sql ); //phpcs:ignore
							if ( $id ) {
								$wpdb->delete( // phpcs:ignore
									$table_sku,
									array(
										'id' => $id,
									)
								);
							}

							$query = "INSERT INTO $table_sku ( sku, product_id, lang, created_date ) VALUES ( '{$variation->get_sku()}', '$post_id', '$lang', '$time' )";
							$wpdb->query( $query ); //phpcs:ignore
						}
					}

					$list_sku = implode( ',', $sku_array );
					$wpdb->update( // phpcs:ignore
						$table_name,
						array(
							'html_price'     => $product->get_price_html(),
							'price'          => $product->get_price(),
							'sku_variations' => $list_sku,
						),
						array(
							'id' => $post_id,
						)
					);
				}
			}
		}

		/**
		 * Update Table when delete product.
		 *
		 * @param (string|int) $post_id }| Post Id.
		 */
		public function delete_product( $post_id ) {
			// If an old book is being updated, exit.
			global $wpdb;
			$table_name = $wpdb->prefix . 'woostify_product_index';
			if ( 'product' == get_post_type( $post_id ) ) { //phpcs:ignore
				$wpdb->delete( // phpcs:ignore
					$table_name,
					array(
						'id' => $post_id,
					)
				);
			}
		}

		/**
		 * Update table index when untrash post.
		 *
		 * @param (string|int) $post_id }| Post Id.
		 */
		public function untrash_post( $post_id ) {
			// If an old book is being updated, exit.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			global $wpdb;
			$lang       = get_locale();
			$time       = current_time( 'mysql' );
			$index      = new Woostify_Index_Table();
			$table_name = $wpdb->prefix . $index::DB_NAME;
			$table_tax  = $wpdb->prefix . $index::DB_TAX_NAME;
			$table_sku  = $wpdb->prefix . $index::DB_SKU_INDEX;
			if ( 'product' == get_post_type( $post_id ) ) { //phpcs:ignore
				$post = get_post( $post_id );
				$index->create_product( $post, $table_name, $table_tax, $table_sku );
			}
		}

		/**
		 * Action Customer search template.
		 *
		 * @param (string) $search_template | Custom template search.
		 */
		public function search_result_template( $search_template ) {

			if ( 'product' == get_query_var( 'post_type' ) && is_search() ) { // phpcs:ignore
				$search_template = WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/ajax-product-search/templates/search.php';
			}

			return $search_template;
		}

		/**
		 * Get Currencies.
		 */
		public function get_currencies() {
			global $woocommerce_wpml;
			if ( $woocommerce_wpml && $woocommerce_wpml->multi_currency ) {
				return $woocommerce_wpml->multi_currency->get_currencies( 'include_default = true' );
			}
			return false;
		}

		/**
		/**
		 * Get Lisst  Currencies WPML.
		 */
		public function get_list_currency() {
			global $woocommerce_wpml;
			$currencies    = $this->get_currencies();
			$list_currency = array();
			$lang          = array();
			if ( $currencies ) {

				foreach ( $currencies as $currency => $data ) {
					$lang = $data['languages'];
				}

				foreach ( $lang as $key => $code ) {
					$list_currency[ $key ] = $woocommerce_wpml->multi_currency->get_language_default_currency( $key );
				}
			}

			return $list_currency;
		}

		/**
		 * Session User.
		 */
		public function session_user() {
			if ( ! session_id() && ! headers_sent() ) {
				session_start();
			}
			global $woocommerce_wpml;
			$user                           = wp_get_current_user();
			$_SESSION['currency_symbol']    = get_woocommerce_currency_symbol();
			$_SESSION['currency_code']      = get_woocommerce_currency();
			$_SESSION['version']            = WOOSTIFY_PRO_VERSION;
			$_SESSION['currency_pos']       = get_option( 'woocommerce_currency_pos' );
			$_SESSION['user']               = array(
				'id'      => $user->ID,
				'roles'   => $user->roles,
				'caps'    => $user->caps,
				'allcaps' => $user->allcaps,
			);
			$_SESSION['lang']               = false;
			$_SESSION['wc_dynamic_pricing'] = false;
			if ( defined( 'ICL_SITEPRESS_VERSION' ) && defined( 'ICL_LANGUAGE_CODE' ) && $woocommerce_wpml ) {
				$_SESSION['lang']            = true;
				$_SESSION['currency_symbol'] = array();
				$_SESSION['currency_code']   = array();
				foreach ( $this->get_list_currency() as $code => $currency ) {
					$_SESSION['currency_symbol'][ $code ] = get_woocommerce_currency_symbol( $currency );
					$_SESSION['currency_code'][ $code ]   = $currency;
				}
			}
			if ( class_exists( 'WC_Dynamic_Pricing' ) ) {
				$_SESSION['wc_dynamic_pricing'] = true;
			}

			session_write_close();
		}

		/**
		 * Notice index table.
		 */
		public function admin_notice_index() {
			global $wpdb;
			$table_name  = $wpdb->prefix . Woostify_Index_Table::DB_NAME;
			$sql         = "SELECT *  FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = 'transition'";
			$check_index = ! $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ); //phpcs:ignore
			$query       = $wpdb->get_results( $sql ); //phpcs:ignore

			if( $check_index ) { // phpcs:ignore
				?>
					<div class="notice notice-error message woostify-index-notice woostify-notice">
						<div class="notice-content-wrapper">
							<div class="notice-logo">
								<img src="<?php echo esc_url( WOOSTIFY_PRO_URI . 'assets/images/logo.png' ); ?>" alt="<?php echo esc_attr( 'Woostify' ); ?>">
							</div>
							<div class="notice-content">

								<h2 class="notice-head"><?php echo esc_html__( 'Important Setup!', 'woostify-pro' ); ?></h2>

								<span class="notice-indexer">
									<?php echo esc_html__( 'Woostify Ajax Product Search requires setup to work, please go to Ajax Product Search page and click ', 'woostify-pro' ); ?>
									<?php echo '<strong>' . esc_html__( 'Index Data button.', 'woostify-pro' ) . '</strong>'; ?>
								</span>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=ajax-search-product-settings' ) ); ?>">
									<?php echo esc_html__( 'Index Now', 'woostify-pro' ); ?>
								</a>

								<span class="btn admin-btn btn-close-notice notice-dismiss">
								</span>

							</div>
						</div>
					</div>
				<?php
			} elseif ( ! $check_index && empty( $query ) ) {
				?>
				<div class="notice notice-error message woostify-index-notice woostify-notice">
					<div class="notice-content-wrapper">
						<div class="notice-logo">
							<img src="<?php echo esc_url( WOOSTIFY_PRO_URI . 'assets/images/logo.png' ); ?>" alt="<?php echo esc_attr( 'Woostify' ); ?>">
						</div>
						<div class="notice-content">

							<h2 class="notice-head"><?php echo esc_html__( 'Important Index data!', 'woostify-pro' ); ?></h2>

							<span class="notice-indexer">
								<?php echo esc_html__( 'The new version of Woostify Ajax Product Search requires reindex product to work, please go to Ajax Product Search page and click ', 'woostify-pro' ); ?>
								<?php echo '<strong>' . esc_html__( 'Index Data button.', 'woostify-pro' ) . '</strong>'; ?>
							</span>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ajax-search-product-settings' ) ); ?>">
								<?php echo esc_html__( 'Index Now', 'woostify-pro' ); ?>
							</a>

							<span class="btn admin-btn btn-close-notice notice-dismiss">
							</span>

						</div>
					</div>
				</div>
				<?php
			}
		}

		/**
		 * Notice index table.
		 *
		 * @param (string) $new_status }| New Status.
		 * @param (string) $old_status }| Old Status.
		 * @param (object) $post }| Post Object.
		 */
		public function status_transitions( $new_status, $old_status, $post ) {
			global $wpdb;
			$index      = new Woostify_Index_Table();
			$table_name = $wpdb->prefix . $index::DB_NAME;
			$post_id    = $post->ID;
			if ( $new_status != $old_status && 'product' == get_post_type( $post_id ) ) { // phpcs:ignore
				$id = $wpdb->update( // phpcs:ignore
					$table_name,
					array(
						'transition' => $new_status,
					),
					array(
						'id' => $post_id,
					)
				);
			}
		}

		/**
		 * Notice index table.
		 *
		 * @param (string) $post_id | Post ID.
		 * @param (object) $post }| Post Object.
		 */
		public function save_product( $post_id, $post ) {
			global $wpdb;
			$index      = new Woostify_Index_Table();
			$table_name = $wpdb->prefix . $index::DB_NAME;
			$wpdb->update( // phpcs:ignore
				$table_name,
				array(
					'name'              => $post->post_title,
					'short_description' => $post->post_excerpt,
					'description'       => $post->post_content,
				),
				array(
					'id' => $post_id,
				)
			);
		}

		/**
		 * Update category table index.
		 *
		 * @param (int)    $term_id }| Term ID.
		 * @param (string) $tt_id }| Term taxonomy ID.
		 */
		public function update_category( $term_id, $tt_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . Woostify_Index_Table::DB_CATEGORIES;
			$term       = get_term( $term_id );
			$url        = get_term_link( $term_id, 'product_cat' );
			$wpdb->update( // phpcs:ignore
				$table_name,
				array(
					'name' => $term->name,
					'url'  => $url,
				),
				array(
					'id' => $term_id,
				)
			);
		}

		/**
		 * Update tag table index.
		 *
		 * @param (int)    $term_id }| Term ID.
		 * @param (string) $tt_id }| Term taxonomy ID.
		 */
		public function update_tag( $term_id, $tt_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . Woostify_Index_Table::DB_TAGS;
			$term       = get_term( $term_id );
			$url        = get_term_link( $term_id, 'product_tag' );
			$wpdb->update( // phpcs:ignore
				$table_name,
				array(
					'name' => $term->name,
					'url'  => $url,
				),
				array(
					'id' => $term_id,
				)
			);
		}

		/**
		 * Create Category table index.
		 *
		 * @param (int)    $term_id }| Term ID.
		 * @param (string) $tt_id }| Term taxonomy ID.
		 */
		public function create_category( $term_id, $tt_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . Woostify_Index_Table::DB_CATEGORIES;
			$term       = get_term( $term_id );
			$url        = get_term_link( $term_id, 'product_cat' );
			$index      = new Woostify_Index_Table();
			$lang       = get_locale();
			if ( defined( 'ICL_SITEPRESS_VERSION' ) && defined( 'ICL_LANGUAGE_CODE' ) ) {
				$lang = $index->get_lang_by_local( $lang );
			}
			$wpdb->insert( //phpcs:ignore
				$table_name,
				array(
					'id'           => $term->term_id,
					'name'         => $term->name,
					'url'          => $url,
					'lang'         => $lang,
					'created_date' => current_time( 'mysql' ),
				)
			);
		}

		/**
		 * Create tag table index.
		 *
		 * @param (int)    $term_id }| Term ID.
		 * @param (string) $tt_id }| Term taxonomy ID.
		 */
		public function create_tag( $term_id, $tt_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . Woostify_Index_Table::DB_TAGS;
			$term       = get_term( $term_id );
			$url        = get_term_link( $term_id, 'product_tag' );
			$index      = new Woostify_Index_Table();
			$lang       = get_locale();
			if ( defined( 'ICL_SITEPRESS_VERSION' ) && defined( 'ICL_LANGUAGE_CODE' ) ) {
				$lang = $index->get_lang_by_local( $lang );
			}
			$wpdb->insert( //phpcs:ignore
				$table_name,
				array(
					'id'           => $term->term_id,
					'name'         => $term->name,
					'url'          => $url,
					'lang'         => $lang,
					'created_date' => current_time( 'mysql' ),
				)
			);
		}

		/**
		 * Create tag table index.
		 */
		public function index_notice_transition() {
			global $wpdb;
			$table_name = $wpdb->prefix . Woostify_Index_Table::DB_NAME;
			$sql        = "SELECT *  FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = 'transition'";
			$query = $wpdb->get_results( $sql ); //phpcs:ignore

			if ( empty( $query ) ) {
				?>
					<div class="notice notice-error message woostify-index-notice woostify-notice">
						<div class="notice-content-wrapper">
							<div class="notice-logo">
								<img src="<?php echo esc_url( WOOSTIFY_PRO_URI . 'assets/images/logo.png' ); ?>" alt="<?php echo esc_attr( 'Woostify' ); ?>">
							</div>
							<div class="notice-content">

								<h2 class="notice-head"><?php echo esc_html__( 'Important Index data!', 'woostify-pro' ); ?></h2>

								<span class="notice-indexer">
									<?php echo esc_html__( 'The new version of Woostify Ajax Product Search requires reindex product to work, please go to Ajax Product Search page and click ', 'woostify-pro' ); ?>
									<?php echo '<strong>' . esc_html__( 'Index Data button.', 'woostify-pro' ) . '</strong>'; ?>
								</span>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=ajax-search-product-settings' ) ); ?>">
									<?php echo esc_html__( 'Index Now', 'woostify-pro' ); ?>
								</a>

								<span class="btn admin-btn btn-close-notice notice-dismiss">
								</span>

							</div>
						</div>
					</div>
				<?php
			}
		}

		/**
		 * Enable archive attribute.
		 *
		 * @param (int)    $option | Term ID.
		 * @param (string) $old_value | Term taxonomy ID.
		 * @param (string) $value | Term taxonomy ID.
		 */
		public function enable_archive_attribute( $option, $old_value, $value ) {
			if ( $option == 'woostify_ajax_search_product_attribute' && ! empty( $value ) ) { // phpcs:ignore
				$attributes_tax = wc_get_attribute_taxonomies();
				foreach ( $attributes_tax as $tax ) {
					$args = array(
						'name'         => $tax->attribute_label,
						'slug'         => wc_sanitize_taxonomy_name( $tax->attribute_name ),
						'type'         => $tax->attribute_type,
						'order_by'     => $tax->attribute_orderby,
						'has_archives' => 1,
					);
					wc_update_attribute( $tax->attribute_id, $args );
				}
			}
		}

		/**
		 * Get all custom field of product.
		 *
		 * @param (string) $key | keyword.
		 */
		public function get_custom_field( $key = null ) {
			global $wpdb;
			$sql = "SELECT DISTINCT meta_key
				FROM $wpdb->postmeta as pm
				INNER JOIN $wpdb->posts as p ON p.ID = pm.post_id
				WHERE p.post_type = 'product'
				AND pm.meta_value NOT LIKE 'field_%'
				AND pm.meta_value NOT LIKE 'a:%'
				AND pm.meta_value NOT LIKE '%\%\%%'
				AND pm.meta_value NOT LIKE '_oembed_%'
				AND pm.meta_value NOT REGEXP '^1[0-9]{9}'
				AND pm.meta_value NOT IN ('1','0','-1','no','yes','[]', '')";

			if ( $key ) {
				$sql .= " AND pm.meta_key LIKE '%$key%'";
			}

			$meta_key           = $wpdb->get_col( $sql ); // phpcs:ignore
			$excluded_meta_keys = array(
				'_sku',
				'_wp_old_date',
				'_tax_status',
				'_stock_status',
				'_product_version',
				'_smooth_slider_style',
				'auctioninc_calc_method',
				'auctioninc_pack_method',
				'_thumbnail_id',
				'_product_image_gallery',
				'pdf_download',
				'slide_template',
				'cad_iframe',
				'downloads',
				'edrawings_file',
				'3d_pdf_download',
				'3d_pdf_render',
				'_original_id',
			);
			$custom_fields      = array_diff( $meta_key, $excluded_meta_keys );

			return $custom_fields;
		}

		/**
		 * Get all custom field of product.
		 */
		public function find_custom_field() {
			check_ajax_referer( 'woostify_nonce', '_ajax_nonce' );
			$key           = sanitize_text_field( wp_unslash( $_GET['key'] ) );//phpcs:ignore
			$custom_fields = $this->get_custom_field( $key );

			foreach ( $custom_fields as $value ) {
				?>
				<li class="field-item" data-value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></li>
				<?php
			}

			die();

		}

		/**
		 * Ajax index data
		 */
		public function index_data_next() {
			check_ajax_referer( 'woostify_nonce' );
			global $wpdb;
			$results    = array();
			$index      = new Woostify_Index_Table();
			$table_name = $wpdb->prefix . 'woostify_product_index';
			$table_tax  = $wpdb->prefix . 'woostify_tax_index';
			$table_sku  = $wpdb->prefix . 'woostify_sku_index';
			$table_stw  = $wpdb->prefix . 'woostify_stopwords';
			$table_cat  = $wpdb->prefix . 'woostify_category_index';
			$table_tag  = $wpdb->prefix . 'woostify_tag_index';
			$table_attr = $wpdb->prefix . $index::DB_ATTRIBUTE;
			$page       = ( isset( $_GET['index'] ) ) ? $_GET['index'] : 1; //phpcs:ignore
			$page++;
			$per_page     = $index->get_chunk_number();
			$products     = wc_get_products(
				array(
					'order'    => 'id',
					'limit'    => $per_page,
					'page'     => $page,
					'paginate' => true,
				)
			);
			$product_list = $products->products;
			$index_size   = $products->max_num_pages;
			unset( $products );
			$result = $index->product_to_index( $product_list, $table_name, $table_tax, $table_sku );

			$results['message']       = __( 'Index data Complete', 'woostify' );
			$results['total_product'] = $index->total_product();
			$results['time']          = $index->last_index();
			$results['index_size']    = $index_size;

			if ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
				$results['per']     = $per_page;
				$results['timer']   = timer_stop();
				$results['request'] = get_num_queries();
				$results['mem']     = ceil( memory_get_usage() / 1024 / 1024 );
			}

			wp_send_json_success( $results );
			wp_die();
		}

	}

	Woostify_Ajax_Product_Search::get_instance();

endif;
