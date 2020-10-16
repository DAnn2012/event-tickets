<?php
/**
 * Block: Tickets
 * Commerce Fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/commerce/fields.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 * @version TBD
 *
 * @var Tribe__Tickets__Tickets $provider    The tickets provider class.
 * @var string                  $provider_id The tickets provider class name.
 */

$this->template( 'v2/tickets/commerce/fields/' . $provider->orm_provider );

?>
<input name="provider" value="<?php echo esc_attr( $provider->class_name ); ?>" class="tribe-tickets-provider" type="hidden">
