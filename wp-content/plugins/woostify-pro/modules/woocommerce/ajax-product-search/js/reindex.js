/**
 * Index search
 *
 * @package Woostify
 */

'use strict';

(function ($) {

	'use strict';
	var args = ['attribute', 'category', 'tag', 'custom-fields', 'products'];
	// Show Auto Complete display.
	$('body').on(
		'click',
		'.btn-index-data',
		function (e) {
			var btn = $(this);
			var i = 1;
			btn.prop('disabled', true);
			var data = {
				action: 'index_data',
				_ajax_nonce: admin.nonce,
			};

			$.ajax(
				{
					type: 'GET',
					url: admin.url,
					data: data,
					beforeSend: function (response) {
						$('.index-data .progress').addClass('loading');
						btn.text('Start Index Data...');
					},
					success: function (response) {

						if (response.data) {
							$('.last-index').text(response.data.time);
							$('.index-total-product').text(response.data.total_product);
							if (response.data.index_size > 1) {
								var index_size = response.data.index_size;
								setTimeout(function () {
									index_product(i, btn, index_size);
								}, 3000)
							} else {
								btn.prop('disabled', false);
								$('.index-data .progress').removeClass('loading');
								btn.text('Index Data Success');
							}
						} else {
							btn.prop('disabled', false);
							btn.text('Try Again');
							$('.index-data .progress').removeClass('loading');
						}
					},
				}
			);
		}
	);

	function index_product(counter, btn, index_size) {
		var progressbar = btn.parent().siblings('.woostify-progress-bar');
		if (progressbar.length) {
			progressbar.slideDown(300);
			var progress = parseInt(counter * 100 / index_size);
			progressbar.find('.woostify-progress-bar-value').html(progress + '%').css('width', progress + '%');
		}
		var data = {
			action: 'index_data_next',
			index: counter,
			_ajax_nonce: admin.nonce,
		};
		$.ajax(
			{
				type: 'GET',
				url: admin.url,
				data: data,
				success: function (response) {
					if (response.data) {
						$('.last-index').text(response.data.time);
						$('.index-total-product').text(response.data.total_product);
						if ((counter + 1) <= response.data.index_size) {
							counter++;
							var index_size = response.data.index_size;
							setTimeout(function () {
								index_product(counter, btn, index_size);
							}, 3000)

						} else {
							btn.prop('disabled', false);
							$('.index-data .progress').removeClass('loading');
							btn.text('Index Data Success');
							progressbar.slideUp();
						}
					} else {
						btn.prop('disabled', false);
						btn.text('Try Again');
						$('.index-data .progress').removeClass('loading');
					}
				},
			}
		);
	}

	$('.btn-close-notice').on(
		'click',
		function (e) {
			$(this).parents('.woostify-notice').slideUp();
		}
	);

	$('.woostify-custom-field-control').on(
		'click',
		function (e) {
			$('.custom-fields').slideDown(500);
			var input = $(this).find('#woostify_add_custom_field');
			$(input, this).focus();
		}
	);

	$(document).on(
		'click',
		'.field-item',
		function (e) {
			var value = $(this).data('value'),
				inputListField = $('#woostify_ajax_search_product_custom_field'),
				listField = inputListField.val(),
				input = $(this).parents('.custom-field-wrapper').find('#woostify_add_custom_field'),
				html = '<span class="woostify-auto-complete-key">' +
					'<span class   ="woostify-title">' + value + '</span>' +
					'<span class="btn-woostify-auto-complete-delete ion-close" data-item="' + value + '"></span>'
			'</span>';

			if (!listField) {
				listField = [];
			} else {
				listField = listField.split(',');
			}
			input.val('');
			if (!listField.includes(value)) {
				input.before(html);
				listField.push(value);
				inputListField.val(listField);
			}

		}
	);

	function find_custom_field() {
		var key = $('#woostify_add_custom_field').val();
		var data = {
			action: 'find_custom_field',
			_ajax_nonce: admin.nonce,
			key: key,
		};
		$.ajax(
			{
				type: 'GET',
				url: admin.url,
				data: data,
				beforeSend: function (response) {
					$('.custom-fields').html(response);
				},
				success: function (response) {
					$('.custom-fields').html(response);
				},
			}
		);
	};

	$('#woostify_add_custom_field').on(
		'keyup',
		function (e) {
			setTimeout(
				function () {
					find_custom_field();
				},
				500
			);
		}
	);

	$(document).on(
		'click',
		'.btn-woostify-auto-complete-delete',
		function (e) {
			e.preventDefault();
			var inputListField = $('#woostify_ajax_search_product_custom_field'),
				value = $(this).data('item'),
				listField = inputListField.val();
			if (!listField) {
				listField = [];
			} else {
				listField = listField.replace(',' + value, '');
				listField = listField.replace(value, '');
				listField = listField.replace(/(^[,\s]+)|([,\s]+$)/g, '');
			}
			inputListField.val(listField);
			$(this).parent().remove();
		}
	);

})(jQuery);
