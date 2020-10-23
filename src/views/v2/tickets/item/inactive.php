<?php
/**
 * Block: Tickets
 * Inactive Ticket Item
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/inactive.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 * @version TBD
 *
 * @var bool $has_tickets_on_sale Whether the event has any tickets on sale.
 * @var bool $is_sale_past        Whether the ticket is past the end sale date.
 */

// Bail if there are tickets on sale.
if ( ! empty( $has_tickets_on_sale ) ) {
	return;
}

$classes = [
	'tribe-tickets__tickets-item',
	'tribe-tickets__tickets-item--inactive',
];

$message = $is_sale_past
	/* translators: %s: Tickets label */
	? sprintf( __( '%s are no longer available', 'event-tickets' ), tribe_get_ticket_label_plural( 'event-tickets' ) )
	/* translators: %s: Tickets label */
	: sprintf( __( '%s are not yet available', 'event-tickets' ), tribe_get_ticket_label_plural( 'event-tickets' ) );
?>
<div <?php tribe_classes( $classes ); ?>>
	<div
		class="tribe-tickets__tickets-item-content tribe-tickets__tickets-item-content--inactive"
	>
		<?php echo esc_html( $message ); ?>
	</div>
</div>
