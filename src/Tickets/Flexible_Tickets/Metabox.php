<?php
/**
 * Handles the modifications to the Tickets metabox required to support Series Passes.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Templates\Admin_Views;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;
use Tribe__Date_Utils as Dates;

/**
 * Class Metabox.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Metabox {

	/**
	 * A reference to the Admin Views handler for Flexible Tickets.
	 *
	 * @since TBD
	 *
	 * @var Admin_Views
	 */
	private Admin_Views $admin_views;

	/**
	 * Metabox constructor.
	 *
	 * since TBD
	 *
	 * @param Admin_Views $admin_views A reference to the Admin Views handler for Flexible Tickets.
	 */
	public function __construct( Admin_Views $admin_views ) {
		$this->admin_views = $admin_views;
	}

	/**
	 * Renders the button to toggle the Series Pass form.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID context of the metabox.
	 *
	 * @return void
	 */
	public function render_form_toggle( int $post_id ) {
		$post = get_post( $post_id );

		if ( ! ( $post instanceof WP_Post && $post->post_type === Series_Post_Type::POSTTYPE ) ) {
			return;
		}

		$ticket_providing_modules = array_diff_key( Tickets::modules(), [ RSVP::class => true ] );
		$this->admin_views->template( 'series-pass-form-toggle', [
			'disabled' => count( $ticket_providing_modules ) === 0,
		] );
	}

	/**
	 * Updates the panels data to add the end date help text and the end date and time values.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data      The panels data.
	 * @param int                 $ticket_id The post ID of the Series Pass.
	 *
	 * @return array<string,mixed> The panels data with the end date help text and the end date and time values.
	 */
	public function update_panel_data( array $data, int $ticket_id ): array {
		$data['ticket_end_date_help_text'] = esc_attr_x(
			'If you do not set an end sale date, passes will be available until the last event in the Series.',
			'Help text for the end date field in the Series Passes meta box.',
			'event-tickets'
		);

		$set_end_date = get_post_meta( $ticket_id, '_ticket_end_date', true );
		$set_end_time = get_post_meta( $ticket_id, '_ticket_end_time', true );

		$datepicker_format       = Dates::datepicker_formats( Dates::get_datepicker_format_index() );
		$data['ticket_end_date'] = $set_end_date ? Dates::date_only( $set_end_date, false, $datepicker_format ) : '';
		$data['ticket_end_time'] = $set_end_time ? Dates::time_only( $set_end_time ) : '';

		return $data;
	}

	/**
	 * Prints a notice letting the user know that the event is part of a Series
	 * and Series Passes should be edited from the Series edit screen.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID context of the metabox.
	 *
	 * @return void
	 */
	public function display_pass_notice( int $post_id ): void {
		$series_ids = tec_series()->where( 'event_post_id', $post_id )->get_ids();

		if ( ! count( $series_ids ) ) {
			return;
		}

		$series = reset( $series_ids );

		$this->admin_views->template( 'series-pass-event-notice', [
			'series_edit_link' => get_edit_post_link( $series ),
			'series_title'     => get_the_title( $series ),
		] );
	}

	/**
	 * Prints the link to the Series edit screen in the context of the Ticket list,
	 * replacing the default Ticket edit actions.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_post_id The post ID of the Series Pass.
	 *
	 * @return void
	 */
	public function render_link_to_series( int $ticket_post_id ): void {
		$this->admin_views->template( 'series-pass-edit-link', [
			'series_edit_link' => get_edit_post_link( $ticket_post_id ),
		] );
	}

	/**
	 * Prints the Series Pass icon in the context of the Ticket list.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function print_series_pass_icon(): void {
		$this->admin_views->template( 'series-pass-icon' );
	}

	/**
	 * Renders the Series Pass type header in the context of the Ticket add and edit form.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function render_type_header(): void {
		$this->admin_views->template( 'series-pass-type-header' );
	}

	/**
	 * Returns the help text for the default ticket type in the ticket form.
	 *
	 * @since TBD
	 *
	 * @param int $event_id  The post ID context of the metabox.
	 * @param int $series_id The post ID of the Series Pass.
	 *
	 * @return string The help text for the default ticket type in the ticket form.
	 */
	public function get_default_ticket_type_header_description( int $event_id, int $series_id ): string {
		$edit_link        = get_edit_post_link( $series_id, 'admin' ) . '#tribetickets';
		$series_edit_link = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			$edit_link,
			get_post_field( 'post_title', $series_id )
		);
		$description      = sprintf(
		// Translators: %1$s is the ticket type label, %2$s is the Event type label, %3$s is the Series Pass type label, %4$s is the Series edit link.
			_x(
				'A single %1$s is specific to this %2$s. You can add a %3$s from the %4$s Series page.',
				'The help text for the default ticket type in the ticket form.',
				'event-tickets'
			),
			tribe_get_ticket_label_singular( 'ticket_type_default_header_description' ),
			tribe_get_event_label_singular_lowercase(),
			tec_tickets_get_series_pass_singular_uppercase( 'ticket_type_default_header_description' ),
			$series_edit_link
		);

		return $description;
	}
}