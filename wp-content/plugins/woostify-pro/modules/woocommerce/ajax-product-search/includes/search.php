<?php
/**
 * WordPress Ajax Process Execution
 *
 * @package WordPress
 * @subpackage Administration
 *
 * @link https://codex.wordpress.org/AJAX_in_Plugins
 */

/**
 * Executing Ajax process.
 *
 * @since 2.1.0
 */
define( 'DOING_AJAX', true );
define( 'SHORTINIT', true );
define( 'WOOSTIFY_DIR', dirname( dirname( dirname( dirname( dirname( dirname( dirname( dirname( $_SERVER['SCRIPT_FILENAME'] ) ) ) ) ) ) ) ) ); //phpcs:ignore

ini_set( 'html_errors', 0 ); //phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	$wp_load_file = 'wp-load.php';
	$dir          = '../../../../';
	$max_depth    = 5;

	while ( $max_depth > 0 ) {
		$wp_load = $dir . $wp_load_file;

		if ( file_exists( $wp_load ) ) {
			require_once $wp_load;
			break;
		} else {

			$alternative_paths = array(
				'wp', // Support for Bedrock by Roots - https://roots.io/bedrock.
				'.wordpress', // Support for Flywheel hosting - https://getflywheel.com.
			);

			foreach ( $alternative_paths as $alternative_path ) {

				$bedrock_abs_path = str_replace( 'wp-load.php', $alternative_path . '/wp-load.php', $wp_load );

				if ( file_exists( $bedrock_abs_path ) ) {
					require_once $bedrock_abs_path;
					break;
				}
			}
		}
		$dir .= '../';
		$max_depth --;
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	require_once WOOSTIFY_DIR . '/wp-load.php';
}

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'ABSPATH is not defined' );
}

// Require an action parameter.
if ( empty( $_GET['token'] ) ) { // phpcs:ignore
	wp_die( '0', 400 );
}

global $wpdb;

$charset     = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'blog_charset'" ); //phpcs:ignore
$charset_com = ! empty( $charset ) ? '; charset=' . $charset : '';
require ABSPATH . '/wp-load.php';
require_once ABSPATH . WPINC . '/class-http.php';
require_once ABSPATH . WPINC . '/class-wp-http-streams.php';
require_once ABSPATH . WPINC . '/class-wp-http-curl.php';
require_once ABSPATH . WPINC . '/class-wp-http-proxy.php';
require_once ABSPATH . WPINC . '/class-wp-http-cookie.php';
require_once ABSPATH . WPINC . '/class-wp-http-encoding.php';
require_once ABSPATH . WPINC . '/class-wp-http-response.php';
require_once ABSPATH . WPINC . '/class-wp-http-requests-response.php';
require_once ABSPATH . WPINC . '/class-wp-http-requests-hooks.php';
require_once ABSPATH . WPINC . '/formatting.php';
require_once ABSPATH . WPINC . '/l10n.php';
require_once ABSPATH . WPINC . '/http.php';
require_once ABSPATH . WPINC . '/plugin.php';
require_once ABSPATH . WPINC . '/theme.php';


if ( get_http_origin() === null ) {
	require_once ABSPATH . WPINC . '/link-template.php';
}
send_origin_headers();
@header( 'Content-Type: application/json' . $charset_com ); //phpcs:ignore
@header( 'X-Robots-Tag: noindex' ); //phpcs:ignore
send_nosniff_header();
nocache_headers();

// Default status.
$response = array();

if ( ! isset( $_GET['keyword'] ) ) { //phpcs:ignore
	wp_send_json_error();
}
session_start();
require 'class-woostify-query.php';
require 'core.php';

$keyword         = sanitize_text_field( wp_unslash( $_GET['keyword'] ) ); //phpcs:ignore
$cat_id          = ( isset( $_GET['cat_id'] ) && ! empty( $_GET['cat_id'] ) ) ? sanitize_text_field( wp_unslash( $_GET['cat_id'] ) ) : false; //phpcs:ignore
$lang            = ( isset( $_GET['lang'] ) && ! empty( $_GET['lang'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : false; //phpcs:ignore

$meta_key        = ( isset( $_GET['lang'] ) && ! empty( $_GET['lang'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : false; //phpcs:ignore

$out_stock         = get_option( 'woostify_ajax_search_product_remove_out_stock_product', '0' );
$total_product     = (int) get_option( 'woostify_ajax_search_product_total', '-1' );
$search_by_title   = get_option( 'woostify_ajax_search_product_by_title', '1' );
$search_by_sku     = get_option( 'woostify_ajax_search_product_by_sku', '1' );
$search_category   = get_option( 'woostify_ajax_search_product_search_category', '0' );
$search_tag        = get_option( 'woostify_ajax_search_product_search_tag', '0' );
$search_attribute  = get_option( 'woostify_ajax_search_product_attribute', '0' );
$description       = get_option( 'woostify_ajax_search_product_by_description', '0' );
$short_description = get_option( 'woostify_ajax_search_product_by_short_description', '0' );
$custom_fields     = get_option( 'woostify_ajax_search_product_custom_field', '' );
$args              = array(
	'keyword'             => trim( $keyword ),
	'cat_id'              => $cat_id,
	'total_product'       => $total_product,
	'sku'                 => $search_by_sku,
	'title'               => $search_by_title,
	'lang'                => $lang,
	'outstock'            => $out_stock,
	'search_category'     => $search_category,
	'search_tag'          => $search_tag,
	'search_attribute'    => $search_attribute,
	'description'         => $description,
	'short_description'   => $short_description,
	'search_custom_field' => $custom_fields,
);
$test              = new Woostify\Woocommerce\Woostify_Query( $args );

$data = $test->result();

wp_send_json_success( $data );
