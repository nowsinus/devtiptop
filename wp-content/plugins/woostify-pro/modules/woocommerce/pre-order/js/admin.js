/**
 * Pre Order Admin
 *
 * @package Woostify Pro
 */

/* global woostify_pre_order_admin */

'use strict';

var woostifyData = woostify_pre_order_admin || {};

document.addEventListener(
	'DOMContentLoaded',
	function () {
		var wp = window.wp,
			body = jQuery('body');
		var datepicker_element = document.querySelector('.datepicker');
		if (datepicker_element) {
			TinyDatePicker('.datepicker');

		}

		body.on(
			'change',
			'#_stock_status',
			function (event) {
				event.preventDefault();
				var option_stock_status = jQuery(this).val();
				console.log(option_stock_status);
				if ('onpreorder' === option_stock_status) {
					jQuery('.options_group.options_group_preorder').removeClass('hidden');
				} else {
					jQuery('.options_group.options_group_preorder').addClass('hidden');
				}
			}
		);

	}
);
