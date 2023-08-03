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

	public function render_link_to_series( int $ticket_post_id ): void {
		$this->admin_views->template( 'series-pass-edit-link', [
			'series_edit_link' => get_edit_post_link( $ticket_post_id ),
		] );
	}

	public function print_series_pass_icon(): void {
		$this->admin_views->template( 'series-pass-icon' );
	}
}