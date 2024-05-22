<?php
/**
 * Template to render a Layout card.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Layout_Card $card The Layout card to render.
 */

use TEC\Tickets\Seating\Admin\Tabs\Layout_Card;

?>
<div class="tec-tickets__tab__cards__item">
	<div class="tec-tickets__tab__cards__item-thumbnail">
		<img src="<?php echo esc_url( $card->get_screenshot_url() ); ?>"
			alt="<?php echo esc_attr( $card->get_name() ); ?>">
	</div>
	<div class="tec-tickets__tab__cards__item-content">
		<div class="tec-tickets__tab__cards__item-title"><?php echo esc_html( $card->get_name() ); ?></div>
		<div class="tec-tickets__tab__cards__item-capacity">
			<?php
				echo esc_html(
					sprintf(
					/* translators: %s: the capacity of the Layout */
						_x( '%s seats', 'Layout seats count', 'event-tickets' ),
						number_format_i18n( $card->get_seats() )
					)
				);
				?>
		</div>
		<div class="tec-tickets__tab__cards__item-actions">
			<a class="button button-secondary edit-Layout" href="#">
				<?php esc_html_e( 'Edit', 'event-tickets' ); ?>
			</a>
			<a class="delete-Layout" href="#">
				<?php esc_html_e( 'Delete', 'event-tickets' ); ?>
			</a>
		</div>
	</div>
</div>
