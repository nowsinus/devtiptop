/**
 * Sale notification
 *
 * @package Woostify Pro
 */

/* global woostify_sale_notification */

'use strict';

// Get real time. Unit hours.
function woositfyRealTime( number = 10 ) {
	let text = '',
		time = number * 3600000,
		data = woostify_sale_notification.data;

	time = Math.floor( Math.random() * ( time - 10000 ) + 10000 );

	let one_day  = 24 * 60 * 60 * 1000,
		one_hour = 60 * 60 * 1000,
		days     = parseInt( time / one_day ),
		hours    = parseInt( ( time - days * one_day ) / one_hour ),
		minutes  = parseInt( ( time - days * one_day - hours * one_hour ) / 60000 ),
		seconds  = parseInt( ( time % 60000 ) / 1000 );

	if ( hours >= 1 ) {
		text = hours + ' ' + ( hours > 1 ? data.hours : data.hour );
	} else if ( minutes > 0 ) {
		text = minutes + ' ' + ( minutes > 1 ? data.minutes : data.minute );
	} else if ( seconds > 0 ) {
		text = seconds + ' ' + ( seconds > 1 ? data.seconds : data.second );
	}

	return text;
}

// Get random value from array.
function woositfyRandomValue( arr ) {
	if ( ! arr.length ) {
		return '';
	}

	return arr[ (Math.random() * arr.length ) | 0]
}

// Generate message.
function woostifyMessage( link, src, alt, text ) {
	let message = '';

	if ( src ) {
		message += '<a class="sale-notification-image" href="' + link + '">';
		message += '<img src="' + src + '" alt="' + alt + '">';
		message += '</a>';
	}

	message += '<div class="sale-notification-message">' + text + '</div>';

	return message;
}

// Sale notification.
function woostifySaleNotification() {
	let box    = document.querySelector( '.woostify-sale-notification-box' ),
		mobile = box ? box.classList.contains( 'display-on-mobile' ) : true;
	if ( ! box || ( ! mobile && window.outerWidth <= 600 ) ) {
		return;
	}

	let data     = woostify_sale_notification.data,
		products = data.products,
		messages = data.messages;
	if ( ! products || ! products.length || ! messages.length ) {
		return;
	}

	let closeBtn         = box.querySelector( '.sale-notification-close-button' ),
		inner            = box.querySelector( '.sale-notification-inner' ),
		firstTimeDisplay = parseInt( woostify_sale_notification.initial_display ) * 1000,
		timeDisplay      = parseInt( woostify_sale_notification.display_time ) * 1000,
		nextTimeDisplay  = parseInt( woostify_sale_notification.next_time_display ) * 1000;

	// Hide notification.
	closeBtn.onclick = function() {
		box.classList.remove( 'active' );
	}

	for ( let i in products ) {
		let random  = woositfyRandomValue( messages ),
			message = woostifyMessage( products[i].link, products[i].src, products[i].alt, random ),
			number  = Math.floor( Math.random() * ( data.min_number - data.max_number ) + data.max_number ),
			time    = Number.isInteger( data.time ) ? woositfyRealTime( data.time ) : woositfyRandomValue( data.time );

		message = message.replace( '{number}', number );
		message = message.replace( '{time_ago}', time + ' ' + data.ago );
		message = message.replace( '{first_name}', woositfyRandomValue( data.first_name ) );
		message = message.replace( '{city}', woositfyRandomValue( data.city ) );
		message = message.replace( '{state}', woositfyRandomValue( data.state ) );
		message = message.replace( '{country}', woositfyRandomValue( data.country ) );
		message = message.replace( '{product_title}', products[i]['title'] );
		message = message.replace( '{product_title_with_link}', '<a href="' + products[i]['link'] + '">' + products[i]['title'] + '</a>' );

		// Loop.
		if ( window.cacheForLoop ) {
			firstTimeDisplay = nextTimeDisplay + timeDisplay;
		}

		// Timer.
		let timeToShow = 0 === Number( i ) ? firstTimeDisplay : ( firstTimeDisplay + Number( i ) * ( timeDisplay + nextTimeDisplay ) );

		// Show notification.
		setTimeout(
			function() {
				inner.innerHTML = message;
				box.classList.add( 'active' );

				// Hide notification.
				setTimeout(
					function() {
						box.classList.remove( 'active' );
					},
					timeDisplay
				);

				// Enable notification loop.
				if ( '1' == woostify_sale_notification.loop && products.length == Number( i ) + 1 ) {
					window.cacheForLoop = true;

					woostifySaleNotification();
				}
			},
			timeToShow
		);
	}

	// Need more space.
	let addToCartSection = document.querySelector( '.sticky-add-to-cart-section.from-bottom' );
	if ( addToCartSection ) {
		// When add to cart section sticked.
		document.documentElement.addEventListener(
			'stickedAddToCart',
			function() {
				box.classList.add( 'need-more-space' );
			}
		);

		// When add to cart section unsticked.
		document.documentElement.addEventListener(
			'unStickedAddToCart',
			function() {
				box.classList.remove( 'need-more-space' );
			}
		);
	}
}

document.addEventListener(
	'DOMContentLoaded',
	function() {
		woostifySaleNotification();
	}
);
