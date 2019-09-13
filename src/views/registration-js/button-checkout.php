<?php
/**
 * This template renders the attendee registration checkout button
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration-js/button-checkout.php
 *
 * @since TBD
 *
 * @version TBD
 *
 */
if ( ! $checkout_url ) {
	return;
}
?>
<form
	class="tribe-tickets__registration__checkout"
	action="<?php echo esc_url( $checkout_url ); ?>"
	method="post"
>
	<input type="hidden" name="tribe_tickets_checkout" value="1" />
	<button
		type="submit"
		class="button-primary tribe-tickets__registration__checkout__submit"
		<?php if ( $cart_has_required_meta && ! $is_meta_up_to_date ) : ?>
		disabled
		<?php endif; ?>
	>
		<?php esc_html_e( 'Checkout', 'event-tickets' ); ?>
	</button>
</form>
