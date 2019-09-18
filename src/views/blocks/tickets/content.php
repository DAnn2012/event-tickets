<?php
/**
 * Block: Tickets
 * Content
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/content.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version TBD
 *
 */

$ticket = $this->get( 'ticket' );

$context = array(
	'ticket' => $ticket,
	'key' => $this->get( 'key' ),
	'is_modal' => $this->get( 'is_modal' ),
);
?>
<?php $this->template( 'blocks/tickets/content-title', $context ); ?>
<?php $this->template( 'blocks/tickets/content-description', $context ); ?>
<?php $this->template( 'blocks/tickets/extra', $context ); ?>
