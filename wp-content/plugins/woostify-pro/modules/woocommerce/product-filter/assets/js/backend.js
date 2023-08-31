/**
 * Product filter
 *
 * @package Woostify Pro
 */

/* global woostify_product_filter */

'use strict';

// Check range.
const woostifyRangeValue = function () {
	let list = document.querySelector('.w-filter-check-range');
	if (!list) {
		return;
	}

	const removeItem = function () {
		let item = list.querySelectorAll('.w-filter-item-pack');
		if (!item.length) {
			return;
		}

		item.forEach(
			function (i) {
				let removeButton = i.querySelector('.w-filter-range-item-remove');

				if (!removeButton) {
					return;
				}

				removeButton.onclick = function () {
					i.remove();
				}
			}
		);
	}

	const addItem = function () {
		let itemWrap = list.querySelector('.w-filter-container-inner'),
			addButton = list.querySelector('.w-filter-range-item-add');
		if (!itemWrap || !addButton) {
			return;
		}

		addButton.onclick = function () {
			itemWrap.insertAdjacentHTML('beforeend', woostify_product_filter.item_node);
			removeItem();
		}
	}

	removeItem();
	addItem();
}

// Value must not empty.
const woostifyUpdateAttr = function () {
	let type = document.querySelector('[name="woostify_product_filter_type"]');
	if (!type) {
		return;
	}

	let table = type.closest('.admin-product-filter');

	type.addEventListener(
		'change',
		function () {
			let source = document.querySelector('[name="woostify_product_filter_data"]'),
				required = document.querySelectorAll('.w-filter-check-range .w-filter-required');

			table.setAttribute('data-type', type.value);

			// Required field.
			if (required.length) {
				if ('check-range' === type.value) {
					required.forEach(
						function (ele) {
							ele.setAttribute('required', 'required');
						}
					);
				} else {
					required.forEach(
						function (ele) {
							ele.removeAttribute('required');

							if (!ele.value.trim()) {
								ele.closest('.w-filter-range-item').remove();
							}
						}
					);
				}
			}

			// Update product attr.
			if ('visual' === type.value && source) {
				source.classList.add('product-attr-only');

				let isProductAttr = source.querySelector('[value=' + source.value + ']');
				if (isProductAttr && isProductAttr.classList.contains('product-taxonomy')) {
					source.value = '';
				}
			} else if ('stock' === type.value && source) {
				source.classList.add('stock');
				source.classList.remove('product-attr-only');
			} else {
				source.classList.remove('product-attr-only');
				source.classList.remove('stock');
			}
		}
	);
}

// Toggle value '0' and '1' on checkbox input.
const woostifyToggleCheckboxValue = function () {
	let checkbox = document.querySelectorAll('.woostify-filter-item [type="checkbox"]');

	if (!checkbox.length) {
		return;
	}

	checkbox.forEach(
		function (el) {
			let func = function () {
				let name = el.name,
					val = el.value,
					con = name + ':' + val,
					req = document.querySelectorAll('[data-require]');

				if (!req.length) {
					return
				}

				for (let i = 0, j = req.length; i < j; i++) {
					let reqe = req[i].getAttribute('data-require');
					if (!reqe.includes(name)) {
						continue;
					}

					if (reqe === con) {
						req[i].classList.remove('hidden');
					} else {
						req[i].classList.add('hidden');
					}
				}
			}
			func();

			el.addEventListener(
				'change',
				function () {
					el.value = '1' == el.value ? '0' : '1';

					func();
				}
			);
		}
	);
}

// For product hierarchical data.
const woostifyProductCatData = function () {
	let filter = document.querySelector('[name="woostify_product_filter_data"]'),
		data = document.querySelector('.w-filter-hierarchical-data');
	if (!filter || !data) {
		return;
	}

	const init = function () {
		if ('product_cat' == filter.value) {
			data.classList.remove('hidden');
		} else {
			data.classList.add('hidden');
		}
	}

	// Init.
	init();
	filter.onchange = init;

	// Denpendency toggle.
	let includeChild = data.querySelector('#w-filter-source-term-hierarchical'),
		expandChild = data.querySelector('#w-filter-source-term-hierarchical-expand'),
		softLimit = document.querySelector('.soft-limit');

	if (includeChild && expandChild) {
		includeChild.addEventListener(
			'change',
			function () {
				let expandParent = expandChild.closest('.w-filter-field');

				if ('0' == includeChild.value) {
					expandParent.classList.add('hidden');

					if (softLimit) {
						softLimit.classList.remove('soft-limit-hidden');
					}
				} else {
					expandParent.classList.remove('hidden');

					if (softLimit) {
						softLimit.classList.add('soft-limit-hidden');
					}
				}
			}
		);
	}
}

