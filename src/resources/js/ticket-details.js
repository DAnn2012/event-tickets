var tribe_ticket_details = tribe_ticket_details || {};

( function( $, obj ) {
	'use strict';

	obj.init = function( detailsElems ) {
		obj.event_listeners();
	}

	obj.selectors = [
		'.tribe-block__tickets__item__details__summary--more',
		'.tribe-block__tickets__item__details__summary--less',
	];

	obj.event_listeners = function() {
		// Add keyboard support for enter key.
		$( document ).on( 'keyup', obj.selectors, function( event ) {
			// Toggle open like click does.
			if ( 13 === event.keyCode ) {
				obj.toggle_open( event.target );
			}
		} );

		$( document ).on( 'click', obj.selectors, function( event ) {
			obj.toggle_open( event.target );
		} );
	}

	obj.toggle_open = function( trigger ) {
		if( ! trigger ) {
			return;
		}

		var $parent = $( trigger ).closest( '.tribe-block__tickets__item__details__summary' );
		var $target = $( document.getElementById( trigger.getAttribute( 'aria-controls' ) ) );

		if ( ! $target || ! $parent ) {
			return;
		}

		event.preventDefault();
		// Let our CSS handle the hide/show. Also allows us to make it responsive.
		var onOff = ! $parent.hasClass( 'tribe__details--open' );
		$parent.toggleClass( 'tribe__details--open', onOff );
		$target.toggleClass( 'tribe__details--open', onOff );
	}

	$( document ).ready(
		function() {
			var detailsElems = document.querySelectorAll( '.tribe-block__tickets__item__details__summary' );

			// details element not present
			if ( ! detailsElems.length ) {
				return;
			}

			obj.init( detailsElems );
		}
	);

} )( jQuery, tribe_ticket_details );
