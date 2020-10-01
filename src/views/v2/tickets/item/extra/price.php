<?php
/**
 * Block: Tickets
 * Extra column, price
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/extra/price.php
 *
 * See more documentation about our views templating system.
 *
 * @link    http://m.tri.be/1amp
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $post_id       The Post object ID.
 * @var Tribe__Tickets__Ticket_Object $ticket        The ticket object.
 * @var Tribe__Tickets__Tickets $provider            The tickets provider class.
 * @var string $provider_id                          The tickets provider class name.
 * @var Tribe__Tickets__Commerce__Currency $currency The Currency Object
 */

$classes = [
	'tribe-common-b2',
	'tribe-common-b1--min-medium',
	'tribe-tickets__item__extra__price',
];

$has_suffix = ! empty( $ticket->price_suffix );

?>
<div <?php tribe_classes( $classes ); ?>>
	<?php if ( ! empty( $ticket->on_sale ) ) : ?>
		<span class="tribe-common-b2 tribe-tickets__original_price">
			<?php echo $currency->get_formatted_currency_with_symbol( $ticket->regular_price, $post_id, $provider_id ); ?>
		</span>
	<?php endif; ?>
	<span class="tribe-tickets__sale_price">
		<?php echo $currency->get_formatted_currency_with_symbol( $ticket->price, $post_id, $provider_id ); ?>
		<?php if ( $has_suffix ) : ?>
			<span class="tribe-tickets__sale-price-suffix tribe-common-b2">
				<?php
				// This suffix contains HTML to be output.
				// phpcs:ignore
				echo $ticket->price_suffix;
				?>
			</span>
		<?php endif; ?>
	</span>
</div>
