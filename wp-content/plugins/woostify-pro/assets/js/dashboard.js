/**
 * Dashboard script
 *
 * @package Woostify Pro
 */

/* global ajaxurl, woostify_pro_dashboard */

'use strict';

// Check licenses.
var checkLicenses = function() {
	var field  = document.getElementById( 'woostify_license_key_field' ),
		button = document.getElementById( 'woostify_pro_license_key_submit' );

	if ( ! field || ! button ) {
		return;
	}

	button.onclick = function( e ) {
		e.preventDefault();

		var license = field.value.trim(),
			message = document.querySelector( '.license-key-message' );

		if ( ! license.length ) {
			alert( woostify_pro_dashboard.license_empty );
			return;
		}

		button.classList.add( 'updating-message' );

		// Request.
		var request = new Request(
			ajaxurl,
			{
				method: 'POST',
				body: 'action=woostify_pro_check_licenses&ajax_nonce=' + woostify_pro_dashboard.ajax_nonce + '&woostify_license_key=' + license,
				credentials: 'same-origin',
				headers: new Headers(
					{
						'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
					}
				)
			}
		);

		// Receiving Update.
		var receivingUpdate = function() {
			if ( message ) {
				message.innerHTML = woostify_pro_dashboard.receiving;
				message.classList.remove( 'not-receiving-updates' );
				message.classList.add( 'receiving-updates' );
			}

			button.innerHTML = woostify_pro_dashboard.deactivate_label;
			field.disabled   = true;
		}

		// Not Receiving Update.
		var notReceivingUpdate = function() {
			if ( message ) {
				message.innerHTML = woostify_pro_dashboard.not_receiving;
				message.classList.add( 'not-receiving-updates' );
				message.classList.remove( 'receiving-updates' );
			}

			button.innerHTML = woostify_pro_dashboard.activate_label;
			field.disabled   = false;
		}

		var isJson = function( str ) {
			try {
				JSON.parse( str );
			} catch ( e ) {
				return false;
			}

			return true;
		}

		// Fetch API.
		fetch( request )
			.then(
				function( res ) {
					if ( 200 !== res.status ) {
						console.log( 'Status Code: ' + res.status );
						button.classList.remove( 'updating-message' );
						return;
					}

					res.json().then(
						function( data ) {
							var success = false,
								action  = 'activate';

							for ( var i = 0, j = data.length; i < j; i++ ) {
								if ( ! isJson( data[i] ) ) {
									continue;
								}

								var res = JSON.parse( data[i] );

								if ( ! res.success ) {
									continue;
								}

								if ( res.success && 'valid' === res.license ) {
									success = true;
								} else if ( res.success && 'deactivated' === res.license ) {
									// Deactivate success.
									success = true;
									action  = 'deactivate';
								}
							}

							if ( success ) {
								if ( 'activate' == action ) {
									alert( woostify_pro_dashboard.activate_success_message );
									receivingUpdate();
								} else {
									alert( woostify_pro_dashboard.deactivate_success_message );
									notReceivingUpdate();
								}
							} else {
								notReceivingUpdate();

								alert( woostify_pro_dashboard.failure_message );
							}
						}
					);
				}
			)
			.catch(
				function( err ) {
					console.log( err );
				}
			).finally(
				function() {
					// Remove button loading animation.
					button.classList.remove( 'updating-message' );
				}
			);
	}
}

// Detect all featured are activated.
var detectFeature = function() {
	var list      = document.querySelectorAll( '.module-item' ),
		activated = document.querySelectorAll( '.module-item.activated' );

	if ( ! list.length ) {
		return;
	}

	var size    = ( list.length == activated.length ) ? 'yes' : '',
		request = new Request(
			ajaxurl,
			{
				method: 'POST',
				body: 'action=all_feature_activated&detect=' + size + '&ajax_nonce=' + woostify_pro_dashboard.ajax_nonce,
				credentials: 'same-origin',
				headers: new Headers(
					{
						'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
					}
				)
			}
		);

	// Fetch API.
	fetch( request );
}

