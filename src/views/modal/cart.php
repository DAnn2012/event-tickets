<?php
/**
 * Modal: Cart
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/modal/cart.php
 *
 * @since TBD
 *
 * @version TBD
 *
 */

$cart_classes = [
	'tribe-modal-cart',
	'tribe-modal__cart',
	'tribe-common',
];

// We don't display anything if there is no provider or tickets
if ( ! $provider || empty( $tickets ) ) {
	return false;
}

?>
<form
	id="tribe-modal__cart"
	action="<?php echo esc_url( $cart_url ) ?>"
	class="<?php echo esc_attr( implode( ' ', $cart_classes ) ); ?>"
	method="post"
	enctype='multipart/form-data'
	data-provider="<?php echo esc_attr( $provider->class_name ); ?>"
	novalidate
>
	<?php $template_obj->template( 'blocks/tickets/commerce/fields', [ 'provider' => $provider, 'provider_id' => $provider_id ] ); ?>

	<?php if ( $has_tickets_on_sale ) : ?>
		<?php foreach ( $tickets_on_sale as $key => $ticket ) : ?>
			<?php $template_obj->template( 'blocks/tickets/item', [ 'ticket' => $ticket, 'key' => $key, 'is_modal' => true ] ); ?>
		<?php endforeach; ?>
	<?php endif; ?>
	<?php $template_obj->template( 'blocks/tickets/footer', [ 'is_modal' => true ] ); ?>
</form>
