( function( window, $ ) {
	var $table = $( document.getElementById( 'tribe_ticket_list_table' ) ).find( ' tbody' ),
		enable_width = '400px';

	/**
	* Implemnts jQuery drag-n-drop for the ticket table.
	* Stores order in the #tickets_order field.
	*
	* @param jQuery object $element parent element to make sortable ( var $table above )
	*/
	function make_sortable( $element ) {
		// If we don't have at least 2 sortable items, don't sort.
		if ( 2 > $element.find( 'tr:not(.Tribe__Tickets__RSVP)' ).length ) {
			return;
		}

		$element.sortable({
			cursor: 'move',
			items: 'tr:not(.Tribe__Tickets__RSVP)',
			forcePlaceholderSize: true,
			update: function() {
				data = $(this).sortable( 'toArray', { key: 'order[]', attribute: 'data-ticket-order-id' } );

				// Strip the text .sortable() requires
				for ( i = 0, len = data.length; i < data.length; i++ ) {
					data[i] = data[i].replace( 'order_', '');
				}

				document.getElementById( 'tribe_tickets_order' ).value = data;
			}
		});
		$element.disableSelection();
		$element.find( '.table-header' ).disableSelection();
		$element.sortable( 'option', 'disabled', false );
	}

	$( document ).ready( function () {
		// init if we're not on small screens
		if ( window.matchMedia( '( min-width: 400px )' ).matches ) {
			 make_sortable( $table );
		}

		// disable/init depending on screen size
		$( window ).on( 'resize', function() {
			if ( window.matchMedia( '( min-width: 400px )' ).matches ) {
				if ( ! $( $table ).hasClass( 'ui-sortable' ) ) {
					make_sortable( $table );
				}
			} else {
				if ( $( $table ).hasClass( 'ui-sortable' ) ) {
					$( $table ).sortable( 'option', 'disabled', true );
				}
			}
		});
	});



})( window, jQuery );