// Activate or Deactive mudule.
var moduleAction = function() {
	var list = document.querySelector( '.woostify-module-list' );
	var list_info = document.querySelector('.woostify-module-info-list');

	if ( ! list && ! list_info ) {
		return;
	}
	var module_message_el = document.querySelector('.woostify-save-message');
	var item_info = list_info.querySelectorAll('.module-info-item');
	var item = list.querySelectorAll( '.module-item' );
	if ( ! item.length ) {
		return;
	}

	item.forEach(
		function( element ) {
			var checkbox = element.querySelectorAll( '.module-checkbox' );
			
			if ( ! checkbox ) {
				return;
			}
			
			checkbox[0].checked = false;
			if (element.classList.contains('activated')) {
				checkbox[0].checked = true;
			}

			checkbox[0].onclick = function() {
				var parent 		= checkbox[0].closest( '.module-item' ),
					module_cat  = parent.getAttribute( 'data-module-cat' ),
					option 		= checkbox[0].value,
					status 		= checkbox[0].getAttribute( 'data-status' ),
					label  		= woostify_pro_dashboard.activating;

				if ( 'activated' === status ) {
					label = woostify_pro_dashboard.deactivating;
				}

				// Request.
				var request = new Request(
					ajaxurl,
					{
						method: 'POST',
						body: 'action=module_action&name=' + option + '&status=' + status + '&ajax_nonce=' + woostify_pro_dashboard.ajax_nonce,
						credentials: 'same-origin',
						headers: new Headers(
							{
								'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
							}
						)
					}
				);
				
				// Add .loading class to list module
				list.classList.add( 'loading' );

				// Add .loading class to parent item.
				parent.classList.add( 'loading' );

				// Fetch API.
				fetch( request )
					.then(
						function( res ) {
							if ( 200 !== res.status ) {
								console.log( 'Status Code: ' + res.status );
								return;
							}

							res.json().then(
								function( r ) {
									if ( ! r.success ) {
										return;
									}
									var siblings_module = ''; siblings( element );
									var checked_active = false;

									// Update data status.
									checkbox[0].setAttribute( 'data-status', r.data.status );
						
									// Update parent class name.
									if ( parent.classList.contains( 'deactivated' ) && 'deactivated' != r.data.status  ) {
										parent.classList.remove( 'deactivated' );
										checked_active = true;
									}else{
										parent.classList.remove( 'activated' );						
									}
									parent.classList.add( r.data.status  );
									siblings_module = siblings( parent );

									item_info.forEach(
										function (item) {
											if (item.classList.contains(option)) {

												if ( item.classList.contains( 'deactivated' ) && 'deactivated' != r.data.status  ) {
													item.classList.remove( 'deactivated' );
												}else{
													item.classList.remove( 'activated' );
												}
												item.classList.add( r.data.status  );

											}
										}
									);

									//Add .succes class to module message
									module_message_el.classList.add( 'success' );
									module_message_el.querySelector('.message-success').innerHTML = woostify_edit_screen.saved_success;
									setTimeout(function () {
										module_message_el.classList.remove( 'success' );
									},3000);									
								
									siblings_module.forEach(
										function (item) { 
											if ( item.classList.contains( 'activated' ) ) {
												checked_active = true;
											}
										}
									);

									if ( checked_active ) {
										list_info.classList.remove( 'show-default' );
									}else{
										list_info.classList.add( 'show-default' );
									}

									// Detect all featured are activated.
									detectFeature();
								}
							);
						}
					).finally(
						function() {
							// Remove .loading class.
							list.classList.remove( 'loading' );
							parent.classList.remove( 'loading' );
						}
					);
			}

		}
	);
}

