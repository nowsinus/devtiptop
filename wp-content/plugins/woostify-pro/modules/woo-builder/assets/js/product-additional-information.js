/**
 * Elementor additional information
 *
 * @package Woostify Pro
 */

 'use strict';

document.addEventListener(
	'DOMContentLoaded',
	function() {
		jQuery( document.body ).on(
			'found_variation',
			function( event, variation ) {
				var $weight = jQuery(
					'.product_weight, .woocommerce-product-attributes-item--weight .woocommerce-product-attributes-item__value'
				),
				$dimensions = jQuery(
					'.product_dimensions, .woocommerce-product-attributes-item--dimensions .woocommerce-product-attributes-item__value'
				);
				if ( variation.weight ) {
					$weight.wc_set_content( variation.weight_html );
				} else {
					$weight.wc_reset_content();
				}
				if ( variation.dimensions ) {
					// Decode HTML entities.
					$dimensions.wc_set_content( jQuery.parseHTML( variation.dimensions_html )[0].data );
				} else {
					$dimensions.wc_reset_content();
				}
			}
		);

		jQuery( '.reset_variations' ).on(
			'click',
			function() {
				var $weight = jQuery(
					'.product_weight, .woocommerce-product-attributes-item--weight .woocommerce-product-attributes-item__value'
				),
				$dimensions = jQuery(
					'.product_dimensions, .woocommerce-product-attributes-item--dimensions .woocommerce-product-attributes-item__value'
				);
				$weight.wc_reset_content();
				$dimensions.wc_reset_content();
			}
		);
	}
);
