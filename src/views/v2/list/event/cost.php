<?php
/**
 * View: List Single Event Cost
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events/views/v2/list/event/cost.php
 *
 * See more documentation about our views templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version TBD
 *
 * @var WP_Post $event The event post object with properties added by the `tribe_get_event` function.
 *
 * @see tribe_get_event() For the format of the event object.
 *
 */

if ( empty( $event->cost ) ) {
	return;
}

/*
	Here we do what we want with
	$event->tickets_data
*/

// @TODO @fe @juanfra: Come back to this when there's a decision about template tags.

?>
<div class="tribe-events-c-small-cta tribe-events-calendar-list__event-cost">
	<a href="#" class="tribe-events-c-small-cta__link tribe-common-cta tribe-common-cta--thin-alt">
		<?php esc_html_e( 'Buy Now', 'event-tickets' ); ?>
	</a>
	<span class="tribe-events-c-small-cta__price">
		<?php echo esc_html( $event->cost ) ?>
	</span>
</div>