// Multi Activate or Deactivate module.
var multiModuleAction = function() {
	var module_action = document.querySelectorAll( '.woostify-select-module-all' ),	
		items  = document.querySelectorAll( '.module-item:not(.disabled)' ),
		list_info = document.querySelector('.woostify-module-info-list'),
		module_message_el = document.querySelector('.woostify-save-message');

	if ( ! module_action || ! items.length ) {
		return;
	}

	module_action.forEach(
		function(element){
			element.onclick = function () {
				var parent_checkbox = this.closest('.woostify-select-module-all-switch');
				var element_sblings = siblings(parent_checkbox);
				var module_cat = this.getAttribute('data-module-cat');
				var actionValue = this.getAttribute('data-module-action');
				var moduleActions = [];
				var item_info = list_info.querySelectorAll('.module-info-item' + ((module_cat != 'all')? '.' + module_cat : '') );
	
				if (this.checked) {
					parent_checkbox.classList.add('active-all');
					if (element_sblings.length) {
						element_sblings.forEach(
							function( el ) {
								el.classList.remove( 'active-all' );
								if (typeof el.children[0] !== 'undefined') {
									el.children[0].checked = false;
								}
							}
						);
					}
				}else{
					parent_checkbox.classList.remove('active-all');
				}

				// Get module action
				items.forEach(
					function( element ) {
						var checkbox      = element.querySelector( '.module-checkbox' ),
							status   = checkbox.getAttribute( 'data-status' ).trim(),
							module  = checkbox.value,
							module_action = [];
						// Return if process busy.
						if ( element.classList.contains( '.loading' ) ) {
							alert( 'Process running.' );
							return;
						}
	
						// Return if Action empty
						if (actionValue == '') {
							return;
						}
	
						// Return if same Action 
						if ( actionValue === status ) {
							return;
						}
					
						// Trigger click.
						if (element.classList.contains(module_cat) || module_cat == 'all') {
				
							module_action = {
								'name'   : module,
								'status' : status,
							};
							
							moduleActions.push(module_action);
							
							element.classList.add( 'loading' );
							checkbox.setAttribute( 'data-status', status);
							if ( status == 'deactivated' ) {
								checkbox.checked = true;
							}else{
								checkbox.checked = false;
							}
							
						}
	
					}
				);

				// Request.
				var request = new Request(
					ajaxurl,
					{
						method: 'POST',
						body: 'action=module_action_all&module_actions=' + JSON.stringify(moduleActions) + '&ajax_nonce=' + woostify_pro_dashboard.ajax_nonce,
						credentials: 'same-origin',
						headers: new Headers(
							{
								'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
							}
						)
					}
				);

				// Fetch API.
				fetch( request )
				.then(
					function( res ) {
						if ( 200 !== res.status ) {
							console.log( 'Status Code: ' + res.status );
							return;
						}
					
						res.json().then(
							function( r ) {
								if ( ! r.success ) {
									return;
								}
								
								if ( r.data.length == 0 ) {
									return;
								}

								var checked_active = false;

								//Add .succes class to module message
								module_message_el.classList.add( 'success' );
								setTimeout(function () {
									module_message_el.classList.remove( 'success' );
								},3000);
						
								items.forEach(
									function (item, index) {
										if ( item.classList.contains(module_cat) || module_cat == 'all' ) {
											var checkbox = item.querySelector( '.module-checkbox' );
											var module_name = checkbox.value;

											r.data.forEach(
												function ( value ) {
													if (module_name == value['name']) {

														if (value['status'] == 'activated') {
															checkbox.checked = true;
															checked_active = true;
														}else{
															checkbox.checked = false;
														}
														
														checkbox.setAttribute( 'data-status', value['status']);

														item_info.forEach(
															function (item) {
																if (item.classList.contains(value['name'])) {
																	item.className = '';
																	item.classList.add( 'module-info-item', module_cat, value['name'], value['status'] );
																}
															}
														);
													}
												}
											);
											
										}
									}
								);

								item_info.forEach(
									function (item) {
										if ( item.classList.contains( 'activated' ) ) {
											checked_active = true;
										}
									}
								);

								if ( checked_active ) {
									list_info.classList.remove( 'show-default' );
								}else{
									list_info.classList.add( 'show-default' );
								}
								

								// Detect all featured are activated.
								detectFeature();
							}
						);
					}
				).finally(
					function() {
						// Remove .loading class.
						items.forEach(
							function (item) { 
								item.classList.remove( 'loading' );
							}
						);
					}
				);

			}
		}
	);

	
}

var goToAddOnsTab = function () {
	var activate_addons = document.querySelector('.activate-add-ons');

	if ( !activate_addons ) {
		return;
	}

	activate_addons.addEventListener(
		'click',
		function (e) {
			var tab_head_button = document.querySelector('[href="#'+ this.getAttribute('data-tab') +'"]');
			var tab_head_button_Siblings  = siblings( tab_head_button );
			tab_head_button.classList.add('active');
			if ( tab_head_button_Siblings.length ) {
				tab_head_button_Siblings.forEach(el => {
					el.classList.remove('active');
				});
			}

		}
	)
}

