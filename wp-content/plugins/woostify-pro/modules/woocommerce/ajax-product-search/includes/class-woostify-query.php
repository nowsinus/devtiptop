<?php
/**
 * Woostify Ajax Product Search Class
 *
 * @package  Woostify Pro
 */

namespace Woostify\Woocommerce;

defined( 'ABSPATH' ) || exit;


/**
 * Woostify Ajax Product Search Class
 */
class Woostify_Query {

	/**
	 * Key Word
	 *
	 * @var total_product
	 */
	protected $keyword;

	/**
	 * Category ID
	 *
	 * @var category_id
	 */
	protected $category_id;

	/**
	 * Show total Product
	 *
	 * @var total_product
	 */
	protected $total_product;

	/**
	 * Search By SKU
	 *
	 * @var search_by_sku
	 */
	protected $search_by_sku;

	/**
	 * Search By Title
	 *
	 * @var search_by_title
	 */
	protected $search_by_title;

	/**
	 * Search By Title
	 *
	 * @var lang
	 */
	protected $lang;

	/**
	 * Search By Title
	 *
	 * @var remove_stock
	 */
	protected $remove_stock;

	/**
	 * Search Cateory
	 *
	 * @var search_category
	 */
	protected $search_category;

	/**
	 * Search Tag
	 *
	 * @var search_tag
	 */
	protected $search_tag;

	/**
	 * Search Attribute
	 *
	 * @var search_attribute
	 */
	protected $search_attribute;

	/**
	 * Search by description
	 *
	 * @var search_description
	 */
	protected $search_description;

	/**
	 * Search by short description
	 *
	 * @var search_short_description
	 */
	protected $search_short_description;

	/**
	 * Search by short descrition
	 *
	 * @var search_short_description
	 */
	protected $search_custom_field;

	/**
	 * Constructor.
	 *
	 * @param (array) $args | Search data.
	 */
	public function __construct( $args ) {
		$this->keyword                  = $args['keyword'];
		$this->category_id              = $args['cat_id'];
		$this->total_product            = $args['total_product'];
		$this->search_by_title          = $args['title'];
		$this->search_by_sku            = $args['sku'];
		$this->lang                     = $args['lang'];
		$this->remove_stock             = $args['outstock'];
		$this->search_category          = $args['search_category'];
		$this->search_tag               = $args['search_tag'];
		$this->search_attribute         = $args['search_attribute'];
		$this->search_description       = $args['description'];
		$this->search_short_description = $args['short_description'];
		$this->search_custom_field      = $args['search_custom_field'];
	}

	/**
	 * Check transition column.
	 *
	 * @return Query string.
	 */
	protected function transition_update() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'woostify_product_index';
		$sql        = "SELECT *  FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = 'transition'";
		$query = $wpdb->get_results( $sql ); //phpcs:ignore

