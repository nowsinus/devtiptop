/**
 * Variation Swatches Admin
 *
 * @package Woostify Pro
 */

/* global woostify_variation_swatches_admin */

'use strict';
if ( typeof woostifyEvent == 'undefined' ){
	var woostifyEvent = {};
}
var frame, woostifyData = woostify_variation_swatches_admin || {};

document.addEventListener(
	'DOMContentLoaded',
	function() {
		if( woostifyEvent.adminVariationGallery||0 ) return;
		var wp   = window.wp,
			body = jQuery( 'body' );

		jQuery( '#term-color' ).wpColorPicker();

		// Update attribute image.
		body.on(
			'click',
			'.woostify-variation-swatches-upload-image-button',
			function( event ) {
				event.preventDefault();

				var button = jQuery( this );

				// If the media frame already exists, reopen it.
				if ( frame ) {
					frame.open();
					return;
				}

				// Create the media frame.
				frame = wp.media.frames.downloadable_file = wp.media(
					{
						title   : woostifyData.i18n.mediaTitle,
						button  : {
							text: woostifyData.i18n.mediaButton
						},
						multiple: false
					}
				);

				// When an image is selected, run a callback.
				frame.on(
					'select',
					function() {
						var attachment = frame.state().get( 'selection' ).first().toJSON();

						button.siblings( 'input.woostify-variation-swatches-term-image' ).val( attachment.id );
						button.siblings( '.woostify-variation-swatches-remove-image-button' ).show();
						button.parent().prev( '.woostify-variation-swatches-term-image-thumbnail' ).find( 'img' ).attr( 'src', attachment.sizes.thumbnail.url );
					}
				);

				// Finally, open the modal.
				frame.open();
			}
		).on(
			'click',
			'.woostify-variation-swatches-remove-image-button',
			function() {
				var button = jQuery( this );

				button.siblings( 'input.woostify-variation-swatches-term-image' ).val( '' );
				button.siblings( '.woostify-variation-swatches-remove-image-button' ).show();
				button.parent().prev( '.woostify-variation-swatches-term-image-thumbnail' ).find( 'img' ).attr( 'src', woostifyData.placeholder );

				return false;
			}
		);

		// Toggle add new attribute term modal.
		var modal   = jQuery( '#woostify-variation-swatches-modal-container' ),
			spinner = modal.find( '.spinner' ),
			msg     = modal.find( '.message' ),
			metabox = null;

		body.on(
			'click',
			'.variation_swatches_add_new_attribute',
			function( e ) {
				e.preventDefault();

				var button           = jQuery( this ),
					taxInputTemplate = wp.template( 'woostify-variation-swatches-input-tax' ),
					data             = {
						type: button.data( 'type' ),
						tax : button.closest( '.woocommerce_attribute' ).data( 'taxonomy' )
				};

				// Insert input.
				modal.find( '.woostify-variation-swatches-term-swatch' ).html( jQuery( '#tmpl-woostify-variation-swatches-input-' + data.type ).html() );
				modal.find( '.woostify-variation-swatches-term-tax' ).html( taxInputTemplate( data ) );

				if ( 'color' == data.type ) {
					modal.find( 'input.woostify-variation-swatches-input-color' ).wpColorPicker();
				}

				metabox = button.closest( '.woocommerce_attribute.wc-metabox' );
				modal.show();
			}
		).on(
			'click',
			'.woostify-variation-swatches-modal-close, .woostify-variation-swatches-modal-backdrop',
			function( e ) {
				e.preventDefault();
				closeModal();
			}
		);

		// Send ajax request to add new attribute term.
		body.on(
			'click',
			'.woostify-variation-swatches-new-attribute-submit',
			function( e ) {
				e.preventDefault();

				var button = jQuery( this ),
					type   = button.data( 'type' ),
					error  = false,
					data   = {};

				// Validate.
				modal.find( '.woostify-variation-swatches-input' ).each(
					function() {
						var t = jQuery( this );

						if ( 'slug' != t.attr( 'name' ) && ! t.val() ) {
							t.addClass( 'error' );
							error = true;
						} else {
							t.removeClass( 'error' );
						}

						data[ t.attr( 'name' ) ] = t.val();
					}
				);

				if ( error ) {
					return;
				}

				// Send ajax request.
				spinner.addClass( 'is-active' );
				msg.hide();
				wp.ajax.send(
					'variation_swatches_add_new_attribute',
					{
						data: data,
						error: function( res ) {
							spinner.removeClass( 'is-active' );
							msg.addClass( 'error' ).text( res ).show();
						},
						success: function( res ) {
							spinner.removeClass( 'is-active' );
							msg.addClass( 'success' ).text( res.msg ).show();

							metabox.find( 'select.attribute_values' ).append( '<option value="' + res.id + '" selected="selected">' + res.name + '</option>' );
							metabox.find( 'select.attribute_values' ).change();

							closeModal();
						}
					}
				);
			}
		);

		// Close modal.
		function closeModal() {
			modal.find( '.woostify-variation-swatches-term-name input, .woostify-variation-swatches-term-slug input' ).val( '' );
			spinner.removeClass( 'is-active' );
			msg.removeClass( 'error success' ).hide();
			modal.hide();
		}

		// Wooostify variation_image_gallery support
		init_product_variation_image_gallery( '#variable_product_options' );
		console.log( $('#variable_product_options'));

		woostifyEvent.adminVariationGallery = 1;
	}

);