//Add-ons module tabs
var addOnsTabs = function () {
	var addonstabs = document.querySelector('.woostify-module-addons-tabs');
	if ( ! addonstabs ) {
		return;
	}

	var buttontab = addonstabs.querySelectorAll('.tab-module-button');
	if ( ! buttontab ) {
		return;
	}

	buttontab.forEach(
		function (element) {
			element.onclick = function () {
				var button_siblings  = siblings( element );
				var module_item 				= document.querySelectorAll('.woostify-module-list .module-item');
				var module_cat    				= element.getAttribute('data-module-cat');
				var addons_active_all 			= document.getElementById('add-ons-active-all');
				var woostify_select_module_all  = document.querySelectorAll('.woostify-select-module-all');

				addons_active_all.setAttribute('data-multi-cat', module_cat);

				element.classList.add( 'active' );
				if ( button_siblings.length ) {
					button_siblings.forEach(
						function( el ) {
							el.classList.remove( 'active' );
						}
					);
				}

				module_item.forEach(
					function (item) {

						if (item.classList.contains(module_cat)) {
							item.style.display = "flex";
						}else{
							item.style.display = "none";
						}

						if (module_cat === 'all') {
							item.style.display = "flex";
						}

					}
				);

				woostify_select_module_all.forEach(
					function(el){
						el.setAttribute('data-module-cat',module_cat);
						el.checked = false;
						el.closest('.woostify-select-module-all-switch').classList.remove('active-all');
					}
				);
			}
		}
	);

}

var closeMessageSuccessAddons = function () {
	var module_message_close_button = document.querySelector('.woostify-save-message-close');

	if ( ! module_message_close_button ) {
		return;
	}

	module_message_close_button.addEventListener(
		'click',
		function (e) { 
			var parent = this.closest('.woostify-save-message');
			parent.classList.remove( 'success' );
		}
	);
}


