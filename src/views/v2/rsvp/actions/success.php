<?php
/**
 * Block: RSVP
 * Actions - Success
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/actions/success.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp                The rsvp ticket object.
 * @var int                           $post_id             The post ID that the ticket belongs to.
 * @var string                        $order_status        The order status of the RSVP that was made.
 * @var string                        $opt_in_attendee_ids The list of attendee IDs to send.
 * @var string                        $opt_in_nonce        The nonce for opt-in AJAX requests.
 * @var boolean                       $opt_in_checked      Whether the opt-in field should be checked.
 *
 * @since 4.12.3
 * @version 4.12.3
 */

$toggle_id = 'toggle-rsvp-' . $rsvp->ID;

/**
 * Allow filtering of whether to show the opt-in option for attendees.
 *
 * @since 4.5.2
 * @since TBD Added $post_id and $ticket_id parameters.
 *
 * @param bool $hide_attendee_list_optout Whether to hide attendees list opt-out.
 * @param int  $post_id                   The post ID that the ticket belongs to.
 * @param int  $ticket_id                 The ticket ID.
 */
$hide_attendee_list_optout = apply_filters( 'tribe_tickets_hide_attendees_list_optout', false, $post_id, $rsvp->ID );

if ( 'yes' !== $order_status ) {
	$hide_attendee_list_optout = true;
}
?>
<div class="tribe-tickets__rsvp-actions-success">

	<?php $this->template( 'v2/rsvp/actions/success/title' ); ?>

	<?php if ( false === $hide_attendee_list_optout ) : ?>
		<div class="tribe-tickets__rsvp-actions-success-going-toggle tribe-common-form-control-toggle">
			<input
				class="tribe-common-form-control-toggle__input tribe-tickets__rsvp-actions-success-going-toggle-input"
				id="<?php echo esc_attr( $toggle_id ); ?>"
				name="toggleGroup"
				type="checkbox"
				value="toggleOne"
				<?php checked( $opt_in_checked ); ?>
				data-rsvp-id="<?php echo esc_attr( $rsvp->ID ); ?>"
				data-attendee-ids="<?php echo esc_attr( $opt_in_attendee_ids ); ?>"
				data-opt-in-nonce="<?php echo esc_attr( $opt_in_nonce ); ?>"
			/>
			<label
				class="tribe-common-form-control-toggle__label tribe-tickets__rsvp-actions-success-going-toggle-label"
				for="<?php echo esc_attr( $toggle_id ); ?>"
			>
				<span
					data-js="tribe-tickets-tooltip"
					data-tooltip-content="#tribe-tickets-tooltip-content-<?php echo esc_attr( $rsvp->ID ); ?>"
					aria-describedby="tribe-tickets-tooltip-content-<?php echo esc_attr( $rsvp->ID ); ?>"
				>
					<?php
					echo wp_kses_post(
						sprintf(
							// Translators: 1: opening span. 2: Closing span.
							_x(
								'Show me on public %1$sattendee list%2$s',
								'Toggle for RSVP attendee list.',
								'event-tickets'
							),
							'<span class="tribe-tickets__rsvp-actions-success-going-toggle-label-underline">',
							'</span>'
						)
					);
					?>
				</span>
			</label>
			<?php $this->template( 'v2/rsvp/actions/success/tooltip', [ 'rsvp' => $rsvp ] ); ?>
		</div>
	<?php endif; ?>
</div>
