<?php

namespace TEC\Tickets\Flexible_Tickets;

use Closure;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Events\Recurrence;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Commerce\Tickets_View;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Events__Main as TEC;
use Tribe__Admin__Notices as Notices;

class BaseTest extends Controller_Test_Case {
	use SnapshotAssertions;
	use Ticket_Maker;
	use Series_Pass_Factory;

	protected string $controller_class = Base::class;

	/**
	 * @before
	 */
	public function ensure_ticketables(): void {
		$ticketable_post_types   = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable_post_types[] = 'post';
		$ticketable_post_types[] = TEC::POSTTYPE;
		$ticketable_post_types[] = Series_Post_Type::POSTTYPE;
		$ticketable_post_types   = array_values( array_unique( $ticketable_post_types ) );
		tribe_update_option( 'ticket-enabled-post-types', $ticketable_post_types );
	}

	/**
	 * It should disable Tickets and RSVPs for Series
	 *
	 * @test
	 */
	public function should_disable_tickets_and_rsvps_for_series(): void {
		$controller = $this->make_controller();

		$filtered = $controller->enable_ticket_forms_for_series( [
			'default' => true,
			'rsvp'    => true,
		] );

		$this->assertEquals( [
			'default'                  => false,
			'rsvp'                     => false,
			Series_Passes::TICKET_TYPE => true,
		], $filtered );
	}

