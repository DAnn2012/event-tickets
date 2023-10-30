<?php
/**
 * Filters the labels used by the Tickets plugin in  the admin and frontend of the site to suite the Series Passes
 * wording.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Commerce\Reports\Data\Order_Summary;
use TEC\Tickets\Flexible_Tickets\Series_Passes;
use TEC\Tickets\Flexible_Tickets\Templates\Admin_Views;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Labels.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Reports {

	/**
	 * A reference to the admin views.
	 *
	 * @since TBD
	 *
	 * @var Admin_Views
	 */
	private Admin_Views $admin_views;

	public function __construct( Admin_Views $admin_views ) {
		$this->admin_views = $admin_views;
	}

	/**
	 * Registers the hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'tribe_template_context:tickets/admin-views/attendees', [
			$this,
			'filter_attendees_report_context'
		] );
		add_action( 'tribe_tickets_attendees_event_details_list_top', [ $this, 'render_series_details_on_attendee_report' ], 50 );
		add_action( 'tribe_tickets_report_event_details_list_top', [ $this, 'render_series_details_on_order_report' ], 50 );
		add_filter( 'tec_tickets_commerce_order_report_summary_label_for_type', [ $this, 'filter_series_type_label' ] );
		add_filter( 'tec_tickets_commerce_order_report_summary_should_include_event_sales_data', [ $this, 'filter_out_series_type_tickets_from_order_report' ], 10, 4 );
	}

	/**
	 * Unregister the hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister_hooks(): void {
		remove_filter( 'tribe_template_context:tickets/admin-views/attendees', [
			$this,
			'filter_attendees_report_context'
		] );
		remove_action( 'tribe_tickets_attendees_event_details_list_top', [ $this, 'render_series_details_on_attendee_report' ], 50 );
		remove_action( 'tribe_tickets_report_event_details_list_top', [ $this, 'render_series_details_on_order_report' ], 50 );
		remove_filter( 'tec_tickets_commerce_order_report_summary_label_for_type', [ $this, 'filter_series_type_label' ] );
		remove_filter( 'tec_tickets_commerce_order_report_summary_should_include_event_sales_data', [ $this, 'filter_out_series_type_tickets_from_order_report' ], 10, 4 );
	}

	/**
	 * Renders the series details on attendee report page for an event attached to a series.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The ID of the post being displayed.
	 *
	 * @return void
	 */
	public function render_series_details_on_attendee_report( int $post_id ): void {
		if ( get_post_type( $post_id ) === Series_Post_Type::POSTTYPE ) {
			return;
		}

		// Check if event is part of a series.
		$series_id = tec_series()->where( 'event_post_id', $post_id )->first_id();

		if ( ! $series_id ) {
			return;
		}

		// Generate series summary.
		$title                = get_the_title( $series_id );
		$edit_url             = get_edit_post_link( $series_id );
		$edit_link            = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $edit_url ), $title );
		$attendee_report_link = tribe( 'tickets.attendees' )->get_report_link( get_post( $series_id ) );
		$action_links         = [
			sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $edit_url ), __( 'Edit Series', 'event-tickets' ) ),
			sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( get_permalink( $series_id ) ), __( 'View Series', 'event-tickets' ) ),
			sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $attendee_report_link ), __( 'Series Attendees', 'event-tickets' ) ),
		];

		// Render series details.
		$this->admin_views->template( 'admin/attendees/series-summary', [
			'title'        => $title,
			'edit_link'    => $edit_link,
			'action_links' => $action_links
		] );
	}

	/**
	 * Renders the series details on order report page for an event attached to a series.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The ID of the post being displayed.
	 *
	 * @return void
	 */
	public function render_series_details_on_order_report( int $post_id ): void {
		if ( get_post_type( $post_id ) === Series_Post_Type::POSTTYPE ) {
			return;
		}

		// Check if event is part of a series.
		$series_id = tec_series()->where( 'event_post_id', $post_id )->first_id();

		if ( ! $series_id ) {
			return;
		}

		$provider = Tickets::get_event_ticket_provider_object( $series_id );

		if ( ! $provider ) {
			return;
		}

		// Generate series summary.
		$title                = get_the_title( $series_id );
		$edit_url             = get_edit_post_link( $series_id );
		$edit_link            = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $edit_url ), $title );
		$order_report_link    = $provider->get_event_reports_link( $series_id, true );
		$action_links         = [
			sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $edit_url ), __( 'Edit Series', 'event-tickets' ) ),
			sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( get_permalink( $series_id ) ), __( 'View Series', 'event-tickets' ) ),
			sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $order_report_link ), __( 'Series Orders', 'event-tickets' ) ),
		];

		// Render series details.
		$this->admin_views->template( 'admin/attendees/series-summary', [
			'title'        => $title,
			'edit_link'    => $edit_link,
			'action_links' => $action_links
		] );
	}

	/**
	 * Filters the label for the Series post type for the report pages.
	 *
	 * @since TBD
	 *
	 * @param string $type The type of ticket.
	 *
	 * @return string The updated label.
	 */
	public function filter_series_type_label( $type ): string {
		if ( $type !== Series_Passes::TICKET_TYPE ) {
			return $type;
		}

		return tec_tickets_get_series_pass_plural_uppercase( 'order summary report' );
	}

	/**
	 * Filters the order report to remove the series passes from the event sales data.
	 *
	 * @since TBD
	 *
	 * @param bool              $include Whether to include the event sales data.
	 * @param Ticket_Object     $ticket  The ticket object.
	 * @param array<string,int> $quantity_by_status The quantity of tickets by status.
	 * @param Order_Summary     $order_summary The order summary object.
	 *
	 * @return bool Whether to include the event sales data.
	 */
	public function filter_out_series_type_tickets_from_order_report( $include, $ticket, $quantity_by_status, $order_summary ): bool {
		// If we are processing order report page for the Series post type, then we want to include all the tickets.
		if ( get_post_type( $order_summary->get_post_id() ) === Series_Post_Type::POSTTYPE ) {
			return true;
		}

		// If we are on regular order pages, then we want to filter out the series passes.
		if ( Series_Passes::TICKET_TYPE === $ticket->type() ) {
			return false;
		}

		return $include;
	}

	/**
	 * Filters the context used to render the Attendees Report to add the data needed to support the additional ticket
	 * types.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $context The context used to render the Attendees Report.
	 *
	 * @return array<string,mixed> The updated context.
	 */
	public function filter_attendees_report_context( array $context = [] ): array {
		if ( ! isset( $context['type_icon_classes'] ) ) {
			$context['type_icon_classes'] = [];
		}
		$context['type_icon_classes'][ Series_Passes::TICKET_TYPE ] = 'tec-tickets__admin-attendees-overview-ticket-type-icon--series-pass';

		if ( ! isset( $context['type_labels'] ) ) {
			$context['type_labels'] = [];
		}
		$context['type_labels'][ Series_Passes::TICKET_TYPE ] = tec_tickets_get_series_pass_plural_uppercase( 'Attendees Report' );

		return $context;
	}
}