		return $query;
	}

	/**
	 * Get SQL query string.
	 *
	 * @return Query string.
	 */
	protected function query_string() {
		global $wpdb;

		$sql               = "SELECT tproduct.id, tproduct.name, tproduct.type, tproduct.max_price, tproduct.sku, tproduct.image, tproduct.url, tproduct.price, tproduct.html_price FROM {$wpdb->prefix}woostify_product_index as tproduct";
		$cat_id            = $this->category_id;
		$keyword           = $this->keyword;
		$lang              = $this->lang;
		$parse_title       = $this->parse_title( $keyword );
		$parse_sku         = $this->parse_sku( $keyword );
		$parse_description = $this->parse_description( $keyword );
		$short_description = $this->parse_short_description( $keyword );
		$sku_valiable      = $this->parse_sku_valiable( $keyword );
		$custom_field      = $this->parse_custom_field( $keyword );
		$key_arrays        = explode( ' ', trim( $keyword ) );

		if ( count( $key_arrays ) > 1 ) {
			$this->search_by_sku = false;
		}

		if ( $this->remove_stock ) {
			$sql .= " INNER JOIN {$wpdb->prefix}postmeta as meta ON tproduct.id = meta.post_id";
		}

		if ( $cat_id ) {
			$sql .= " INNER JOIN {$wpdb->prefix}woostify_tax_index as ttax ON tproduct.id = ttax.product_id";
		}

		if ( $this->search_custom_field ) {
			$sql .= " INNER JOIN {$wpdb->prefix}postmeta as acf ON tproduct.id = acf.post_id";
		}

		$sql .= " WHERE ( tproduct.id LIKE '%$keyword%'";

		if ( $this->search_by_title ) {
			$sql .= " OR $parse_title";
		}

		if ( $this->search_by_sku ) {
			$sql .= " OR $parse_sku";
		}

		if ( $this->search_description ) {
			$sql .= " OR $parse_description";
		}

		if ( $this->search_short_description ) {
			$sql .= " OR $short_description";
		}

		if ( $this->search_custom_field ) {
			$sql .= " OR $custom_field";
		}

		$sql .= ' )';

		if ( ! empty( $this->transition_update() ) ) {
			$sql .= " AND tproduct.transition = 'publish'";
		}

		if ( $cat_id ) {
			$sql .= " AND ttax.tax_id = $cat_id";
		}

		if ( $lang ) {
			$sql .= " AND tproduct.lang = '$lang'";
		}

		if ( $this->remove_stock ) {
			$sql .= " AND meta.meta_value != 'outofstock' AND meta.meta_key = '_stock_status'";
		}

		$sql .= " AND tproduct.status = 'enable'";

		$sql = apply_filters( 'woostify_ajax_search_product_sql', $sql ); //phpcs:ignore

		if ( $this->search_by_sku ) {
			$sql .= " UNION SELECT tproduct.id, tproduct.name, tproduct.type, tproduct.max_price, tsku.sku, tproduct.image, tproduct.url, tproduct.price, tproduct.html_price FROM {$wpdb->prefix}woostify_product_index as tproduct LEFT JOIN {$wpdb->prefix}woostify_sku_index as tsku ON tproduct.id = tsku.product_id LEFT JOIN {$wpdb->prefix}postmeta as trule ON tproduct.id = trule.post_id WHERE $sku_valiable";

			if ( $lang ) {
				$sql .= " AND tproduct.lang = '$lang'";
			}
			if ( $this->remove_stock ) {
				$sql .= " AND trule.meta_value = 'outofstock' AND trule.meta_key = '_stock_status'";
			}

			$sql .= " AND tproduct.status = 'enable'";
		}
		// echo( $sql );
		// exit;
		return $sql;
	}

	/**
	 * Parse search product custom fields.
	 *
	 * @param (string) $keyword | Keyword search product custom field.
	 * @return (string) sql search product description.
	 */
	protected function parse_custom_field( $keyword ) {
		$custom_fields  = $this->search_custom_field;
		$key            = $keyword;
		$keywords       = explode( ' ', $key );
		$length         = count( $keywords );
		$metakey_string = "'" . implode( "', '", explode( ',', $custom_fields ) ) . "'";
		if ( $this->search_custom_field && ! empty( $custom_fields ) ) {
			$sql = "( acf.meta_key in ( $metakey_string )"; // Du 1 dau mo ngoac don.
			foreach ( $keywords as $index => $key ) {
				$sql .= " AND acf.meta_value LIKE '%$key%'";
			}
			$sql .= ')'; // Close dau ngoac don.

			return $sql;
		}
	}

	/**
	 * Parse search product description.
	 *
	 * @param (string) $keyword | Keyword search product description.
	 * @return (string) sql search product description.
	 */
	protected function parse_description( $keyword ) {
		$key      = $keyword;
		$keywords = explode( ' ', $key );
		$length   = count( $keywords );
		if ( $this->search_description ) {
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

			return $sql;
		}
	}

	/**
	 * Parse search product description.
	 *
	 * @param (string) $keyword | Keyword search product description.
	 * @return (string) sql search product description.
	 */
	protected function parse_short_description( $keyword ) {
		$key      = $keyword;
		$keywords = explode( ' ', $key );
		$length   = count( $keywords );
		if ( $this->search_short_description ) {
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

			return $sql;
		}
	}

	/**
	 * SQL search category.
	 *
	 * @return (string) sql search title.
	 */
	public function category_query_string() {
		global $wpdb;
		$keyword     = $this->keyword;
		$keyword     = str_replace( '-', ' ', trim( $keyword ) );
		$keyword     = str_replace( ' ', ' +', trim( $keyword ) );
		$keyword     = str_replace( ' ', '* ', trim( $keyword ) );
		$keyword     = '+' . $keyword . '*';
		$lang        = $this->lang;
		$parse_title = $this->parse_title( $keyword );
		$sql         = "SELECT * FROM {$wpdb->prefix}woostify_category_index WHERE match(name) against('$keyword' IN BOOLEAN MODE)";
		if ( $lang ) {
			$sql .= " AND lang = '$lang'";
		}

		return $sql;
	}

	/**
	 * SQL search category.
	 *
	 * @return (string) sql search title.
	 */
	public function attribte_query_string() {
		global $wpdb;
		$keyword     = $this->keyword;
		$keyword     = str_replace( '-', ' ', trim( $keyword ) );
		$keyword     = str_replace( ' ', ' +', trim( $keyword ) );
		$keyword     = str_replace( ' ', '* ', trim( $keyword ) );
		$keyword     = '+' . $keyword . '*';
		$lang        = $this->lang;
		$parse_title = $this->parse_title( $keyword );
		$sql         = "SELECT * FROM {$wpdb->prefix}woostify_attribute_index WHERE match(name) against('$keyword' IN BOOLEAN MODE)";
		if ( $lang ) {
			$sql .= " AND lang = '$lang'";
		}

		return $sql;
	}

	/**
	 * SQL search tags.
	 *
	 * @return (string) sql search tags.
	 */
	public function tags_query_string() {
		global $wpdb;
		$keyword  = $this->keyword;
		$keyword  = str_replace( '-', ' ', trim( $keyword ) );
		$keywords = explode( ' ', $keyword );
		$sql      = '';
		if ( ! empty( $keywords ) ) {
			$sql = "SELECT * FROM {$wpdb->prefix}woostify_tag_index WHERE ";
			foreach ( $keywords as $i => $keyword ) {
				$and  = $i ? ' AND ' : '';
				$sql .= $and . "name like '%{$keyword}%'";
			}
			$lang = $this->lang;
			if ( $lang ) {
				$sql .= " AND lang = '$lang'";
			}
		}

		return $sql;
	}

	/**
	 * Order product found.
	 *
	 * @return (string) sql order.
	 */
	public function orderby() {
		$keyword  = $this->keyword;
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
	 * Parse Sku.
	 *
	 * @param (string) $keyword | Keyword search product title.
	 * @return (string) sql search sku.
	 */
	protected function parse_sku( $keyword ) {
		$sql = "tproduct.sku LIKE '%$keyword%' OR tproduct.sku_variations LIKE '%$keyword%'";
		return $sql;
	}

	/**
	 * Parse Sku variable.
	 *
	 * @param (string) $keyword | Keyword search product title.
	 * @return (string) sql search sku.
	 */
	protected function parse_sku_valiable( $keyword ) {
		$sql = "tsku.sku LIKE '%$keyword%'";
		return $sql;
	}

	/**
	 * Count Total product.
	 *
	 * @return total product.
	 */
	protected function total_product() {
		global $wpdb;
		$sql            = $this->query_string();
		$total_products = $wpdb->get_results( $sql );  //phpcs:ignore

		return count( $total_products );
	}

	/**
	 * Total category.
	 *
	 * @return Categories.
	 */
	protected function categories_found() {
		global $wpdb;
		if ( $this->table_exit( $wpdb->prefix . 'woostify_category_index' ) ) {
			$sql        = $this->category_query_string();
			$categories = $wpdb->get_results( $sql ); //phpcs:ignore

			return $categories;
		}
		return false;
	}

	/**
	 * Total tags.
	 *
	 * @return Tags.
	 */
	protected function tags_found() {
		global $wpdb;
		if ( $this->table_exit( $wpdb->prefix . 'woostify_tag_index' ) ) {
			$sql  = $this->tags_query_string();
			$tags = $wpdb->get_results( $sql ); //phpcs:ignore

			return $tags;
		}

		return false;

	}

	/**
	 * Total Attribute.
	 *
	 * @return Attribute.
	 */
	protected function attribute_found() {
		global $wpdb;
		if ( $this->table_exit( $wpdb->prefix . 'woostify_attribute_index' ) ) {
			$sql       = $this->attribte_query_string();
			$attribute = $wpdb->get_results( $sql ); //phpcs:ignore
			return $attribute;
		}

		return false;
	}

	/**
	 * Total product.
	 *
	 * @return Product.
	 */
	protected function product_found() {
		global $wpdb;
		$keyword = $this->keyword;
		$length  = count( explode( ' ', $keyword ) );
		$table   = $wpdb->prefix . 'woostify_product_index';
		$sql     = '';
		if ( 1 == $length ) { //phpcs:ignore
			$sql .= 'SELECT * FROM (';
		}
		$sql .= $this->query_string();
		if ( 1 == $length ) { //phpcs:ignore
			$sql .= ") $table";
		}
		$sql          .= $this->orderby();
		$total_product = $this->total_product;
		if ( -1 != $total_product ) { //phpcs:ignore
			$sql .= " LIMIT $total_product";
		}
		$products = $wpdb->get_results( $sql ); //phpcs:ignore

		return $products;
	}

	/**
	 * Total product result.
	 *
	 * @return Product with seting.
	 */
	protected function result_product() {
		$products = $this->product_found();
		if ( ! empty( $products ) ) {
			foreach ( $products as $product ) {
				$name                    = $this->highlight( $product->name );
				$product->name_hightline = $name;
				$product->sku_hightline  = $product->sku;
				$price_default           = $product->price;
				if ( $this->search_by_sku ) {
					$product->sku_hightline = $this->highlight( $product->sku );
				}

				if ( $this->get_meta_dynamic( $product->id ) ) {
					$price = $this->get_price( $product );
					if ( $price ) {
						$product->html_price = $this->price_html( $price_default, $price );
					}
				}
				if ( $this->get_wpml_custom_price( $product->id, $this->get_curency() ) ) {
					$price               = $this->get_wpml_custom_price( $product->id, $this->get_curency() );
					$sale_price          = $this->get_wpml_custom_price_sale( $product->id, $this->get_curency() );
					$product->html_price = $this->price_default_html( $price, $sale_price );
				}
				if ( strpos( $this->search_custom_field, '_price' ) !== false ) {
					$product->html_price = $this->highlight( $product->html_price );
				}
			}
		}

		return $products;
	}

	/**
	 * Total product result.
	 *
	 * @return Product with seting.
	 */
	protected function result_categories() {
		$categories = $this->categories_found();
		if ( $categories && ! empty( $categories ) ) {
			foreach ( $categories as $category ) {
				$name                     = $this->highlight( $category->name );
				$category->name_hightline = $name;
			}
		}

		return $categories;
	}

	/**
	 * Total tags result.
	 *
	 * @return Tags with seting.
	 */
	protected function result_tags() {
		$tags = $this->tags_found();
		if ( $tags && ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				$name                = $this->highlight( $tag->name );
				$tag->name_hightline = $name;
			}
		}

		return $tags;
	}

	/**
	 * Total attribute result.
	 *
	 * @return Attribute with seting.
	 */
	protected function result_attributes() {
		$attribute = $this->attribute_found();
		if ( $attribute && ! empty( $attribute ) ) {
			foreach ( $attribute as $attr ) {
				$name                 = $this->highlight( $attr->name );
				$attr->name_hightline = $name . '( ' . $attr->group_name . ')';
			}
		}

		return $attribute;
	}

	/**
	 * Result Data.
	 */
	public function result() {
		$data = array(
			'product_found' => $this->total_product(),
			'products'      => $this->result_product(),
			'categories'    => false,
		);

		if ( $this->search_category ) {
			$data['categories'] = $this->result_categories();
		}

		if ( $this->search_tag ) {
			$data['tags'] = $this->result_tags();
		}
		if ( $this->search_attribute ) {
			$data['attributes'] = $this->result_attributes();
		}

		return $data;
	}

	/**
	 * Result Data.
	 */
	public function check_rule() {
		global $wpdb;
		$sql     = "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_pricing_rules'"; //phpcs:ignore

		return count( $wpdb->get_results( $sql ) ); //phpcs:ignore
	}

	/**
	 * Result Data.
	 *
	 * @param (int) $product_id | Product Id.
	 */
	public function get_meta_dynamic( $product_id ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_pricing_rules' AND post_id = '$product_id'"; //phpcs:ignore
		$meta_dynamic = $wpdb->get_results( $sql ); //phpcs:ignore
		if ( $meta_dynamic ) {
			return $meta_dynamic[0]->meta_value;
		}

		return false;
	}

	/**
	 * Get price for dynamic price plugin.
	 *
	 * @param (array) $product | Search data.
	 */
	public function get_price( $product ) {

		if ( $_SESSION['wc_dynamic_pricing'] && $this->check_rule() > 0 ) {
			$user  = $_SESSION['user'];
			$roles = $user['roles'];
			if ( empty( $roles ) ) {
				$roles = array(
					'unauthenticated',
				);
			}
			$prices = array();

			foreach ( unserialize( $this->get_meta_dynamic( $product->id ) ) as $key => $rule ) { //phpcs:ignore
				$start = $rule['date_from'];
				$end   = $rule['date_to'];

				if ( 'product' == $rule['collector']['type'] && $this->check_date( $start, $end ) ) { //phpcs:ignore

					$conditions      = $rule['conditions'][1];
					$type            = $conditions['type'];
					$args            = $conditions['args'];
					$default_price   = $product->price;
					$price           = false;
					$product_type    = $product->type;
					$max_price       = $product->max_price;
					$variation_rules = '';
					$parent_id       = $args['memberships'];
					if ( array_key_exists( 'memberships', $args ) ) {
						$parent_id = $args['memberships'];
					}
					if ( 'variable' == $product_type ) { //phpcs:ignore
						$variation_rules = $rule['variation_rules']['args']['type'];
					}
					$check_role = array_intersect( $args['roles'], $roles );
					if ( ( 'roles' == $args['applies_to'] && ! empty( $check_role ) ) || 'everyone' == $args['applies_to'] || in_array($args['applies_to'], $roles) || ( 'membership' == $args['applies_to'] && $this->check_user_role( $parent_id ) ) ) { //phpcs:ignore
						$current_rule = $this->check_item( $rule['rules'] );
						if ( $current_rule ) {
							$price_type   = $current_rule['type'];
							$price_amount = $current_rule['amount'];
							$price        = $this->caculater( $default_price, $price_type, $price_amount, $max_price, $variation_rules );
						}
					}

					$prices[] = $price;
				}
			}
			return max( $prices );
		}

		return false;
	}

	/**
	 * Check member role.
	 *
	 * @param (int) $parent_id | Id member role.
	 */
	public function check_user_role( $parent_id ) {
		if ( empty( $parent_id ) ) {
			return false;
		}
		$user   = $_SESSION['user'];
		$userid = $user['id'];
		global $wpdb;
		$parent_id = implode( ',', $parent_id );
		$sql     = "SELECT * FROM {$wpdb->prefix}posts WHERE post_author = $userid AND post_type ='wc_user_membership' AND post_parent IN ( $parent_id )"; //phpcs:ignore

		$user = $wpdb->get_results( $sql ); //phpcs:ignore

		if ( ! empty( $user ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Caculater price for dynamic price plugin.
	 *
	 * @param (float)  $price | product price.
	 * @param (string) $type | type caculater  price.
	 * @param (float)  $amount | discount product price.
	 * @param (float)  $max_price | max price product variable price.
	 * @param (string) $variation_rules | rule variable.
	 */
	public function caculater( $price, $type, $amount, $max_price = 0, $variation_rules = '' ) {
		$price_discount = $price;
		switch ( $type ) {
			case 'price_discount':
				if ( $price > $amount ) {
					if ( 0 == $max_price ) { // phpcs:ignore
						$price_discount = $price - $amount;
					} else {
						$min = $price - $amount;
						$max = $max_price - $amount;
						if ( 'variations' == $variation_rules ) { // phpcs:ignore
							$max = $max_price;
						}
						$price_discount = array(
							'min' => $min,
							'max' => $max,
						);
					}
				}
				break;

			case 'percentage_discount':
				if ( 0 != $amount ) { //phpcs:ignore
					if ( 0 == $max_price ) { // phpcs:ignore
						$price_discount = $price - ( $price / $amount );
					} else {
						$min = $price - ( $price / $amount );
						$max = $max_price - ( $max_price / $amount );
						if ( 'variations' == $variation_rules ) { // phpcs:ignore
							$max = $max_price;
						}

						$price_discount = array(
							'min' => $min,
							'max' => $max,
						);
					}
				}
				break;

			case 'fixed_price':
				$price_discount = $amount;
				break;

			default:
				$price_discount = $price;
				break;
		}

		return $price_discount;
	}


	/**
	 * Check rule for 1 product add cart.
	 *
	 * @param (float) $rules | list dynamic rule.
	 */
	public function check_item( $rules ) {
		$item = array();
		foreach ( $rules as $key => $rule ) {
			$item[$key] = $rule['from']; //phpcs:ignore
		}
		$key = array_search( min( $item ), $item ); //phpcs:ignore
		if ( min( $item ) <= 1 ) { //phpcs:ignore
			return $rules[ $key ]; //phpcs:ignore
		}

		return false;
	}

	/**
	 * Check rule for 1 product add cart.
	 *
	 * @param (string) $start | start date.
	 *
	 * @param (string) $end | end date.
	 */
	public function check_date( $start, $end ) {

		if ( empty( $end ) && empty( $start ) ) {
			return true;
		}
		$today = date( 'Y-m-d' ); //phpcs:ignore
		$today = strtotime( $today );
		$start = strtotime( $start );
		$end   = strtotime( $end );

		if ( $start && 0 > ( $today - $start ) ) {
			return false;
		}

		if ( $end && 0 > ( $end - $today ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Price for dynamic.
	 *
	 * @param (float) $price | product price.
	 *
	 * @param (float) $max_price | product discount.
	 */
	public function price_default_html( $price, $max_price = 0 ) {
		$symbol       = $this->get_curency_symbol();
		$symbol_pos   = $_SESSION['currency_pos'];
		$symbol_pos   = get_option( 'woocommerce_currency_pos' );
		$symbol_left  = '';
		$symbol_right = '';
		switch ( $symbol_pos ) {
			case 'left':
				$symbol_left = '<span class="woocommerce-Price-currencySymbol">' . $symbol . '</span>';
				break;

			case 'right':
				$symbol_right = '<span class="woocommerce-Price-currencySymbol">' . $symbol . '</span>';
				break;

			case 'left_space':
				$symbol_left = '<span class="woocommerce-Price-currencySymbol"> ' . $symbol . '</span>';
				break;

			case 'right_space':
				$symbol_right = '<span class="woocommerce-Price-currencySymbol">' . $symbol . '</span> ';
				break;

			default:
				$symbol_left = '<span class="woocommerce-Price-currencySymbol">' . $symbol . '</span>';
				break;
		}
		if ( 0 != $max_price ) { //phpcs:ignore
			$html = '<del><span class="woocommerce-Price-amount amount">
				<bdi>' .
					$symbol_left . esc_html( $price ) . $symbol_right .
				'</bdi>
			</span></del>
			<ins><span class="woocommerce-Price-amount amount">
				<bdi>' .
					$symbol_left . esc_html( $max_price ) . $symbol_right .
				'</bdi>
			</span></ins>';
		} else {

			$html = '<span class="woocommerce-Price-amount amount">
				<bdi>' .
					$symbol_left . esc_html( $price ) . $symbol_right .
				'</bdi>
			</span>';

		}

		return $html;
	}


	/**
	 * Price for dynamic.
	 *
	 * @param (float) $price | product price.
	 *
	 * @param (float) $price_discount | product discount.
	 */
	public function price_html( $price, $price_discount ) {
		$symbol       = $this->get_curency_symbol();
		$symbol_pos   = $_SESSION['currency_pos'];
		$symbol_pos   = get_option( 'woocommerce_currency_pos' );
		$symbol_left  = '';
		$symbol_right = '';
		switch ( $symbol_pos ) {
			case 'left':
				$symbol_left = '<span class="woocommerce-Price-currencySymbol">' . $symbol . '</span>';
				break;

			case 'right':
				$symbol_right = '<span class="woocommerce-Price-currencySymbol">' . $symbol . '</span>';
				break;

			case 'left_space':
				$symbol_left = '<span class="woocommerce-Price-currencySymbol"> ' . $symbol . '</span>';
				break;

			case 'right_space':
				$symbol_right = '<span class="woocommerce-Price-currencySymbol"> ' . $symbol . '</span> ';
				break;

			default:
				$symbol_left = '<span class="woocommerce-Price-currencySymbol">' . $symbol . '</span>';
				break;
		}

		if ( is_string( $price_discount ) ) { //phpcs:ignore
			$html = '<del><span class="woocommerce-Price-amount amount">
				<bdi>' .
					$symbol_left . esc_html( $price ) . $symbol_right .
				'</bdi>
			</span></del>
			<ins><span class="woocommerce-Price-amount amount">
					<bdi>' .
					$symbol_left . esc_html( $price_discount ) . $symbol_right .
					'</bdi>
				</span></ins>';
		} else {
			if ( $price_discount['min'] != $price_discount['max'] ) { //phpcs:ignore
				$html = '<span class="woocommerce-Price-amount amount">
					<bdi>' .
					$symbol_left . esc_html( $price_discount['min'] ) . $symbol_right .
					'</bdi>
				</span>'
				. ' - ' .
				'<span class="woocommerce-Price-amount amount">
					<bdi>' .
					$symbol_left . esc_html( $price_discount['max'] ) . $symbol_right .
					'</bdi>
				</span>';
			} else {
				$html = '<span class="woocommerce-Price-amount amount">
					<bdi>' .
						$symbol_left . esc_html( $price_discount['max'] ) . $symbol_right .
					'</bdi>
				</span>';
			}
		}

		return $html;
	}

	/**
	 * Get price default when use dynamic.
	 *
	 * @param (int) $product_id | product id.
	 */
	public function get_price_default( $product_id ) {
		global $wpdb;
		$sql   = "SELECT * FROM {$wpdb->prefix}postmeta WHERE post_id = $product_id AND meta_key ='_regular_price'"; //phpcs:ignore
		$price = $wpdb->get_results( $sql ); //phpcs:ignore

		if ( ! empty( $price ) ) {
			return $price[0]->meta_value;
		}
		return 0;
	}

	/**
	 * Get curency symbol.
	 */
	public function get_curency_symbol() {
		if ( is_string( $_SESSION['currency_symbol'] ) ) {
			return $_SESSION['currency_symbol'];
		}

		return $_SESSION['currency_symbol'][ $this->lang ];
	}

	/**
	 * Get curency symbol.
	 */
	public function get_curency() {
		if ( is_string( $_SESSION['currency_code'] ) ) {
			return $_SESSION['currency_code'];
		}

		return $_SESSION['currency_code'][ $this->lang ];
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
	 */
	public function highlight( $string ) {
		$str     = html_entity_decode( trim( $string ) );
		$string  = $str;
		$keyword = wp_specialchars_decode( trim( $this->keyword ) );
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
	 * Get price default when use dynamic.
	 *
	 * @param (int)    $post_id | product id.
	 * @param (string) $currency | currency code.
	 */
	public function get_wpml_custom_price( $post_id, $currency ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = '%d' AND meta_key = '%s'", $post_id, '_regular_price_' . $currency );
		$res = $wpdb->get_var( $sql );

		if ( $res ) {
			return $res;
		}

		return false;
	}

	/**
	 * Get price default when use dynamic.
	 *
	 * @param (int)    $post_id | product id.
	 * @param (string) $currency | currency code.
	 */
	public function get_wpml_custom_price_sale( $post_id, $currency ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = '%d' AND meta_key = '%s'", $post_id, '_sale_price_' . $currency );
		$res = $wpdb->get_var( $sql );

		if ( $res ) {
			return $res;
		}

		return false;
	}

	/**
	 * Get price default when use dynamic.
	 */
	public function wpml_option() {
		global $wpdb;
		$sql   = "SELECT * FROM {$wpdb->prefix}options WHERE option_name = '_wcml_settings' AND autoload ='yes'"; //phpcs:ignore
		$options = $wpdb->get_results( $sql ); //phpcs:ignore
		if ( $options ) {
			$options = $options[0]->option_value;
			$option  = unserialize( $options ); //phpcs:ignore
			return $option;
		}

		return false;
	}

	/**
	 * Check table exit.
	 *
	 * @param (string) $table_name | product id.
	 */
	public function table_exit( $table_name ) {
		global $wpdb;
		return $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ); //phpcs:ignore
	}
}