	/**
	 * It should not replace tickets block on post
	 *
	 * @test
	 */
	public function should_not_replace_tickets_block_on_post(): void {
		$post_id  = static::factory()->post->create( [
			'post_type' => 'page',
		] );
		$ticket_1 = $this->create_tc_ticket( $post_id, 23 );
		$ticket_2 = $this->create_tc_ticket( $post_id, 24 );

		$this->make_controller()->register();

		$html = tribe( Tickets_View::class )->get_tickets_block( $post_id );

		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[ $post_id, $ticket_1, $ticket_2 ],
			[ '{{post_id}}', '{{ticket_1}}', '{{ticket_2}}' ],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should not replace tickets block on Series
	 *
	 * @test
	 */
	public function should_not_replace_tickets_block_on_series(): void {
		$series = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$pass_1 = $this->create_tc_series_pass( $series, 23 )->ID;
		$pass_2 = $this->create_tc_series_pass( $series, 89 )->ID;

		$this->make_controller()->register();

		$html = tribe( Tickets_View::class )->get_tickets_block( $series );

		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[ $series, $pass_1, $pass_2 ],
			[ '{{series_id}}', '{{pass_1}}', '{{pass_2}}' ],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should not replace tickets block on Events not in Series
	 *
	 * @test
	 */
	public function should_not_replace_tickets_block_on_events_not_in_series(): void {
		$event    = tribe_events()->set_args( [
			'title'      => 'Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 00:00:00',
			'end_date'   => '2020-01-01 00:00:00',
		] )->create()->ID;
		$ticket_1 = $this->create_tc_ticket( $event, 23 );
		$ticket_2 = $this->create_tc_ticket( $event, 89 );

		$this->make_controller()->register();

		$html = tribe( Tickets_View::class )->get_tickets_block( $event );

		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[ $event, $ticket_1, $ticket_2 ],
			[ '{{event_id}}', '{{ticket_1}}', '{{ticket_2}}' ],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should replace tickets block on Events in Series
	 *
	 * @test
	 */
	public function should_replace_tickets_block_on_events_in_series(): void {
		$series   = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$pass_1 = $this->create_tc_series_pass( $series, 23 )->ID;
		$pass_2 = $this->create_tc_series_pass( $series, 89 )->ID;
		$pass_3 = $this->create_tc_series_pass( $series, 89 )->ID;
		// Sort the tickets "manually".
		wp_update_post( [ 'ID' => $pass_1, 'menu_order' => 2 ] );
		wp_update_post( [ 'ID' => $pass_2, 'menu_order' => 0 ] );
		wp_update_post( [ 'ID' => $pass_3, 'menu_order' => 1 ] );
		$event    = tribe_events()->set_args( [
			'title'      => 'Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 00:00:00',
			'end_date'   => '2020-01-01 00:00:00',
			'series'     => $series,
		] )->create()->ID;
		$ticket_1 = $this->create_tc_ticket( $event, 23);
		$ticket_2 = $this->create_tc_ticket( $event, 89 );
		$ticket_3 = $this->create_tc_ticket( $event, 89 );
		// Sort the tickets "manually".
		wp_update_post( [ 'ID' => $ticket_1, 'menu_order' => 2 ] );
		wp_update_post( [ 'ID' => $ticket_2, 'menu_order' => 0 ] );
		wp_update_post( [ 'ID' => $ticket_3, 'menu_order' => 1 ] );

		$this->make_controller()->register();

		$html = tribe( Tickets_View::class )->get_tickets_block( $event );

		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[
				$event,
				$ticket_1,
				$ticket_2,
				$ticket_3,
				$series,
				$pass_1,
				$pass_2,
				$pass_3
			],
			[
				'{{event_id}}',
				'{{ticket_1}}',
				'{{ticket_2}}',
				'{{ticket_3}}',
				'{{series_id}}',
				'{{pass_1}}',
				'{{pass_2}}',
				'{{pass_3}}'
			],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should disable tickets and RSVPs for recurring event
	 *
	 * @test
	 */
	public function should_disable_tickets_and_rsvps_for_recurring_event(): void {
		$recurrence      = ( new Recurrence() )
			->with_start_date( '2020-01-01 00:00:00' )
			->with_end_date( '2020-01-01 10:00:00' )
			->with_weekly_recurrence()
			->with_end_after( 3 )
			->to_event_recurrence();
		$recurring_event = tribe_events()->set_args( [
			'title'      => 'Single Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 00:00:00',
			'end_date'   => '2020-01-01 10:00:00',
			'recurrence' => $recurrence
		] )->create();

		$controller = $this->make_controller();

		$filtered = $controller->disable_tickets_on_recurring_events( [
			'default' => true,
			'rsvp'    => true,
		], $recurring_event->ID );

		$this->assertEqualSets( [
			'default' => false,
			'rsvp'    => false,
		], $filtered );
	}

	/**
	 * It should not disable tickets and RSVPs for single event
	 *
	 * @test
	 */
	public function should_not_disable_tickets_and_rsvps_for_single_event(): void {
		$single_event = tribe_events()->set_args( [
			'title'      => 'Single Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 00:00:00',
			'end_date'   => '2020-01-01 10:00:00',
		] )->create();

		$controller = $this->make_controller();

		$filtered = $controller->disable_tickets_on_recurring_events( [
			'default' => true,
			'rsvp'    => true,
		], $single_event->ID );

		$this->assertEqualSets( [
			'default' => true,
			'rsvp'    => true,
		], $filtered );
	}

	public function recurring_events_and_tickets_admin_notices_provider(): Generator {
		yield 'single event' => [
			function () {
				$event = tribe_events()->set_args( [
					'title'      => 'Single Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'end_date'   => '2020-01-01 10:00:00',
				] )->create()->ID;

				return [ $event, null, false ];
			}
		];

		yield 'single event with tickets' => [
			function () {
				$event     = tribe_events()->set_args( [
					'title'      => 'Single Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'end_date'   => '2020-01-01 10:00:00',
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event );

				return [ $event, $ticket_id, false ];
			}
		];

		yield 'recurring event' => [
			function () {
				$event = tribe_events()->set_args( [
					'title'      => 'Recurring Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'end_date'   => '2020-01-01 10:00:00',
					'recurrence' => 'RRULE:FREQ=WEEKLY;COUNT=3',
				] )->create()->ID;

				return [ $event, null, false ];
			}
		];

		yield 'recurring event with tickets' => [
			function () {
				$event     = tribe_events()->set_args( [
					'title'      => 'Recurring Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'end_date'   => '2020-01-01 10:00:00',
					'recurrence' => 'RRULE:FREQ=WEEKLY;COUNT=3',
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event );

				return [ $event, $ticket_id, true ];
			}
		];

		yield 'recurring event occurrence' => [
			function () {
				$event = tribe_events()->set_args( [
					'title'      => 'Recurring Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'end_date'   => '2020-01-01 10:00:00',
					'recurrence' => 'RRULE:FREQ=WEEKLY;COUNT=3',
				] )->create()->ID;

				// Second occurrence.
				$occurrence = Occurrence::where( 'post_id', $event )->offset( 1 )->first();

				return [ $occurrence->provisional_id, null, false ];
			}
		];

		yield 'recurring event with tickets occurrence' => [
			function () {
				$event     = tribe_events()->set_args( [
					'title'      => 'Recurring Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'end_date'   => '2020-01-01 10:00:00',
					'recurrence' => 'RRULE:FREQ=WEEKLY;COUNT=3',
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event );

				// Second occurrence.
				$occurrence = Occurrence::where( 'post_id', $event )->offset( 1 )->first();

				return [ $occurrence->provisional_id, $ticket_id, true ];
			}
		];
	}

	/**
	 * It should control the notice about recurring events and tickets correctly
	 *
	 * @test
	 * @dataProvider recurring_events_and_tickets_admin_notices_provider
	 */
	public function should_control_the_notice_about_recurring_events_and_tickets_correctly( Closure $fixture ): void {
		[ $event_id, $ticket_id, $expect_notice_when_unregistered ] = array_replace( [ null, null, null ], $fixture() );

		$notices     = Notices::instance();
		$notice_slug = 'tribe_notice_classic_editor_ecp_recurring_tickets-' . $event_id;
		// Simulate a request to edit the event.
		$_GET['post'] = $event_id;
		// Remove other hooked functions to avoid side effects.
		$GLOBALS['wp_filter']['admin_init'] = new \WP_Hook();
		// Hook the admin notices.
		tribe( 'tickets.admin.notices' )->hook();
		// Finally dispatch the `admin_init` action.
		do_action( 'admin_init' );

		$notice = $notices->get( $notice_slug );

		if ( $expect_notice_when_unregistered ) {
			$this->assertNotNull( $notice );
		} else {
			$this->assertNull( $notice );
		}

		// Build and register the controller.
		$controller = $this->make_controller()->register();

		// Simulate a request to edit the event.
		$_GET['post'] = $event_id;
		// Remove the previous notice.
		$notice = $notices->remove( $notice_slug );
		$this->assertNull( $notices->get( $notice_slug ) );
		// Dispatch the `admin_init` action again.
		do_action( 'admin_init' );

		$notice = $notices->get( $notice_slug );
		$this->assertNull( $notice, 'When the controller is registered no notice should ever show.' );
	}

	/**
	 * It should mark series as ticketables when registering
	 *
	 * @test
	 */
	public function should_mark_series_as_ticketables_when_registering(): void {
		// Start a request with Series not ticketable.
		$option_name           = 'ticket-enabled-post-types';
		$cpt                   = Series_Post_Type::POSTTYPE;
		$ticketable_post_types = (array) tribe_get_option( $option_name, [] );
		if ( ( $index = array_search( $cpt, $ticketable_post_types, true ) ) !== false ) {
			unset( $ticketable_post_types[ $index ] );
		}
		tribe_update_option( $option_name, $ticketable_post_types );

		// Sanity check.
		$index = array_search( $cpt, (array) tribe_get_option( $option_name, [] ), true );
		$this->assertFalse( $index );

		// Build and register the controller.
		$controller = $this->make_controller();
		$controller->register();

		// Check that Series is ticketable.
		$index = array_search( $cpt, (array) tribe_get_option( $option_name, [] ), true );
		$this->assertNotFalse( $index );

		$controller->unregister();

		// Check that Series is not ticketable.
		$index = array_search( $cpt, (array) tribe_get_option( $option_name, [] ), true );
		$this->assertFalse( $index );
	}
}
