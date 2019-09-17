<?php
/**
 * Block: Tickets
 * Submit Button - Modal
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/submit-button-modal.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 *
 * @version TBD
 *
 */

/* translators: %s is the event or post title the tickets are attached to. */
$title       = sprintf( _x( '%s Tickets', 'Modal title. %s: event name', 'event-tickets' ), get_the_title() );
$button_text = _x( 'Get Tickets', 'Get selected tickets.', 'event-tickets' );
$content     = apply_filters( 'tribe_events_tickets_edd_attendee_registration_modal_content', '<p>Tickets modal needs content, badly.</p>' );

/**
 * Filter Modal Content
 *
 * @since TBD
 *
 * @param string $content a string of default content
 * @param Tribe__Tickets__Editor__Template $template_obj the Template objec
 */
$content     = apply_filters( 'tribe_events_tickets_attendee_registration_modal_content', '<p>Ticket Modal</p>', $this );

$args = [
	'append_target'           => 'body',
	'button_classes'          => [ 'tribe-common-c-btn', 'tribe-common-c-btn--small', 'tribe-tickets__submit' ],
	'button_name'             => $provider_id . '_get_tickets',
	'button_text'             => $button_text,
	'button_type'             => 'submit',
	'close_event'             => 'tribe_dialog_close_ar_modal',
	'content_wrapper_classes' => 'tribe-common tribe-dialog__wrapper tribe-modal__wrapper--ar',
	'show_event'              => 'tribe_dialog_show_ar_modal',
	'title'                   => $title,
	'title_classes'           => [
		'tribe-dialog__title',
		'tribe-modal__title',
		'tribe-common-h5',
		'tribe-common-h--alt',
		'tribe-modal--ar__title',
	],
];

tribe( 'dialog.view' )->render_modal( $content, $args );
$event_id = get_the_ID();
/** @var Tribe__Tickets__Editor__Template $template */
$template = tribe( 'tickets.editor.template' );
$provider_id = Tribe__Tickets__Tickets::get_event_ticket_provider( $post_id );
$provider    = call_user_func( [ $provider_id, 'get_instance' ] );
$obj_tickets = $provider->get_tickets( $event_id );
foreach( $obj_tickets as $ticket ) {
	$ticket_data = [
		'id'       => $ticket->ID,
		'qty'      => 1,
		'provider' => $provider,
	];

	$tickets_content[] = $ticket_data;
}

$template->template( 'registration-js/attendees/content', array( 'event_id' => $event_id, 'tickets' => $tickets_content ) );