// For product hierarchical data.
const woostifyProductStock = function () {
	let filter = document.querySelector('[name="woostify_product_filter_type"]'),
		data = document.querySelector('[name="woostify_product_filter_data"]');
	if (!filter || !data) {
		return;
	}
	const init = function () {
		if ('checkbox' == filter.value) {
			data.classList.remove('hidden');
		} else {
			data.classList.add('hidden');
		}
	}

	// Init.
	init();
	filter.onchange = init;
}

// For product stock data.
const woostifyProductStockType = function () {
	let filter = document.querySelector('[name="woostify_product_filter_type"]'),
		data = document.querySelector('.w-filter-stock-data'),
		stock = document.querySelector('.soft-limit');
	if (!filter || !data) {
		return;
	}
	const init = function () {
		if ('stock' == filter.value) {
			data.classList.remove('hidden');
			stock.classList.add('hidden');
		} else {
			data.classList.add('hidden');
			stock.classList.remove('hidden');
		}
	}

	// Init.
	init();
	filter.onchange = init;
}

// Term condition data.
const woostifyTermConditionData = function () {
	let condition = document.querySelector('.w-filter-condition-data'),
		select = condition ? condition.querySelector('.w-filter-condition-select') : false,
		field = condition ? condition.querySelector('.w-filter-condition-field') : false;
	if (!select || !field) {
		return;
	}

	const init = function () {
		if (!select.value) {
			field.classList.add('hidden');
		} else {
			field.classList.remove('hidden');
		}
	}

	// Init.
	init();
	condition.onchange = init;
}

const woostifyFieldShowOn = function (selector) {
	let fields = document.querySelectorAll(selector),
		type = document.querySelector('[name="woostify_product_filter_type"]');
	if (!fields.length) {
		return;
	}

	if (!type || ('stock' == type.value)) {
		return;
	}

	fields.forEach(
		function (el) {
			let showon = el.getAttribute('showon');
			let showon_args = showon.split(':');
			let showon_field_name = showon_args[0];
			let is_equal = true;
			if (typeof showon_args[0].split('!')[1] !== 'undefined') {
				is_equal = false;
			}

			if (!is_equal) {
				showon_field_name = showon_args[0].split('!')[0];
			}
			let showon_field_val = showon_args[1];

			let parent_field = document.getElementsByName(showon_field_name)[0];
			let parent_field_val = parent_field.value;

			if (!is_equal) {
				if (parent_field_val !== showon_field_val) {
					el.classList.remove('hidden');
				} else {
					el.classList.add('hidden');
				}

				parent_field.addEventListener(
					'change',
					function (e) {
						let curr_val = e.target.value;
						if (curr_val === showon_field_val) {
							el.classList.add('hidden');
						} else {
							el.classList.remove('hidden');
						}
					}
				);

				return false;
			}

			if (parent_field_val === showon_field_val) {
				el.classList.remove('hidden');
			} else {
				el.classList.add('hidden');
			}

			parent_field.addEventListener(
				'change',
				function (e) {
					let curr_val = e.target.value;
					if (curr_val === showon_field_val) {
						el.classList.add('hidden');
					} else {
						el.classList.remove('hidden');
					}
				}
			);

			return false;
		}
	);
}

// Sort order.
const woostifySortableOrder = function () {
	let selector = document.querySelector('.woostify-multi-selection-inner'),
		parent = selector ? selector.closest('.woostify-multi-selection') : false,
		input = parent ? parent.querySelector('.woostify-multi-select-value') : false;

	if (!selector) {
		return;
	}

	let sortable = new Sortable(selector);

	document.addEventListener(
		'filterOrder',
		function () {
			let data = [],
				list = selector.querySelectorAll('.woostify-multi-select-id');

			list.forEach(
				function (ele) {
					let saveId = ele.getAttribute('data-id');

					if (!saveId || data.includes(saveId)) {
						return;
					}

					data.push(saveId);
				}
			);

			input.value = data.join('|');
		}
	);
}