// Changelog
var showChangelog = function () {
	var woostify_changelog = document.querySelector('.changelog-woostify-wrapper'); 
	var changelog_version = woostify_changelog.querySelectorAll( '.changelog-woostify-version' );
	var page_numbers = woostify_changelog.querySelectorAll('.page-numbers'); 

	page_numbers.forEach(
		function (item, index) {
			var page_number  = item.querySelectorAll( '.page-number' );
			var product_id   = item.getAttribute('data-changelog-product');
			var per_page     = item.getAttribute('data-per-page');
			var total_pages  = item.getAttribute( 'data-total-pages' );
			var page_pre     = item.querySelector('.page-pre');
			var page_next    = item.querySelector('.page-next');
			var dots         = item.querySelector('.dots');
			var page_current = item.querySelector('.page-number.active');

			page_number.forEach(
				function (element) {
					element.onclick = function() {
						var page_siblings = siblings( element );
						var page = this.getAttribute( 'data-page-number' );
						page = parseFloat(page);
		
						page_current = this;
						element.classList.add( 'active' );
						var n = 1;
						if ( page_siblings.length ) {
							page_siblings.forEach(
								function( el ) {
									el.classList.remove( 'active' );
									el.classList.remove( 'actived' );
								}
							);
							for (var i = 1; i <= 5 ; i++) {
								if (n <= 5) {
		
									if (page - i > 0 && item.children[page - i]) {
										item.children[page - i].classList.add( 'actived' );
										n++;
									}
		
									if (item.children[page + i - 1]) {
										item.children[page + i - 1].classList.add( 'actived' );
										n++;
									}

								}
							}
							
							if ( page - 1 != 0) {
								page_pre.classList.remove( 'disable' );
							}else{
								page_pre.classList.add( 'disable' );
							}

							if ( parseFloat( total_pages ) - page >= 2) {
								page_next.classList.remove( 'disable' );
							}else{
								page_next.classList.add( 'disable' );
							}

							if ( parseFloat( total_pages ) - page <= 2 ) {
								dots.classList.add( 'hide' );
							}else{
								dots.classList.remove( 'hide' );
							}
						}
		
						// Request.
						var request = new Request(
							ajaxurl,
							{
								method: 'POST',
								body: 'action=changelog_pagination&page=' + parseInt(page) + '&per_page=' + per_page + '&product_id=' + product_id + '&ajax_nonce=' + woostify_pro_dashboard.ajax_nonce,
								credentials: 'same-origin',
								headers: new Headers(
									{
										'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
									}
								)
							}
						);
						
						// Add .loading class
						changelog_version[index].classList.add('loading');
		
						// Fetch API.
						fetch( request )
						.then(
							function( res ) {
								if ( 200 !== res.status ) {
									console.log( 'Status Code: ' + res.status );
									return;
								}
							
								res.json().then(
									function( r ) {
										if ( ! r.success ) {
											return;
										}
										
										if ( r.data.length == 0 ) {
											return;
										}
		
	
										var content = '';
										var monthNames = ["January", "February", "March", "April", "May", "June",
										"July", "August", "September", "October", "November", "December"
										];
										r.data.forEach(
											function (value, index) {
												var date = new Date(value.date);
												var day = date.getDay();
												var month = monthNames[date.getMonth()];
												var year = date.getFullYear();
		
												content += '<li class="changelog-item">';
												content += '<div class="changelog-version-heading">';
												content += 	'<span>'+value.title.rendered+'</span>';
												content += 	'<span class="changelog-version-date">'+month+ ' '+ day +', ' + year+'</span>';
												content += '</div>';
												content += '<div class="changelog-version-content">';
												content += 		value.content.rendered;
												content += '</div>';
												content += '</li>';
											}
										);
		
										changelog_version[index].innerHTML = content;
			
									}
								);
							}
						).finally(
							function() {
								// Remove .loading class
								changelog_version[index].classList.remove('loading');
							}
						);
					}
				}
			);
			
			// Pre page
			page_pre.addEventListener(
				'click',
				function () {					
					var page = parseFloat( page_current.getAttribute( 'data-page-number' ) );
				
					page -= 1;

					if ( page == 0 ) {
						return;
					}

					page_current = item.children[page];

					var page_siblings = siblings( page_current );
					var n = 1;

					page_current.classList.add( 'active' );
					if ( page_siblings.length ) {
						page_siblings.forEach(
							function( el ) {
								el.classList.remove( 'active' );
								el.classList.remove( 'actived' );
							}
						);
						for (var i = 1; i <= 5 ; i++) {
							if (n <= 5) {
	
								if (page - i > 0 && item.children[page - i]) {
									item.children[page - i].classList.add( 'actived' );
									n++;
								}
	
								if (item.children[page + i - 1]) {
									item.children[page + i - 1].classList.add( 'actived' );
									n++;
								}

							}
						}
						
						if ( page - 1 != 0) {
							page_pre.classList.remove( 'disable' );
						}else{
							page_pre.classList.add( 'disable' );
						}

						if ( parseFloat( total_pages ) - page >= 2) {
							page_next.classList.remove( 'disable' );
						}else{
							page_next.classList.add( 'disable' );
						}

						if ( parseFloat(dots.getAttribute('data-last-pages')) - page >= 2 ) {
							dots.classList.add( 'hide' );
						}else{
							dots.classList.remove( 'hide' );
						}
					}

					// Request.
					var request = new Request(
						ajaxurl,
						{
							method: 'POST',
							body: 'action=changelog_pagination&page=' + parseInt(page) + '&per_page=' + per_page + '&product_id=' + product_id + '&ajax_nonce=' + woostify_pro_dashboard.ajax_nonce,
							credentials: 'same-origin',
							headers: new Headers(
								{
									'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
								}
							)
						}
					);
					
					// Add .loading class
					changelog_version[index].classList.add('loading');

					// Fetch API.
					fetch( request )
					.then(
						function( res ) {
							if ( 200 !== res.status ) {
								console.log( 'Status Code: ' + res.status );
								return;
							}
						
							res.json().then(
								function( r ) {
									if ( ! r.success ) {
										return;
									}
									
									if ( r.data.length == 0 ) {
										return;
									}
	
									console.log(r.data);
									var content = '';
									var monthNames = ["January", "February", "March", "April", "May", "June",
									"July", "August", "September", "October", "November", "December"
									];
									r.data.forEach(
										function (value, index) {
											var date = new Date(value.date);
											var day = date.getDay();
											var month = monthNames[date.getMonth()];
											var year = date.getFullYear();
	
											content += '<li class="changelog-item">';
											content += '<div class="changelog-version-heading">';
											content += 	'<span>'+value.title.rendered+'</span>';
											content += 	'<span class="changelog-version-date">'+month+ ' '+ day +', ' + year+'</span>';
											content += '</div>';
											content += '<div class="changelog-version-content">';
											content += 		value.content.rendered;
											content += '</div>';
											content += '</li>';
										}
									);
	
									changelog_version[index].innerHTML = content;
		
								}
							);
						}
					).finally(
						function() {
							// Remove .loading class
							changelog_version[index].classList.remove('loading');
						}
					);

				}
			);
			
			// Next page
			page_next.addEventListener(
				'click',
				function () {
					var page = parseFloat( page_current.getAttribute( 'data-page-number' ) );

					page += 1;

					if ( parseFloat( total_pages ) - page == 0 ) {
						return;
					}

					page_current = item.children[page];

					var page_siblings = siblings( page_current );
					var n = 1;

					page_current.classList.add( 'active' );
					if ( page_siblings.length ) {
						page_siblings.forEach(
							function( el ) {
								el.classList.remove( 'active' );
								el.classList.remove( 'actived' );
							}
						);
						for (var i = 1; i <= 5 ; i++) {
							if (n <= 5) {
	
								if (page - i > 0 && item.children[page - i]) {
									item.children[page - i].classList.add( 'actived' );
									n++;
								}
	
								if (item.children[page + i - 1]) {
									item.children[page + i - 1].classList.add( 'actived' );
									n++;
								}

							}
						}
						
						if ( page - 1 != 0) {
							page_pre.classList.remove( 'disable' );
						}else{
							page_pre.classList.add( 'disable' );
						}

						if ( parseFloat( total_pages ) - page >= 2) {
							page_next.classList.remove( 'disable' );
						}else{
							page_next.classList.add( 'disable' );
						}

						if ( parseFloat(dots.getAttribute('data-last-pages')) - page >= 2 ) {
							dots.classList.add( 'hide' );
						}else{
							dots.classList.remove( 'hide' );
						}
					}

					// Request.
					var request = new Request(
						ajaxurl,
						{
							method: 'POST',
							body: 'action=changelog_pagination&page=' + parseInt(page) + '&per_page=' + per_page + '&product_id=' + product_id + '&ajax_nonce=' + woostify_pro_dashboard.ajax_nonce,
							credentials: 'same-origin',
							headers: new Headers(
								{
									'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
								}
							)
						}
					);

					// Add .loading class
					changelog_version[index].classList.add('loading');

					// Fetch API.
					fetch( request )
					.then(
						function( res ) {
							if ( 200 !== res.status ) {
								console.log( 'Status Code: ' + res.status );
								return;
							}
						
							res.json().then(
								function( r ) {
									if ( ! r.success ) {
										return;
									}
									
									if ( r.data.length == 0 ) {
										return;
									}
	
									var content = '';
									var monthNames = ["January", "February", "March", "April", "May", "June",
									"July", "August", "September", "October", "November", "December"
									];
									r.data.forEach(
										function (value, index) {
											var date = new Date(value.date);
											var day = date.getDay();
											var month = monthNames[date.getMonth()];
											var year = date.getFullYear();
	
											content += '<li class="changelog-item">';
											content += '<div class="changelog-version-heading">';
											content += 	'<span>'+value.title.rendered+'</span>';
											content += 	'<span class="changelog-version-date">'+month+ ' '+ day +', ' + year+'</span>';
											content += '</div>';
											content += '<div class="changelog-version-content">';
											content += 		value.content.rendered;
											content += '</div>';
											content += '</li>';
										}
									);
	
									changelog_version[index].innerHTML = content;
		
								}
							);
						}
					).finally(
						function() {
							// Remove .loading class
							changelog_version[index].classList.remove('loading');
						}
					);

				}
			);

		}
	);


}

document.addEventListener(
	'DOMContentLoaded',
	function() {
		checkLicenses();
		moduleAction();
		multiModuleAction();
		goToAddOnsTab();
		addOnsTabs();
		closeMessageSuccessAddons();
		showChangelog();
	}
);
