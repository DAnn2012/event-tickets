<?php
/**
 * AR: Mini-Cart
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration-js/mini-cart.php
 *
 * @since TBD
 *
 * @version TBD
 *
 */
$provider = $this->get( 'provider' );
$tickets = $this->get( 'tickets' );
// We don't display anything if there is no provider or tickets
if ( ! $provider || empty( $tickets ) ) {
	return false;
}

$cart_classes = [
	'tribe-mini-cart',
	'tribe-common',
];


/** @var Tribe__Tickets__Commerce__Currency $currency */
$currency = tribe( 'tickets.commerce.currency' );

?>
<form
	id="tribe-mini-cart"
	action="<?php echo esc_url( $cart_url ) ?>"
	<?php tribe_classes( $cart_classes ); ?>
	method="post"
	enctype='multipart/form-data'
	data-provider="<?php echo esc_attr( $provider->class_name ); ?>"
	autocomplete="off"
	novalidate
>
	<?php $template_obj->template( 'blocks/tickets/commerce/fields', [ 'provider' => $provider, 'provider_id' => $provider_id ] ); ?>

	<?php if ( $has_tickets_on_sale ) : ?>
		<?php foreach ( $tickets_on_sale as $key => $ticket ) : ?>
		<?php $currency_symbol     = $currency->get_currency_symbol( $ticket->ID, true ); ?>
			<?php $template_obj->template( 'blocks/tickets/item', [ 'ticket' => $ticket, 'key' => $key, 'is_mini' => true, 'currency_symbol' => $currency_symbol ] ); ?>
		<?php endforeach; ?>
	<?php endif; ?>
	<?php $template_obj->template( 'blocks/tickets/footer', [ 'is_mini' => true ] ); ?>
</form>