function init_gallery_sortable(elm){
	$ = jQuery;
	var _containers = $(elm);
	if( ! _containers.length ) return;
	var all_gallery = [];
	if( _containers.hasClass('product_variation_images')) {
		all_gallery = _containers;
	}else{
		all_gallery = _containers.find( 'ul.product_variation_images' );
	}

	// Image ordering.
	all_gallery.sortable( {
		items: 'li.image',
		cursor: 'move',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		forceHelperSize: false,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'image wc-metabox-sortable-placeholder product_image_variation_placeholder',
		start: function ( event, ui ) {
			ui.item.css( 'background-color', '#f6f6f6' );
		},
		stop: function ( event, ui ) {
			ui.item.removeAttr( 'style' );
		},
		update: function ( e, ui) {
			console.log( ui );
			var attachment_ids = [];
			var row_gallery = ui.item.closest('.row-product_variation_images');
			row_gallery.find( 'ul li.image' )
				.css( 'cursor', 'default' )
				.each( function () {
					var attachment_id = $( this ).attr( 'data-attachment_id' );
					attachment_ids.push( attachment_id );
				} );
				row_gallery.find('input.product_image_variation_gallery').val( attachment_ids.join(',') ).trigger( 'change' );
		},
	} ); 
}
function init_product_variation_image_gallery(elm){
	$ = jQuery;
	var _containers = $(elm);
	
	// Product variation gallery file uploads.
	init_gallery_sortable(_containers);
	_containers.on( 'click', 'a.add-product-variation-gallery', function ( event ) {
		event.preventDefault();
		var $el = $( this );
		var _container = $el.closest('.row-product_variation_images');
		var $image_gallery_ids = _container.find( '.product_image_variation_gallery' );
		var $product_variation_images = _container.find( 'ul.product_variation_images' );

		// If the media frame already exists, reopen it.
		if ( product_variation_gallery_frame ) {
			console.log( product_variation_gallery_frame);
			product_variation_gallery_frame.open();
		} else{
			// Create the media frame.
			var product_variation_gallery_frame;
			product_variation_gallery_frame = wp.media.frames.product_variation_gallery = wp.media( {
				// Set the title of the modal.
				title: $el.data( 'choose' ),
				button: {
					text: $el.data( 'update' ),
				},
				states: [
					new wp.media.controller.Library( {
						title: $el.data( 'choose' ),
						filterable: 'all',
						multiple: true,
					} ),
				],
			} );
		}

		// When an image is selected, run a callback.
		product_variation_gallery_frame.off('select').on( 'select', function () {
			var selection = product_variation_gallery_frame.state().get( 'selection' );
			var attachment_ids = $image_gallery_ids.val();

			selection.map( function ( attachment ) {
				attachment = attachment.toJSON();

				if ( attachment.id ) {
					attachment_ids = attachment_ids
						? attachment_ids + ',' + attachment.id
						: attachment.id;
					var attachment_image =
						attachment.sizes && attachment.sizes.thumbnail
							? attachment.sizes.thumbnail.url
							: attachment.url;

					$product_variation_images.append(
						'<li class="image" data-attachment_id="' +
							attachment.id +
							'"><img src="' +
							attachment_image +
							'" /><ul class="actions"><li><a href="javascript:void(0);" class="delete" title="' +
							$el.data( 'delete' ) +
							'">' +
							$el.data( 'text' ) +
							'</a></li></ul></li>'
					);
				}
			} );

			$image_gallery_ids.val( attachment_ids ).trigger( 'change' );
			init_gallery_sortable(_container);
		} );

		// Finally, open the modal.
		product_variation_gallery_frame.open();
	} );

	// Remove images.
	_containers.on( 'click', '.product_variation_images a.delete', function (e) {
		var row_gallery = $( this ).closest('.row-product_variation_images');
		$( this ).closest( 'li.image' ).remove();

		var attachment_ids = [];
		row_gallery.find( 'ul li.image' )
			.css( 'cursor', 'default' )
			.each( function () {
				var attachment_id = $( this ).attr( 'data-attachment_id' );
				attachment_ids.push(attachment_id);
			} );

		row_gallery.find('.product_image_variation_gallery').val( attachment_ids.join(',') ).trigger( 'change' );

		return false;
	} );

}