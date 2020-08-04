/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since TBD
 *
 * @type {PlainObject}
 */
tribe.tickets = tribe.tickets || {};
tribe.tickets.rsvp = tribe.tickets.rsvp || {};

/**
 * Configures RSVP block Object in the Global Tribe variable
 *
 * @since  TBD
 *
 * @type {PlainObject}
 */
tribe.tickets.rsvp.block = {};

/**
 * Initializes in a Strict env the code that manages the RSVP block.
 *
 * @since TBD
 *
 * @param  {PlainObject} $   jQuery
 * @param  {PlainObject} obj tribe.tickets.rsvp.block
 *
 * @return {void}
 */
( function( $, obj ) {
	'use strict';
	var $document = $( document );

	/**
	 * Selectors used for configuration and setup
	 *
	 * @since TBD
	 *
	 * @type {PlainObject}
	 */
	obj.selectors = {
		container: '.tribe-tickets__rsvp-wrapper',
		rsvpForm: 'form[name~="tribe-tickets-rsvp-form"]',
		goingButton: '.tribe-tickets__rsvp-actions-button-going',
		notGoingButton: '.tribe-tickets__rsvp-actions-button-not-going',
		cancelButton: '.tribe-tickets__rsvp-form-button--cancel',
		errorMessage: '.tribe-tickets__form-message--error',
		hiddenElement: '.tribe-common-a11y-hidden',
		displayToggle: '.tribe-tickets__rsvp-actions-success-going-toggle-input',
	};

	/**
	 * Binds events for the going button.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 *
	 * @return {void}
	 */
	obj.bindGoing = function( $container ) {
		var data  = {};

		var rsvpId = $container.data( 'rsvp-id' );

		var $goingButton = $container.find( obj.selectors.goingButton );

		$goingButton.each( function( index, button ) {
			$( button ).on( 'click', function() {
				data = {
					action: 'tribe_tickets_rsvp_handle',
					ticket_id: rsvpId,
					step: 'going',
				};

				tribe.tickets.rsvp.manager.request( data, $container );
			} );
		} );
	};

	/**
	 * Binds events for the not going button.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 *
	 * @return {void}
	 */
	obj.bindNotGoing = function( $container ) {
		var data  = {};

		var rsvpId = $container.data( 'rsvp-id' );

		var $notGoingButton = $container.find( obj.selectors.notGoingButton );

		$notGoingButton.each( function( index, button ) {
			$( button ).on( 'click', function() {
				data = {
					action: 'tribe_tickets_rsvp_handle',
					ticket_id: rsvpId,
					step: 'not-going',
				};

				tribe.tickets.rsvp.manager.request( data, $container );
			} );
		} );
	};

	/**
	 * Binds events for the cancel button.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 *
	 * @return {void}
	 */
	obj.bindCancel = function( $container ) {
		var data  = {};
		var rsvpId = $container.data( 'rsvp-id' );
		var $cancelButton = $container.find( obj.selectors.cancelButton );

		$cancelButton.each( function( index, button ) {
			$( button ).on( 'click', function() {

				if ( ! confirm( TribeRsvp.cancelText ) ) {
					return;
				}

				data = {
					action: 'tribe_tickets_rsvp_handle',
					ticket_id: rsvpId,
					step: null,
				};

				tribe.tickets.rsvp.manager.request( data, $container );
			} );
		} );
	};

	/**
	 * Handle the RSVP toggle for listing in public attendee list.
	 *
	 * @since TBD
	 *
	 * @param {event} e submission event
	 */
	obj.handleDisplayToggle = function( e ) {
		e.preventDefault();

		const $input = $( e.target );
		const rsvpId = $input.data( 'rsvp-id' );
		const checked = $input.prop( 'checked' );
		const attendeeIds = $input.data( 'attendee-ids' );
		const nonce = $input.data( 'opt-in-nonce' );
		const $container = e.data.container;

		var data = {
			action: 'tribe_tickets_rsvp_handle',
			ticket_id: rsvpId,
			step: 'opt-in',
			opt_in: checked,
			opt_in_none: nonce,
			attendee_ids: attendeeIds,
		};

		tribe.tickets.rsvp.manager.request( data, $container );
	};

	/**
	 * Handle the RSVP form submission
	 *
	 * @since TBD
	 *
	 * @param {event} e submission event
	 */
	obj.handleSubmission = function( e ) {
		e.preventDefault();

		const $form = $( this );
		const $container = $form.closest( obj.selectors.container );
		const rsvpId = $form.data( 'rsvp-id' );
		const params = $form.serializeArray();

		var data = {
			action: 'tribe_tickets_rsvp_handle',
			ticket_id: rsvpId,
			step: 'success',
		};

		$( params ).each( function( index, object ) {
			data[ object.name ] = object.value;
		} );

		tribe.tickets.rsvp.manager.request( data, $container );
	};

	/**
	 * Binds events for the RSVP form.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 *
	 * @return {void}
	 */
	obj.bindForm = function( $container ) {
		var $rsvpForm = $container.find( obj.selectors.rsvpForm );

		$rsvpForm.each( function( index, form ) {
			$( form ).on( 'submit', obj.handleSubmission );
		} );
	};

	/**
	 * Binds events for the display in public attendee toggle.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 *
	 * @return {void}
	 */
	obj.bindDisplayToggle = function( $container ) {
		const $displayToggle = $container.find( obj.selectors.displayToggle );

		$displayToggle.on(
			'input',
			{ container: $container },
			obj.handleDisplayToggle
		);
	};

	/**
	 * Unbinds events.
	 *
	 * @since TBD
	 *
	 * @param  {Event}       event    event object for 'beforeAjaxSuccess.tribeTicketsRsvp' event
	 * @param  {jqXHR}       jqXHR    Request object
	 * @param  {PlainObject} settings Settings that this request was made with
	 *
	 * @return {void}
	 */
	obj.unbindEvents = function( event, jqXHR, settings ) {
		var $container = event.data.container;
		var $goingButton = $container.find( obj.selectors.goingButton );
		var $notGoingButton = $container.find( obj.selectors.notGoingButton );
		var $cancelButton = $container.find( obj.selectors.cancelButton );
		var $rsvpForm = $container.find( obj.selectors.rsvpForm );
		const $displayToggle = $container.find( obj.selectors.displayToggle );

		$goingButton.off();
		$notGoingButton.off();
		$cancelButton.off();
		$rsvpForm.off();
		$displayToggle.off();
	};

	/**
	 * Binds events for container.
	 *
	 * @since TBD
	 *
	 * @param {jQuery}  $container jQuery object of object of the RSVP container.
	 *
	 * @return {void}
	 */
	obj.bindEvents = function( $container ) {

		obj.bindGoing( $container );
		obj.bindNotGoing( $container );
		obj.bindCancel( $container );
		obj.bindForm( $container );
		obj.bindDisplayToggle( $container );

		$container.on(
			'beforeAjaxSuccess.tribeTicketsRsvp',
			{ container: $container },
			obj.unbindEvents
		);
	};

	/**
	 * Initialize RSVP events.
	 *
	 * @since TBD
	 *
	 * @param {Event}   event      event object for 'afterSetup.tribeTicketsRsvp' event
	 * @param {integer} index      jQuery.each index param from 'afterSetup.tribeTicketsRsvp' event.
	 * @param {jQuery}  $container jQuery object of view container.
	 *
	 * @return {void}
	 */
	obj.init = function( event, index, $container ) {
		obj.bindEvents( $container );
	};

	/**
	 * Handles the initialization of the RSVP block events when Document is ready.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	obj.ready = function() {
		$document.on(
			'afterSetup.tribeTicketsRsvp',
			tribe.tickets.rsvp.manager.selectors.container,
			obj.init
		);
	};

	// Configure on document ready.
	$document.ready( obj.ready );
} )( jQuery, tribe.tickets.rsvp.block );
