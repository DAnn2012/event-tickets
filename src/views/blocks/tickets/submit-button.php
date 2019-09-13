<?php
/**
 * Block: Tickets
 * Submit Button
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/submit-button.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 *
 * @version TBD
 *
 */
?>
<button
	class="tribe-common-c-btn tribe-common-c-btn--small tribe-block__tickets__buy"
	type="submit"
>
	<?php echo esc_html_x( 'Add to cart', 'Add tickets to cart.', 'event-tickets' ); ?>
</button>
