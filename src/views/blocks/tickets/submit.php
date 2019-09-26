<?php
/**
 * Block: Tickets
 * Submit
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/submit.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 *
 * @version TBD
 *
 */
$provider = $this->get( 'provider' );
$must_login = ! is_user_logged_in() && $provider->login_required();
$event_id = $this->get( 'event_id' );
$event = get_post( $event_id );
$is_event_page = class_exists( 'Tribe__Events__Main' ) && Tribe__Events__Main::POSTTYPE === $event->post_type;

/* var Tribe__Tickets__Attendee_Registration__Main $attendee_reg */
$attendee_reg = tribe( 'tickets.attendee_registration' );
$on_registration_page = $attendee_reg->is_on_page() || $attendee_reg->is_using_shortcode();
?>

<?php if ( $is_event_page ) : ?>
	<?php if ( $must_login ) : ?>
		<?php $this->template( 'blocks/tickets/submit-login' ); ?>
	<?php elseif ( $is_event_page && Tribe__Settings_Manager::get_option( 'ticket-attendee-modal' ) ) : ?>
		<?php $this->template( 'blocks/tickets/submit-button-modal' ); ?>
	<?php else : ?>
		<?php $this->template( 'blocks/tickets/submit-button' ); ?>
	<?php endif; ?>
<?php elseif( $on_registration_page ) : ?>
	<?php $this->template( 'blocks/tickets/registration/attendee/submit.php' ); ?>
<?php endif; ?>

