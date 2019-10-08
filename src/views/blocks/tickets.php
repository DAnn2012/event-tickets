<?php
/**
 * Block: Tickets
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @since 4.10.8 Updated loading logic for including a renamed template.
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this
 */

/** @var Tribe__Tickets__Commerce__Currency $currency */
$currency            = tribe( 'tickets.commerce.currency' );
$cart_classes        = [ 'tribe-block', 'tribe-tickets', 'tribe-common' ];
$cart_url            = $this->get( 'cart_url' );
$has_tickets_on_sale = $this->get( 'has_tickets_on_sale' );
$is_sale_past        = $this->get( 'is_sale_past' );
$provider            = $this->get( 'provider' );
$provider_id         = $this->get( 'provider_id' );
$tickets             = $this->get( 'tickets', [] );
$tickets_on_sale     = $this->get( 'tickets_on_sale' );

// We don't display anything if there is no provider or tickets
if ( ! $provider || empty( $tickets ) ) {
	return false;
}

$html = $this->template( 'blocks/attendees/order-links', [], false );

if ( empty( $html ) ) {
	$html = $this->template( 'blocks/attendees/view-link', [], false );
}

echo $html;
?>

<form
	id="tribe-tickets"
	action="<?php echo esc_url( $cart_url ) ?>"
	<?php tribe_classes( $cart_classes ); ?>
	method="post"
	enctype='multipart/form-data'
	data-provider="<?php echo esc_attr( $provider->class_name ); ?>"
	autocomplete="off"
	data-provider-id="<?php echo esc_attr( $provider->orm_provider ); ?>"
	novalidate
>
	<h2 class="tribe-common-h4 tribe-common-h--alt tribe-tickets__title"><?php esc_html_e( 'Tickets', 'event-tickets' ); ?></h2>
	<p id="tribe-tickets__notice__tickets-in-cart" class="tribe-common-b3 tribe-tickets-notice tribe-tickets-notice--barred tribe-tickets-notice--barred-left">
		<?php esc_html_e( 'The numbers below include tickets for this event already in your cart. Clicking "Get Tickets" will allow you to edit any existing attendee information as well as change ticket quantities.', 'event-tickets' ); ?>
	</p>
	<?php $this->template( 'blocks/tickets/commerce/fields', [ 'provider' => $provider, 'provider_id' => $provider_id ] ); ?>
	<?php if ( $has_tickets_on_sale ) : ?>
	<!-- begin tickets_on_sale -->
		<?php foreach ( $tickets_on_sale as $key => $ticket ) : ?>
			<?php $ticket_symbol = $currency->get_currency_symbol( $ticket->ID, true ); ?>
			<?php $this->template( 'blocks/tickets/item', [ 'ticket' => $ticket, 'key' => $key, 'currency_symbol' => $ticket_symbol ] ); ?>
		<?php endforeach; ?>
		<?php
		// We're assuming that all the currency is the same here.
		$currency_symbol     = $currency->get_currency_symbol( $tickets[0]->ID, true );
		$this->template( 'blocks/tickets/footer', [ 'tickets' => $tickets, 'currency_symbol' => $currency_symbol ] );
		?>
		<!-- end tickets_on_sale -->
	<?php else : ?>
		<?php echo $this->template( 'blocks/tickets/item-inactive', [ 'is_sale_past' => $is_sale_past ] ); ?>
	<?php endif; ?>
	<?php
		ob_start();
		/**
		 * Allows filtering of extra classes used on the tickets-block loader
		 *
		 * @since  TBD
		 *
		 * @param  array $classes The array of classes that will be filtered.
		 */
		$classes = apply_filters( 'tribe_tickets_block_loader_classes', [ 'tribe-tickets-loader__tickets-block' ] );
		include Tribe__Tickets__Templates::get_template_hierarchy( 'components/loader.php' );
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
	?>
	<!-- end #tribe-tickets -->
</form>
