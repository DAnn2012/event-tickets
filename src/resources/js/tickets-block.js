// @TODO: Take this line off once we actually have the tribe object
if ( 'undefined' === typeof tribe ) {
	tribe = {};
}

// Define the tickets object if not defined.
if ( 'undefined' === typeof tribe.tickets ) {
	tribe.tickets = {};
}

tribe.tickets.block = {
	num_attendees: 0,
	event        : {}
};

( function( $, obj, te ) {
	'use strict';

	/* Variables */

	obj.document = $( document );

	/*
	 * Ticket Block Selectors.
	 *
	 * @since TBD
	 *
	 */
	obj.selector = {
		container                  : '#tribe-tickets',
		item                       : '.tribe-tickets__item',
		itemExtraAvailable         : '.tribe-tickets__item__extra__available',
		itemExtraAvailableQuantity : '.tribe-tickets__item__extra__available_quantity',
		itemOptOut                 : '.tribe-tickets-attendees-list-optout--wrapper',
		itemOptOutInput            : '#tribe-tickets-attendees-list-optout-',
		itemPrice                  : '.tribe-amount',
		itemQuantity               : '.tribe-tickets__item__quantity',
		itemQuantityInput          : '.tribe-tickets-quantity',
		submit                     : '.tribe-tickets__buy',
	};

	/*
	 * AR Cart Modal Selectors.
	 * Note: some of these have the modal class as well,
	 * as the js can pick up the tempalte in the DOM and grab the wrong data.
	 *
	 * @since TBD
	 *
	 */
	obj.modalSelector = {
		container  : '.tribe-modal__wrapper--ar',
		cartForm   : '.tribe-modal__wrapper--ar #tribe-modal__cart',
		arForm     : '.tribe-modal__wrapper--ar #tribe-modal__attendee_registration',
		itemRemove : '.tribe-tickets__item__remove',
		itemTotal  : '.tribe-tickets__item__total__wrap .tribe-amount',
		arItem     : '.tribe-ticket',
		metaField  : '.ticket-meta',
		submit     : '.tribe-block__tickets__item__attendee__fields__footer_submit',
	};

	obj.commerceSelector = {
		Tribe__Tickets_Plus__Commerce__EDD__Main         : 'edd',
		Tribe__Tickets__RSVP                             : 'rsvp',
		Tribe__Tickets__Commerce__PayPal__Main           : 'tribe-commerce',
		Tribe__Tickets_Plus__Commerce__WooCommerce__Main : 'woo',
	};

	var $tribe_ticket = $( obj.selector.container );

	// Bail if there are no tickets on the current event/page/post.
	if ( 0 === $tribe_ticket.length ) {
		return;
	}

	obj.tribe_ticket_provider = $tribe_ticket.data( 'provider' );
	obj.postId = $( '.status-publish' ).attr( 'id' ).replace( 'post-', '' );

	/**
	 * Init the tickets script.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.init = function() {
		obj.checkAvailability();
		obj.initPrefill();
	}

	/* DOM Updates */

	/**
	 * Make dom updates for the AJAX response.
	 *
	 * @since 4.9
	 *
	 * @return array
	 */
	obj.updateAvailability = function( tickets ) {
		Object.keys( tickets ).forEach( function( ticket_id ) {
			var available = tickets[ ticket_id ].available;
			var $ticketEl = $( obj.selector.item + "[data-ticket-id='" + ticket_id + "']" );

			if ( 0 === available ) { // Ticket is out of stock.
				var unavailableHtml = tickets[ ticket_id ].unavailable_html;
				// Set the availability data attribute to false.
				$ticketEl.attr( 'available', false );
				// Remove classes for instock and purchasable.
				$ticketEl.removeClass( 'instock' );
				$ticketEl.removeClass( 'purchasable' );

				// Update HTML elements with the "Out of Stock" messages.
				$ticketEl.find( obj.selector.itemQuantity ).html( unavailableHtml );
			}

			if ( 1 < available ) { // Ticket in stock, we may want to update values.
				$ticketEl.find( obj.selector.itemQuantityInput ).attr( { 'max' : available } );
				$ticketEl.find( obj.selector.itemExtraAvailableQuantity ).html( available );
			}
		} );
	}

	/**
	 * Update all the footer info.
	 *
	 * @since TBD
	 *
	 * @param int    $form The form we're updating.
	 */
	obj.updateFooter = function( $form ) {
		obj.updateFooterCount( $form );
		obj.updateFooterAmount( $form );
		$form.find( '.tribe-tickets__footer' ).addClass( 'tribe-tickets__footer--active' );
	}

	/**
	 * Adjust the footer count for +/-.
	 *
	 * @since TBD
	 *
	 * @param int    $form The form we're updating.
	 */
	obj.updateFooterCount = function( $form ) {
		var $field      = $form.find( '.tribe-tickets__footer__quantity__number' );
		var footerCount = 0;
		var $qtys       = $form.find( obj.selector.itemQuantityInput );

		$qtys.each(function(){
			var new_quantity = parseInt( $(this).val(), 10 );
			new_quantity     = isNaN( new_quantity ) ? 0 : new_quantity;
			footerCount      += new_quantity;
		} );

		if ( 0 > footerCount ) {
			return;
		}

		$field.text( footerCount );
	}

	/**
	 * Adjust the footer total/amount for +/-.
	 *
	 * @since TBD
	 *
	 * @param int    $form The form we're updating.
	 */
	obj.updateFooterAmount = function( $form ) {
		var $field       = $form.find( '.tribe-tickets__footer__total__number' );
		var footerAmount = 0;
		var $qtys        = $form.find( obj.selector.itemQuantityInput );

		$qtys.each(function(){
			var $price   = $( this ).closest( obj.selector.item ).find( obj.selector.itemPrice );
			var quantity = parseInt( $( this ).val(), 10 );
			quantity     = isNaN( quantity ) ? 0 : quantity;
			footerAmount += parseFloat( $price.text() ) * quantity;
		} );

		if ( 0 > footerAmount ) {
			return;
		}

		$field.text( obj.numberFormat ( footerAmount ) );
	}

	/**
	 * Update Cart Totals in Modal.
	 *
	 * @since TBD
	 *
	 * @param $cart The jQuery form object to update totals.
	 */
	obj.updateFormTotals = function ( $cart ) {
		var total_qty    = 0;
		var total_amount = 0.00;

		$cart.find( obj.selector.item ).each(
			function () {
				var modalCartItem = $( this );
				var qty           = obj.getQty( modalCartItem );

				var total = parseFloat( $( this ).find( obj.modalSelector.itemTotal ).text().replace( ',', '' ) );

				if ( '.' === obj.getCurrencyFormatting().thousands_sep ) {
					total = parseFloat( $( this ).find( obj.modalSelector.itemTotal ).text().replace( /\./g,'' ).replace( ',', '.' ) );
				}

				total_qty    += parseInt( qty, 10 );
				total_amount += total;

			}
		);

		obj.updateFooter( $cart );

		obj.appendARFields( $cart );
	};

	/**
	 * Possibly Update an Items Qty and always update the Total.
	 *
	 * @since TBD
	 *
	 * @param int id            The id of the ticket/product.
	 * @param obj $modalCartItem The cart item to update.
	 * @param obj $blockCartItem The optional ticket block cart item.
	 *
	 * @returns {number}
	 */
	obj.updateItem = function ( id, $modalCartItem, $blockCartItem ) {
		var item = {};
		item.id  = id;

		if ( ! $blockCartItem ) {
			item.qty   = obj.getQty( $modalCartItem );
			item.price = obj.getPrice( $modalCartItem );
		} else {
			item.qty   = obj.getQty( $blockCartItem );
			item.price = obj.getPrice( $modalCartItem );

			$modalCartItem.find( obj.selector.itemQuantityInput ).val( item.qty );

			if ( item.qty <= 0 ) {
				$modalCartItem.fadeOut();
			} else {
				$modalCartItem.fadeIn();
			}

			// We force new DOM queries here to be sure we pick up dynamically generated items.
			var optoutSelector = obj.selector.itemOptOutInput + $blockCartItem.data( 'ticket-id' );
			item.$optOut = $( optoutSelector );

			if ( item.$optOut.length && item.$optOut.is( ':checked' ) ) {
				$( optoutSelector + '-modal' ).val( '1' );
			} else {
				$( optoutSelector + '-modal' ).val( '0' );
			}
		}

		obj.updateTotal( item.qty, item.price, $modalCartItem );

		return item;

	};

	/**
	 * Update the Price for the Given Cart Item.
	 *
	 * @since TBD
	 *
	 * @param number qty   The quantity.
	 * @param number price The price.
	 * @param obj cartItem The cart item to update.
	 *
	 * @returns {string}
	 */
	obj.updateTotal = function ( qty, price, $cartItem ) {

		var total_for_item = ( qty * price ).toFixed( obj.getCurrencyFormatting().number_of_decimals );
		var $field         = $cartItem.find( '.tribe-tickets__item__total' );

		$field.text( obj.numberFormat( total_for_item ) );

		return total_for_item;
	};

	/* Utility */

	/**
	 * Get the tickets IDs.
	 *
	 * @since 4.9
	 *
	 * @return array
	 */
	obj.getTickets = function() {
		var $tickets = $( obj.selector.item ).map(
			function() {
				return $( this ).data( 'ticket-id' );
			}
		).get();

		return $tickets;
	}

	/**
	 * Maybe display the Opt Out.
	 *
	 * @since TBD
	 *
	 * @param obj $ticket The ticket item element.
	 * @param int new_quantity The new ticket quantity.
	 */
	obj.maybeShowOptOut = function( $ticket, new_quantity ) {
		var has_optout = $ticket.has( obj.selector.itemOptOut ).length;
		if ( has_optout ) {
			var $optout = $ticket.closest( obj.selector.item ).find( obj.selector.itemOptOut );
			( 0 < new_quantity ) ? $optout.show() : $optout.hide();
		}
	}

	/**
	 * En/disable the ticket fields for Tribe Commerce.
	 *
	 * @since TBD
	 *
	 * @param int new_quantity The new ticket quantity.
	 * @param obj $form The form element.
	 * @param int ticket_id The ticket ID.
	 */
	obj.tribeCommerceDisable = function( new_quantity, $form, ticket_id ) {
		if ( 0 < new_quantity ) {
			$form
				.find( '[data-ticket-id]:not([data-ticket-id="' + ticket_id + '"])' )
				.closest( obj.selector.item )
				.find( 'input, button' )
				.attr( 'disabled', 'disabled' )
				.closest( 'div' )
				.addClass( 'tribe-tickets-purchase-disabled' );

		} else {
			$form
				.find( 'input, button' )
				.removeAttr( 'disabled' )
				.closest( 'div' )
				.removeClass( 'tribe-tickets-purchase-disabled' );
		}
	}

	/**
	 * Appends AR fields when modal cart quantities are changed.
	 *
	 * @since TBD
	 *
	 * @param obj $form The form we are updating.
	 */
	obj.appendARFields = function ( $form ) {
		var nonMetaCount = 0;
		$form.find( obj.selector.item ).each(
			function () {
				var $cartItem = $( this );

				if ( $cartItem.is( ':visible' ) ) {
					var ticketID          = $cartItem.closest( obj.selector.item ).data( 'ticket-id' );
					var $ticket_container = $( obj.modalSelector.arForm ).find( '.tribe-tickets__item__attendee__fields__container[data-ticket-id="' + ticketID + '"]' );

					// Ticket does not have meta - no need to jump through hoops (and throw errors).
					if ( ! $ticket_container.length ) {
						nonMetaCount += obj.getQty( $cartItem );
						return;
					}

					var $existing = $ticket_container.find( obj.modalSelector.arItem );
					var qty       = obj.getQty( $cartItem );

					if ( 0 >= qty ) {
						$ticket_container.removeClass( 'tribe-tickets--has-tickets' );
						$ticket_container.find( obj.modalSelector.arItem ).remove();

						return;
					}

					if ( $existing.length > qty ) {
						var remove_count = $existing.length - qty;

						$ticket_container.find( '.tribe-ticket:nth-last-child( -n+' + remove_count + ' )' ).remove();
					} else if ( $existing.length < qty ) {
						var ticketTemplate = window.wp.template( 'tribe-registration--' + ticketID );
						var counter        = 0 < $existing.length ? $existing.length + 1 : 1;

						$ticket_container.addClass( 'tribe-tickets--has-tickets' );

						for ( var i = counter; i <= qty; i++ ) {
							var data = { 'attendee_id': i };

							$ticket_container.append( ticketTemplate( data ) );
							obj.maybeHydrateAttendeeBlockFromLocal( $existing.length );
						}
					}
				}
			}
		);

		var $notice = $( '.tribe-tickets-notice--non-ar' );
		if ( nonMetaCount ) {
			$( '#tribe-tickets__non-ar-count' ).text( nonMetaCount );
			$notice.show();
		} else {
			$notice.hide();
		}

		obj.document.trigger( 'tribe-ar-fields-appended' );
	}

	/**
	 * Step up the input according to the button that was clicked.
	 * Handles IE/Edge.
	 *
	 * @since TBD
	 */
	obj.stepUp = function( $input, originalValue ) {
		// We use 0 here as a shorthand for no maximum.
		var max      = $input[ 0 ].max ? Number( $input[ 0 ].max ) : -1;
		var step     = $input[ 0 ].step ? Number( $input [ 0 ].step ) : 1;
		var increase = ( -1 === max || max >= originalValue + step ) ? originalValue + step : max;

		if ( 'function' === typeof $input[ 0 ].stepUp ) {
			try {
				$input[ 0 ].stepUp();
			} catch ( ex ) {
				$input[ 0 ].value = increase;
			}
		} else {
			$input[ 0 ].value = increase;
		}
	}

	/**
	 * Step down the input according to the button that was clicked.
	 * Handles IE/Edge.
	 *
	 * @since TBD
	 */
	obj.stepDown = function( $input, originalValue ) {
		var min      = $input[ 0 ].min ? Number( $input[ 0 ].min ) : 0;
		var step     = $input[ 0 ].step ? Number( $input [ 0 ].step ) : 1;
		var decrease = ( min <= originalValue - step && 0 < originalValue - step ) ? originalValue - step : min;

		if ( 'function' === typeof $input[ 0 ].stepDown ) {
			try {
				$input[ 0 ].stepDown();
			} catch ( ex ) {
				$input[ 0 ].value = decrease;
			}
		} else {
			$input[ 0 ].value = decrease;
		}
	}

	/**
	 * Check tickets availability.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.checkAvailability = function() {
		// We're checking availability for all the tickets at once.
		var params = {
			action  : 'ticket_availability_check',
			tickets : obj.getTickets(),
		};

		$.post(
			TribeTickets.ajaxurl,
			params,
			function( response ) {
				var success = response.success;

				// Bail if we don't get a successful response.
				if ( ! success ) {
					return;
				}

				// Get the tickets response with availability.
				var tickets = response.data.tickets;

				// Make DOM updates.
				obj.updateAvailability( tickets );

			}
		);

		// Repeat every 15 seconds
		setTimeout( obj.checkAvailability, 15000 );
	}

	/**
	 * Get the Quantity.
	 *
	 * @since TBD
	 *
	 * @param obj cartItem The cart item to update.
	 *
	 * @returns {number}
	 */
	obj.getQty = function ( $cartItem ) {
		var qty = parseInt( $cartItem.find( obj.selector.itemQuantityInput ).val(), 10 );

		return isNaN( qty ) ? 0 : qty;
	};

	/**
	 * Get the Price.
	 *
	 *
	 * @param obj cartItem     The cart item to update.
	 * @params string cssClass The string of the class to get the price from.
	 *
	 * @returns {number}
	 */
	obj.getPrice = function ( $cartItem ) {
		var price = parseFloat( $cartItem.find( obj.selector.itemPrice ).text() );

		return isNaN( price ) ? 0 : price;
	};

	/**
	 * Get the Currency Formatting for a Provider.
	 *
	 * @since TBD
	 *
	 * @returns {*}
	 */
	obj.getCurrencyFormatting = function () {
		var currency = JSON.parse( TribeCurrency.formatting );

		return currency[obj.tribe_ticket_provider];
	};

	/**
	 * Format the number according to provider settings.
	 * Based off coding fron https://stackoverflow.com/a/2901136.
	 *
	 * @since TBD
	 *
	 * @param number The number to format.
	 *
	 * @returns {string}
	 */
	obj.numberFormat = function ( number ) {
		var decimals      = obj.getCurrencyFormatting().number_of_decimals;
		var dec_point     = obj.getCurrencyFormatting().decimal_point;
		var thousands_sep = obj.getCurrencyFormatting().thousands_sep;

		var n          = !isFinite( +number ) ? 0 : +number;
		var prec       = !isFinite( +decimals ) ? 0 : Math.abs( decimals );
		var sep        = ( 'undefined' === typeof thousands_sep ) ? ',' : thousands_sep;
		var dec        = ( 'undefined' === typeof dec_point ) ? '.' : dec_point;
		var toFixedFix = function ( n, prec ) {
			// Fix for IE parseFloat(0.55).toFixed(0) = 0;
			var k = Math.pow( 10, prec );
			return Math.round( n * k ) / k;
		};

		var s = ( prec ? toFixedFix( n, prec ) : Math.round( n )).toString().split( '.' );

		if ( s[0].length > 3 ) {
			s[0] = s[0].replace( /\B(?=(?:\d{3} )+(?!\d))/g, sep );
		}

		if ( ( s[1] || '' ).length < prec ) {
			s[1] = s[1] || '';
			s[1] += new Array( prec - s[1].length + 1 ).join( '0' );
		}

		return s.join( dec );
	}

	/**
	 * Adds focus effect to ticket block.
	 *
	 * @since TBD
	 *
	 */
	obj.focusTicketBlock = function( input ) {
		$( input ).closest( obj.modalSelector.arItem ).addClass( 'tribe-ticket-item__has-focus' );
	}

	/**
	 * Remove focus effect from ticket block.
	 *
	 * @since TBD
	 *
	 */
	obj.unfocusTicketBlock = function( input ) {
		$( input ).closest( obj.modalSelector.arItem ).removeClass( 'tribe-ticket-item__has-focus' );
	}

	/* Prefill Handling */

	/**
	 * Init the tickets block prefill.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.initPrefill = function() {
		obj.prefillTicketsBlock();
	}

	/**
	 * Init the modal block prefill.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.initModalPrefill = function() {
		$.ajax( {
			type: 'GET',
			data: {'provider': $tribe_ticket.data( 'providerId' )},
			dataType: 'json',
			url: $tribe_ticket.data( 'cart' ),
			success: function ( data ) {
				if ( data.tickets ) {
					obj.prefillModalCart( $tribe_ticket, data.tickets );
				}

				if ( data.meta ) {
					var count = false;
					$.each( data.meta, function( ticket ) {
						var $matches = $tribe_ticket.find( `[data-ticket-id="${ticket.ticket_id}"]`);
						if ( $matches.length ) {
							obj.prefillModalAR( data.meta );
							return;
						}
					});
				}
console.log('local?');
				// If we didn't get meta from the cart, let's fill with sessionStorage.
				var local = obj.getLocal();
				console.log('local');
				console.log(local);
				if ( local.meta ) {
					obj.prefillModalAR( local.meta );
				}
			}
		} );
	}

	/**
	 * Prefills the modal AR fields from supplied data.
	 *
	 * @since TBD
	 *
	 * @param meta Data to fill the form in with.
	 * @param length Starting pointer for partial fill-ins.
	 *
	 * @return void
	 */
	obj.prefillModalAR = function( meta, length ) {
		if ( undefined === meta || 0 >= meta.length ) {
			return;
		}

		if ( undefined === length ) {
			var length = 0;
		}

		var $form = $( obj.modalSelector.arForm );
		var $containers = $form.find( '.tribe-tickets__item__attendee__fields__container' );

		if ( 0 < length ) {
			var removed = meta.splice( 0, length - 1 );
		}

		$.each( meta, function( index, ticket ) {
			var $current_containers = $containers.find( obj.modalSelector.arItem ).filter( `[data-ticket-id="${ticket.ticket_id}"]` );
			if ( ! $current_containers.length ) {
				return;
			}
			var current = 0;
			$.each( ticket.items, function( index, data ) {
				if ( 'object' !== typeof data ) {
					return;
				}

				$.each( data, function( index, value ) {
					var $field = $current_containers.eq( current ).find( `[name*="${index}"]`);
					if ( ! $field.is( ':radio' ) && ! $field.is( ':checkbox' ) ) {
						$field.val( value);
					} else {
						$field.each( function( index ) {
							var $item = $( this );
							if ( value === $item.val() ) {
								$item.prop( 'checked', true );
							}
						});
					}
				});

				current++;
			});
		});
	}

	/**
	 * Prefill the Cart.
	 *
	 * @since TBD
	 *
	 * @returns {*}
	 */
	obj.prefillModalCart = function ( $form, tickets ) {
		$.each( tickets, function ( index, value ) {
			var $item = $form.find( '[data-ticket-id="' + value.ticket_id + '"]' );
			if ( $item ) {
				$item.find( '.tribe-ticket-quantity' ).val( value.quantity );
			}
		} );

	};

	/**
	 * Prefill tickets block from cart.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.prefillTicketsBlock = function() {
		$.ajax({
			type: 'GET',
			url: '/wp-json/tribe/tickets/v1/cart',
			data: {},
			success: function( response ) {
				var tickets = response.tickets;

				if ( ! tickets ) {
					return;
				}

				var $eventCount = 0;

				tickets.forEach(function(ticket) {
					var $ticketRow = $( `.tribe-tickets__item[data-ticket-id="${ticket.ticket_id}"]` );
					var $field = $ticketRow.find( obj.selector.itemQuantityInput );
					if ( $field.length ) {
						$field.val( ticket.quantity );
						$field.trigger( 'change' );
						$eventCount++;
					}

				});

				if ( 0 < $eventCount ) {
					$( '#tribe-tickets__notice__tickets-in-cart' ).show();
				}
			}
		});
	}

	/* sessionStorage ("local") */

	/**
	 * Stores attendee and cart form data to sessionStorage.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.storeLocal = function() {
		var meta  = obj.getMetaForSave();
		sessionStorage.setItem( 'tribe_tickets_attendees-' + obj.postId, window.JSON.stringify( meta ) );

		var tickets  = obj.getTicketsForCart();
		sessionStorage.setItem( 'tribe_tickets_cart-' + obj.postId, window.JSON.stringify( tickets ) );
	}

	/**
	 * Gets attendee and cart form data from sessionStorage.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	obj.getLocal = function( postId ) {
		if ( ! postId ) {
			var postId = obj.postId;
		}

		var meta    = window.JSON.parse( sessionStorage.getItem( 'tribe_tickets_attendees-' + postId ) );
		var tickets = window.JSON.parse( sessionStorage.getItem( 'tribe_tickets_cart-' + postId ) );
		var ret     = {  meta, tickets };

		return ret;
	}

	/**
	 * Clears attendee and cart form data from sessionStorage.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.clearLocal = function( postId ) {
		sessionStorage.removeItem( 'tribe_tickets_attendees-' + postId );
		sessionStorage.removeItem( 'tribe_tickets_cart-' + postId );
	}

	/**
	 * Attempts to hydrate a dynamically-created attendee form "block" from sessionStorage data.
	 *
	 * @since TBD
	 *
	 * @param object data The attendee data.
	 *
	 * @return void
	 */
	obj.maybeHydrateAttendeeBlockFromLocal = function( length ) {
		var local = obj.getLocal();

		if ( ! local ) {
			return;
		}

		var data = local.meta;
		$.ajax( {
			type: 'GET',
			data: {'provider': $tribe_ticket.data( 'providerId' )},
			dataType: 'json',
			url: $tribe_ticket.data( 'cart' ),
			success: function ( data ) {
				var cartSkip = data.meta.length;
				if (length < cartSkip ) {
					obj.prefillModalAR( data.meta, length );

					return;
				} else {
					var $attendeeForm = $( obj.modalSelector.arForm );
					var $newBlocks     = $attendeeForm.find( obj.modalSelector.arItem ).slice( length - 1 );
					if ( ! $newBlocks ) {
						return;
					}

					$newBlocks.find( obj.modalSelector.metaField ).each(
						function() {
							var $this     = $( this );
							var name      = $this.attr( 'name' );
							var storedVal = data[ name ];

							if ( storedVal ) {
								$this.val( storedVal );
							}
						}
					);
				}
			}
		} );
	}

	/* Data Formatting / API Handling */

	/**
	 * Get ticket data to send to cart.
	 *
	 * @since TBD
	 *
	 * @return obj Tickets data object.
	 */
	obj.getTicketsForCart = function() {
		var tickets     = [];
		var $cartForm   = $( obj.modalSelector.cartForm );
		var $ticketRows = $cartForm.find( obj.selector.item );

		$ticketRows.each(
			function() {
				var $this     = $( this );
				var ticket_id = $this.data( 'ticketId' );
				var qty       = $this.find( obj.selector.itemQuantityInput ).val();
				var optout    = $this.find( '[name="attendee[optout]"]' ).val();
				if ( 0 < qty ) {
					var data          = {};
					data['ticket_id'] = ticket_id;
					data['quantity']  = qty;
					data['optout']    = optout;
					tickets.push( data );
				}
			}
		);

		return tickets;
	}

	/**
	 *
	 *
	 * @since TBD
	 *
	 * @return obj Meta data object.
	 */
	obj.getMetaForSave = function() {
		var $arForm     = $( obj.modalSelector.arForm );
		var $ticketRows = $arForm.find( obj.modalSelector.arItem );
		var meta    = [];
		var tempMeta    = [];
		$ticketRows.each(
			function() {
				var data      = {};
				var $row      = $( this );
				var ticket_id = $row.data( 'ticketId' );

				var $fields = $row.find( obj.modalSelector.metaField );

				// Skip tickets with no meta fields
				if ( ! $fields.length ) {
					return;
				}

				if ( ! tempMeta[ ticket_id ] ) {
					tempMeta[ ticket_id ] = {};
					tempMeta[ ticket_id ]['ticket_id'] = ticket_id;
					tempMeta[ ticket_id ][ 'items' ] = [];
				}

				$fields.each(
					function() {
						var $field = $( this );
						var name   = $field.attr( 'name' );
						// Grab everything after the last bracket `[`.
						name       = name.split( '[' );
						name       = name.pop().replace( ']', '' );
						var value  = $field.val();

						// Skip blank fields.
						if ( ! value ) {
							return;
						}

						// Skip unchecked radio/checkboxes.
						if ( $field.is( ':radio' ) || $field.is( ':checkbox' ) ) {
							if ( ! $field.prop( 'checked' ) ) {
								return;
							}
						}

						data[name] = value;
					}
				);

				tempMeta[ ticket_id ]['items'].push(data);
			}
		);

		Object.keys(tempMeta).forEach( function( index ) {
			var newArr = {
				'ticket_id': index,
				'items': tempMeta[index]['items']
			};
			meta.push( newArr );
		});

		return meta;
	}

	/* Validation */

	/**
	 * Validates the entire meta form.
	 * Adds errors to the top of the modal.
	 *
	 * @since TBD
	 *
	 * @param $form jQuery object that is the form we are validating.
	 *
	 * @return boolean If the form validates.
	 */
	obj.validateForm = function( $form ) {
		var $containers     = $form.find( obj.modalSelector.arItem );
		var formValid       = true;
		var invalidTickets  = 0;

		$containers.each(
			function() {
				var $container     = $( this );
				var validContainer = obj.validateBlock( $container );

				if ( ! validContainer ) {
					invalidTickets++;
					formValid = false;
				}
			}
		);

		return [formValid, invalidTickets];
	}

	/**
	 * Validates and adds/removes error classes from a ticket meta block.
	 *
	 * @since TBD
	 *
	 * @param $container jQuery object that is the block we are validating.
	 *
	 * @return boolean True if all fields validate, false otherwise.
	 */
	obj.validateBlock = function( $container ) {
		var $fields = $container.find( obj.modalSelector.metaField );
		var validBlock = true;
		$fields.each(
			function() {
				var $field = $( this );
				var isValidfield = obj.validateField( $field[0] );

				if ( ! isValidfield ) {
					validBlock = false;
				}
			}
		);

		if ( validBlock ) {
			$container.removeClass( 'tribe-ticket-item__has-error' );
		} else {
			$container.addClass( 'tribe-ticket-item__has-error' );
		}

		return validBlock;
	}

	/**
	 * Validate Checkbox/Radio group.
	 * We operate under the assumption that you must check _at least_ one,
	 * but not necessarily all. Also that the checkboxes are all required.
	 *
	 * @since TBD
	 *
	 * @param $group The jQuery object for the checkbox group.
	 *
	 * @return boolean
	 */
	obj.validateCheckboxRadioGroup = function( $group ) {
		var $checkboxes   = $group.find( obj.modalSelector.metaField );
		var checkboxValid = false;
		var required      = true;

		$checkboxes.each(
			function() {
				var $this = $( this );
				if ( $this.is( ':checked' ) ) {
					checkboxValid = true;
				}

				if ( ! $this.prop( 'required' ) ) {
					required = false;
				}
			}
		);

		var valid = ! required || checkboxValid;

		return valid;
	}

	/**
	 * Adds/removes error classes from a single field.
	 *
	 * @since TBD
	 *
	 * @param input DOM Object that is the field we are validating.
	 *
	 * @return boolean
	 */
	obj.validateField = function( input ) {
		var isValidfield = true;
		var $input       = $( input );
		var isValidfield = input.checkValidity();

		if ( ! isValidfield ) {
			var $input = $( input );
			// Got to be careful of required checkbox/radio groups...
			if ( $input.is( ':checkbox' ) || $input.is( ':radio' ) ) {
				var $group = $input.closest( '.tribe-common-form-control-checkbox-radio-group' );

				if ( $group.length ) {
					isValidfield = obj.validateCheckboxRadioGroup( $group );
				}
			} else {
				isValidfield = false;
			}
		}

		if ( ! isValidfield ) {
			$input.addClass( 'ticket-meta__has-error' );
		} else {
			$input.removeClass( 'ticket-meta__has-error' );
		}

		return isValidfield;
	}

	/* Event Handling */

	/**
	 * Handle the number input + and - actions.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.document.on(
		'click touchend',
		'.tribe-tickets__item__quantity__remove, .tribe-tickets__item__quantity__add',
		function( e ) {
			var $input = $( this ).parent().find( 'input[type="number"]' );

			if( $input.is( ':disabled' ) ) {
				return;
			}

			e.preventDefault();

			var originalValue = Number( $input[ 0 ].value );
			var $modalForm    = $input.closest( obj.modalSelector.cartForm );

			// Step up or Step down the input according to the button that was clicked.
			// Handles IE/Edge.
			if ( $( this ).hasClass( 'tribe-tickets__item__quantity__add' ) ) {
				obj.stepUp( $input, originalValue );
			} else {
				obj.stepDown( $input, originalValue );
			}

			obj.updateFooter( $input.closest( 'form' ) );

			// Trigger the on Change for the input (if it has changed) as it's not handled via stepUp() || stepDown().
			if ( originalValue !== $input[ 0 ].value ) {
				$input.trigger( 'change' );
			}

			if ( $modalForm.length ) {
				var $item = $input.closest( obj.selector.item );
				obj.updateTotal( obj.getQty( $item ), obj.getPrice( $item ), $item );
			}
		}
	);

	/**
	 * Remove Item from Cart Modal.
	 *
	 * @since TBD
	 *
	 */
	obj.document.on(
		'click',
		obj.modalSelector.itemRemove,
		function ( e ) {
			e.preventDefault();


			var ticket    = {};
			var $cart     = $( this ).closest( 'form' );
			var $cartItem = $( this ).closest( obj.selector.item );

			$cartItem.find( obj.selector.itemQuantity ).val( 0 );
			$cartItem.fadeOut() ;

			ticket.id    = $cartItem.data( 'ticketId' );
			ticket.qty   = 0;
			ticket.price = obj.getPrice( $cartItem );

			obj.updateTotal( ticket.qty, ticket.price, $cartItem );
			obj.updateFormTotals( $cart );

			$( '.tribe-tickets__item__attendee__fields__container[data-ticket-id="' + ticket.id + '"]' )
				.removeClass( 'tribe-tickets--has-tickets' )
				.find( obj.modalSelector.arItem ).remove();
		}
	);

	/**
	 * Adds focus effect to ticket block.
	 *
	 * @since TBD
	 *
	 */
	obj.document.on(
		'focus',
		'.tribe-ticket .ticket-meta',
		function( e ) {
			var input      = e.target;
			obj.focusTicketBlock( input );
		}
	);

	/**
	 * handles input blur.
	 *
	 * @since TBD
	 *
	 */
	obj.document.on(
		'blur',
		'.tribe-ticket .ticket-meta',
		function( e ) {
			var input      = e.target;
			obj.unfocusTicketBlock( input );
		}
	);

	/**
	 * Handle the Ticket form(s).
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.document.on(
		'change keyup',
		obj.selector.itemQuantityInput,
		function( e ) {
			var $this        = $( this );
			var $ticket      = $this.closest( obj.selector.item );
			var $ticket_id   = $ticket.data( 'ticket-id' );
			var $form        = $this.closest( 'form' );
			var new_quantity = parseInt( $this.val(), 10 );
			new_quantity     = isNaN( new_quantity ) ? 0 : new_quantity;

			e.preventDefault();
			obj.maybeShowOptOut( $ticket, new_quantity );
			obj.updateFooter( $form );
			obj.updateFormTotals( $form );

			// Only disable / enable if is a Tribe Commerce Paypal form.
			if ( 'Tribe__Tickets__Commerce__PayPal__Main' === $form.data( 'provider' ) ) {
				obj.tribeCommerceDisable( new_quantity, $form, $ticket_id );
			}
		}
	);

	/**
	 * Stores to sessionStorage onbeforeunload for accidental refreshes, etc.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.document.on(
		'beforeunload',
		function( e ) {
			if ( tribe.tickets.modal_redirect ) {
				return;
			}

			obj.storeLocal();
		}
	);

	/**
	 * Handle Modal submission.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.document.on(
		'click',
		obj.modalSelector.submit,
		function( e ) {
			e.preventDefault();
			var $button    = $( this );


			var $arForm = $( obj.modalSelector.arForm );
			var isValidForm = obj.validateForm( $arForm );
			var $errorNotice = $( '.tribe-tickets-notice--error' );

			if ( ! isValidForm[ 0 ] ) {
				$( obj.modalSelector.container ).animate( { scrollTop : 0 }, 'slow' );

				$( '.tribe-tickets-notice--error__count' ).text( isValidForm[ 1 ] );
				$errorNotice.show();
				return false;
			}

			$errorNotice.hide();

			// save meta and cart
			var params = {
				provider: obj.commerceSelector[obj.tribe_ticket_provider],
				tickets : obj.getTicketsForCart(),
				meta    : obj.getMetaForSave(),
				post_id : obj.postId,
			};

			$.ajax({
				type: 'POST',
				url: '/wp-json/tribe/tickets/v1/cart',
				data: params,
				success: function( response ) {
					//redirect url
					var url = response.checkout_url;

					if( 'cart-button' === $button.attr( 'name' ) ) {
						url = response.cart_url
					}

					// Clear sessionStorage before redirecting the user.
					obj.clearLocal();
					// Set a var so we don't save what we just erased.
					tribe.tickets.modal_redirect = true;

					window.location.href = url;
				},
				fail: function( response ) {
					// @TODO: add messaging on error?
					return;
				}
			});
		}
	);

	/**
	 * When "Get Tickets" is clicked, update the modal.
	 *
	 * @since TBD
	 *
	 */
	$( te ).on(
		'tribe_dialog_show_ar_modal',
		function ( e, dialogEl, event ) {
			var $modalCart = $( obj.modalSelector.cartForm );
			var $cartItems = $tribe_ticket.find( obj.selector.item );

			$cartItems.each(
				function () {
					var $blockCartItem = $( this );
					var id             = $blockCartItem.data( 'ticketId' );
					var $modalCartItem = $modalCart.find( '[data-ticket-id="' + id + '"]' );

					if ( ! $modalCartItem ) {
						return;
					}

					obj.updateItem( id, $modalCartItem, $blockCartItem );
				}
			);

			obj.initModalPrefill();

			obj.updateFormTotals( $modalCart );
		}
	);

	/**
	 * Handles storing data to local storage
	 */
	$( te ).on(
		'tribe_dialog_close_ar_modal',
		function ( e, dialogEl, event ) {
			obj.storeLocal();
		}
	);

	obj.init();
})( jQuery, tribe.tickets.block, tribe_ev.events );
