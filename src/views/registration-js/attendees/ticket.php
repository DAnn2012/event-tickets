<?php
/**
 * This template renders the attendee registration block for each ticket
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration-js/attendees/ticket.php
 *
 * @since TBD
 *
 * @version TBD
 *
 */

?>
<script type="text/html" id="tmpl-tribe-registration--<?PHP ECHO esc_attr( $ticket['id'] ); ?>">
	<?php
	$ticket_qty = $ticket['qty'];
	$post           = get_post( $ticket['id'] );
	?>
	<h3 class="tribe-common-h5 tribe-common-h5--min-medium tribe-common-h--alt tribe-ticket__heading"><?php echo get_the_title( $post->ID ); ?></h3>
	<?php // go through each attendee ?>
	<?php while ( 0 < $ticket_qty ) : ?>
		<?php
			/**
			* @var Tribe__Tickets_Plus__Meta $meta
			*/
			$fields     = $meta->get_meta_fields_by_ticket( $post->ID );
			$saved_meta = $storage->get_meta_data_for( $post->ID );

			$args = array(
				'event_id'   => $event_id,
				'ticket'     => $post,
				'fields'     => $fields,
				'saved_meta' => $saved_meta,
			);

			$this->template( 'registration-js/attendees/fields', $args );
			$ticket_qty--;
		?>
	<?php endwhile; ?>
</script>