// Index database.
const woostifyIndexDatabase = function () {
	let indexBtn = document.querySelector('.filter-index-button');
	if (!indexBtn) {
		return;
	}

	indexBtn.onclick = function () {
		let table = indexBtn.closest('.form-table'),
			lastIndex = table ? table.querySelector('.last-index') : false,
			indexCount = table ? table.querySelector('.index-count') : false;

		if (
			!lastIndex ||
			!indexCount ||
			indexBtn.getAttribute('disabled') ||
			indexBtn.parentNode.classList.contains('loading')
		) {
			return;
		}

		// Animation.
		indexBtn.parentNode.classList.add('loading');
		indexBtn.setAttribute('disabled', 'disabled');
		indexBtn.innerHTML = woostify_product_filter.indexing_text + '...';

		// Data.
		let data = {
			action: 'woostify_index_filter',
			ajax_nonce: woostify_product_filter.ajax_nonce
		};

		data = new URLSearchParams(data).toString();

		// Request.
		let request = new Request(
			ajaxurl,
			{
				method: 'POST',
				body: data,
				credentials: 'same-origin',
				headers: new Headers(
					{
						'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
					}
				)
			}
		);

		// Fetch API.
		fetch(request)
			.then(
				function (res) {
					if (200 !== res.status) {
						console.log('Status Code: ' + res.status);
						throw res;
					}

					return res.json();
				}
			).then(
				function (json) {
					if (!json.success) {
						return;
					}

					let data = json.data;

					// Set indexed status.
					indexBtn.innerHTML = woostify_product_filter.indexed_text;
					indexBtn.insertAdjacentHTML('afterend', '<span class="info-seuccess">' + woostify_product_filter.indexed_success + '</span>');

					// Remove animation.
					indexBtn.parentNode.classList.remove('loading');

					// Remove index notice.
					let indexNotice = document.querySelector('.woostify-filter-index-notice');
					if (indexNotice) {
						indexNotice.remove();
					}

					// Update time.
					if (data.time) {
						lastIndex.innerHTML = data.time;
					}

					// Update count.
					if (data.total) {
						indexCount.innerHTML = data.total;
					}
				}
			).catch(
				function () {
					// Remove animation.
					indexBtn.parentNode.classList.remove('loading');
					indexBtn.removeAttribute('disabled');
					indexBtn.innerHTML = 'Re-index';
				}
			);
	}
}

// Sortable smart filter archive.
const woostifySortableFilter = function () {
	let list = jQuery('table.wp-list-table #the-list');

	list.sortable(
		{
			'items': 'tr',
			'axis': 'y',
			'update': function () {
				let data,
					ids = list.sortable('serialize');

				ids = ids.replaceAll('&post[]=', ','); // Remove from second string.
				ids = ids.replace('post[]=', ''); // Remove from First string.

				// Return if single ids ( not comma ).
				if (!ids.includes(',')) {
					return;
				}

				data = {
					'action': 'woostify_filter_list_sortable',
					'post_ids': ids,
					'ajax_nonce': woostify_product_filter.ajax_sortable_nonce
				};

				data = new URLSearchParams(data).toString();

				// Request.
				let request = new Request(
					ajaxurl,
					{
						method: 'POST',
						body: data,
						credentials: 'same-origin',
						headers: new Headers(
							{
								'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
							}
						)
					}
				);

				// Fetch.
				fetch(request);
			}
		}
	);
}

// Select shortcode when mouse click.
const woostifySelectText = function () {
	let shortcode = document.querySelectorAll('.w-filter-shortcode');
	if (!shortcode.length || !window.getSelection) {
		return;
	}

	shortcode.forEach(
		function (el) {
			el.onclick = function () {
				let range = document.createRange();

				range.selectNode(el);
				window.getSelection().removeAllRanges();
				window.getSelection().addRange(range);
			}
		}
	);
}

document.addEventListener(
	'DOMContentLoaded',
	function () {
		woostifyToggleCheckboxValue();
		woostifyRangeValue();
		woostifyUpdateAttr();
		woostifyProductCatData();
		woostifyTermConditionData();
		woostifySortableOrder();
		woostifyIndexDatabase();
		woostifyFieldShowOn('.woostify-smart-product-filter-product-setting tr[showon]');
		woostifyFieldShowOn('.admin-product-filter tr[showon]');
		woostifySortableFilter();
		woostifySelectText();
		woostifyProductStockType();
	}
);