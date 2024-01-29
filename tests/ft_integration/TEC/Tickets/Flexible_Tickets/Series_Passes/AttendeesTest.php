<?php

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Tickets__Attendees_Table;
use WP_Post;
use WP_REST_Request;
use WP_REST_Server;

class AttendeesTest extends Controller_Test_Case {
	use Series_Pass_Factory;
	use Attendee_Maker;
	use Order_Maker;

	protected string $controller_class = Attendees::class;
	private $rest_server_backup;

	/**
	 * @before
	 */
	public function backup_rest_server(): void {
		/* @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->rest_server_backup = $wp_rest_server instanceof WP_REST_Server ?
			clone $wp_rest_server
			: $wp_rest_server;
	}

	/**
	 * @after
	 */
	public function restore_rest_server(): void {
		global $wp_rest_server;
		$wp_rest_server = $this->rest_server_backup;
	}

	/**
	 * It should filter Attendees Table columns correctly
	 *
	 * @test
	 */
	public function should_filter_attendees_table_columns_correctly(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		// Create an Event part of the Series.
		$series_event_id = tribe_events()->set_args( [
			'title'      => 'Series Event',
			'status'     => 'publish',
			'start_date' => '2021-01-01 10:00:00',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series,
		] )->create()->ID;
		// Create an Event NOT part of the Series.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Event',
			'status'     => 'publish',
			'start_date' => '2021-01-01 10:00:00',
			'duration'   => 3 * HOUR_IN_SECONDS,
		] )->create()->ID;

		$this->make_controller()->register();

		// Simulate a request to look at the Event not in the Series.
		$_GET['event_id'] = $event_id;
		$attendee_table = new Tribe__Tickets__Attendees_Table();
		$this->assertArrayHasKey( 'check_in', $attendee_table->get_table_columns() );

		// Simulate a request to look at the Event in the Series.
		$_GET['event_id'] = $series_event_id;
		$attendee_table = new Tribe__Tickets__Attendees_Table();
		$this->assertArrayHasKey( 'check_in', $attendee_table->get_table_columns() );

		// Simulate a request to look at the Event in the Series.
		$_GET['event_id'] = $series;
		$attendee_table = new Tribe__Tickets__Attendees_Table();
		$this->assertArrayNotHasKey( 'check_in', $attendee_table->get_table_columns() );
	}

	/**
	 * It should fail to check in pass Attendee from context of Event not part of Series
	 *
	 * @test
	 */
	public function should_fail_to_check_in_pass_attendee_from_context_of_event_not_part_of_series(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$series_attendee_id = $this->create_attendee_for_ticket( $series_pass_id, $series_id );
		// Create an Event not part of the Series.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Series Event',
			'status'     => 'publish',
			'start_date' => '+2 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
		] )->create()->ID;
		$event_provisional_id = Occurrence::find( $event_id, 'post_id' )->provisional_id;
		$commerce = Module::get_instance();

		$controller = $this->make_controller();
		$controller->register();

		$this->assertFalse( $commerce->checkin( $series_attendee_id, false, $event_id ) );
		$this->assertFalse( $commerce->checkin( $series_attendee_id, false, $event_provisional_id ) );
	}

	/**
	 * It should fail to check in pass Attendee from context of not an Event
	 *
	 * @test
	 */
	public function should_fail_to_check_in_pass_attendee_from_context_of_not_an_event(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$series_attendee_id = $this->create_attendee_for_ticket( $series_pass_id, $series_id );
		// Create a post
		$post_id = static::factory()->post->create();
		$commerce = Module::get_instance();

		$controller = $this->make_controller();
		$controller->register();

		$this->assertFalse( $commerce->checkin( $series_attendee_id, false, $post_id ) );
	}

	/**
	 * It should handle Series Pass Attendee checkin correctly
	 *
	 * @test
	 */
	public function should_handle_series_pass_attendee_checkin_correctly(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$series_attendee_id = $this->create_attendee_for_ticket( $series_pass_id, $series_id );
		// Create a past Event part of the Series.
		$past_series_event_id = tribe_events()->set_args( [
			'title'      => 'Series Event',
			'status'     => 'publish',
			'start_date' => '-1 week',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		// Create a Single Ticket and an Attendee for the past Event part of the Series.
		$past_series_event_ticket_id = $this->create_tc_ticket( $past_series_event_id );
		$past_series_event_attendee_id = $this->create_attendee_for_ticket( $past_series_event_ticket_id, $past_series_event_id );
		$past_series_event_provisional_id = Occurrence::find( $past_series_event_id, 'post_id' )->provisional_id;
		// Create a current Event part of the Series.
		$current_series_event_id = tribe_events()->set_args( [
			'title'      => 'Series Event',
			'status'     => 'publish',
			'start_date' => '-1 hour',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		// Create a Single Ticket and an Attendee for the current Event part of the Series.
		$current_series_event_ticket_id = $this->create_tc_ticket( $current_series_event_id );
		$current_series_event_attendee_id = $this->create_attendee_for_ticket( $current_series_event_ticket_id, $current_series_event_id );
		$current_series_event_provisional_id = Occurrence::find( $current_series_event_id, 'post_id' )->provisional_id;
		// Create a near feature Event part of the Series.
		$near_future_series_event_id = tribe_events()->set_args( [
			'title'      => 'Series Event',
			'status'     => 'publish',
			'start_date' => '+3 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		// Create a Single Ticket and an Attendee for the near feature Event part of the Series.
		$near_feature_series_event_ticket_id = $this->create_tc_ticket( $near_future_series_event_id );
		$near_future_series_event_attendee_id = $this->create_attendee_for_ticket( $near_feature_series_event_ticket_id, $near_future_series_event_id );
		$near_future_series_provisional_id = Occurrence::find( $near_future_series_event_id, 'post_id' )->provisional_id;
		// Create a far feature Event part of the Series.
		$far_future_series_event_id = tribe_events()->set_args( [
			'title'      => 'Series Event',
			'status'     => 'publish',
			'start_date' => '+3 days',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		// Create a Single Ticket and an Attendee for the far feature Event part of the Series.
		$far_future_seriees_event_attendee_id = $this->create_tc_ticket( $far_future_series_event_id );
		$far_future_series_event_attendee_id = $this->create_attendee_for_ticket( $far_future_seriees_event_attendee_id, $far_future_series_event_id );
		$far_future_series_provisional_id = Occurrence::find( $far_future_series_event_id, 'post_id' )->provisional_id;
		// Create a Recurring Event part of the Series.
		$near_future_recurring_event_id = tribe_events()->set_args( [
			'title'      => 'Series Event',
			'status'     => 'publish',
			'start_date' => '+4 hours',
			'duration'   => HOUR_IN_SECONDS,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
			'series'     => $series_id
		] )->create()->ID;
		// Fetch the provisional IDs for the 3 Occurrences part of the Recurring Event.
		$near_future_recurring_event_provisional_ids = Occurrence::where( 'post_id', $near_future_recurring_event_id )
			->order_by( 'start_date', 'ASC' )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		$this->assertCount( 3, $near_future_recurring_event_provisional_ids );
		$this->assertEquals(
			$near_future_recurring_event_id,
			Occurrence::normalize_id( $near_future_recurring_event_provisional_ids[0] ),
			'The first Occurrence provisional ID should map to the Recurring Event post ID.'
		);
		// Create a second Recurring Event part of the Series. This one will have only one Occurrence in the time window.
		$one_occurrence_recurring_event_id = tribe_events()->set_args( [
			'title'      => 'Series Event',
			'status'     => 'publish',
			'start_date' => '+4 hours',
			'duration'   => HOUR_IN_SECONDS,
			'recurrence' => 'RRULE:FREQ=WEEKLY;COUNT=3',
			'series'     => $series_id
		] )->create()->ID;
		$one_occurrence_recurring_event_provisional_ids = Occurrence::where( 'post_id', $one_occurrence_recurring_event_id )
			->order_by( 'start_date', 'ASC' )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		// Create an Event NOT part of the Series.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Event',
			'status'     => 'publish',
			'start_date' => '2021-01-01 10:00:00',
			'duration'   => 3 * HOUR_IN_SECONDS,
		] )->create()->ID;
		// Create a Single Ticket and an Attendee for the Event.
		$event_ticket_id = $this->create_tc_ticket( $event_id );
		$event_attendee_id = $this->create_attendee_for_ticket( $event_ticket_id, $event_id );
		$commerce = Module::get_instance();
		$checkin_key = $commerce->checkin_key;
		$attendee_to_event_meta_key = Module::ATTENDEE_EVENT_KEY;
		$attendee_to_ticket_meta_key = Module::ATTENDEE_PRODUCT_KEY;
		// Set the checkin candidates time window to 36 hours.
		$time_buffer = 36 * HOUR_IN_SECONDS;
		add_filter( 'tec_tickets_flexible_tickets_series_checkin_time_buffer', static fn() => $time_buffer );

		// Verify that, to start with, all Attendees are not checked in.
		$this->assertEmpty( get_post_meta( $series_attendee_id, $checkin_key, true ) );
		$this->assertEmpty( get_post_meta( $past_series_event_attendee_id, $checkin_key, true ) );
		$this->assertEmpty( get_post_meta( $current_series_event_attendee_id, $checkin_key, true ) );
		$this->assertEmpty( get_post_meta( $near_future_series_event_attendee_id, $checkin_key, true ) );
		$this->assertEmpty( get_post_meta( $far_future_series_event_attendee_id, $checkin_key, true ) );
		$this->assertEmpty( get_post_meta( $event_attendee_id, $checkin_key, true ) );

		$controller = $this->make_controller();
		$controller->register();

		// Checkin of Attendees for default Tickets.
		$this->assertTrue( $commerce->checkin( $event_attendee_id ),
			'Checkin of an Attendee for a default Event Ticket should happen without issues.'
		);
		$this->assertEquals( 1, get_post_meta( $event_attendee_id, $checkin_key, true ) );
		$this->assertTrue( $commerce->checkin( $past_series_event_attendee_id ),
			'Checkin of an Attendee for a default Event Ticket should happen without issues.' );
		$this->assertEquals( 1, get_post_meta( $past_series_event_attendee_id, $checkin_key, true ) );

		$this->assertTrue( $commerce->checkin( $current_series_event_attendee_id ),
			'Checkin of an Attendee for a default Event Ticket should happen without issues.' );
		$this->assertEquals( 1, get_post_meta( $current_series_event_attendee_id, $checkin_key, true ) );

		$this->assertTrue( $commerce->checkin( $near_future_series_event_attendee_id ),
			'Checkin of an Attendee for a default Event Ticket should happen without issues.' );
		$this->assertEquals( 1, get_post_meta( $near_future_series_event_attendee_id, $checkin_key, true ) );

		$this->assertTrue( $commerce->checkin( $far_future_series_event_attendee_id ),
			'Checkin of an Attendee for a default Event Ticket should happen without issues.' );
		$this->assertEquals( 1, get_post_meta( $far_future_series_event_attendee_id, $checkin_key, true ) );

		// Subscribe to the Attendee clone action to capture the cloned Attendee ID.
		add_action( 'tec_tickets_flexible_tickets_series_pass_attendee_cloned', function ( $clone_id ) use ( &$cloned_attendee_id ) {
			$cloned_attendee_id = $clone_id;
		} );

		// Checking in a Series Pass Attendee without providing an Event ID should fail.
		$cloned_attendee_id = null;
		$this->assertFalse(
			$commerce->checkin( $series_attendee_id, false ),
			'The check in of the Series Pass Attendee without specifying an Event ID should fail.'
		);
		$this->assertNull(
			$cloned_attendee_id,
			'The Series Pass Attendee should not have been cloned.'
		);
		$this->assertEquals( '', get_post_meta( $series_attendee_id, $checkin_key, true ),
			'The original Series Pass Attendee should not be checked in.'
		);

		// Let's make sure the checkin candidates are correct.
		$this->assertEqualSets( [
			$current_series_event_provisional_id,
			$near_future_series_provisional_id,
			$near_future_recurring_event_provisional_ids[0],
			$near_future_recurring_event_provisional_ids[1],
			$one_occurrence_recurring_event_provisional_ids[0],
		], $controller->fetch_checkin_candidates_for_series( $series_attendee_id, $series_id, true ),
			'The checkin candidates for the Series Pass Attendee should be the current Event, the near future Event and the Recurring Event.'
		);

		// Checking in the Series Pass Attendee from any of the Events part of the Series should succeed and clone the Attendee.
		// The cloned Attendee should be related to the provisional ID, not the Event post ID.
		// The assertions are using a mix of real Event post IDs and Occurrence provisional IDs to make sure both work.
		foreach (
			[
				'past_series_event_id'                => [
					$past_series_event_id,
					$past_series_event_provisional_id
				],
				'current_series_event_provisional_id' => [
					$current_series_event_provisional_id,
					$current_series_event_provisional_id
				],
				'near_future_series_event_id'         => [
					$near_future_series_event_id,
					$near_future_series_provisional_id
				],
				'far_future_series_provisional_id'    => [
					$far_future_series_provisional_id,
					$far_future_series_provisional_id
				],
			] as $set => [$event_id, $attendee_target_id]
		) {
			$cloned_attendee_id = null;
			$this->assertTrue(
				$commerce->checkin( $series_attendee_id, false, $event_id ),
				"The check in of the Series Pass Attendee from an Event part of the Series should be successful. | {$set}"
			);
			$this->assertNotNull(
				$cloned_attendee_id,
				"On checkin, the Series Pass Attendee should have been cloned. | {$set}"
			);
			$this->assertEquals( '', get_post_meta( $series_attendee_id, $checkin_key, true ),
				"The original Series Pass Attendee should not be checked in. | {$set}"
			);
			$this->assertEquals(
				$attendee_target_id,
				get_post_meta( $cloned_attendee_id, $attendee_to_event_meta_key, true ),
				"The cloned Attendee should be related to the Event provisional ID | {$set}"
			);
			$this->assertEquals(
				$series_pass_id,
				get_post_meta( $cloned_attendee_id, $attendee_to_ticket_meta_key, true ),
				"The cloned Attendee should be related to the Series Pass | {$set}"
			);
			$this->assertEquals( '1', get_post_meta( $cloned_attendee_id, $checkin_key, true ),
				"The cloned Attendee should be checked in. | {$set}"
			);
		}

		$this->assertFalse(
			$commerce->checkin( $series_attendee_id, false, $near_future_recurring_event_id ),
			"The check in of the Series Pass Attendee from a Recurring Event ID should fail if there are multiple Occurrences in the time window."
		);

		// Checking in Series Pass Attendees from Recurring Event part of the Series by means of real of Provisional ID
		// should succeed. Use the actual Recurring Event ID to check in the Attendee for the first Occurrence.
		$this->assertEqualSets( [
			$near_future_recurring_event_provisional_ids[0],
			$near_future_recurring_event_provisional_ids[1],
		], $controller->fetch_checkin_candidates_for_event( $series_attendee_id, $near_future_recurring_event_id, true ),
			'The checking candidates for the near future Recurring Event should be the first and second Occurrence.'
		);
		foreach (
			[
				'near_future_recurring_event_id, 1st occ.' => $near_future_recurring_event_provisional_ids[0],
				'near_future_recurring_event_id, 2nd occ.' => $near_future_recurring_event_provisional_ids[1],
				'near_future_recurring_event_id, 3rd occ.' => $near_future_recurring_event_provisional_ids[2],
			] as $set => $event_id
		) {
			$attendee_target_id = $event_id;
			$cloned_attendee_id = null;
			$this->assertTrue(
				$commerce->checkin( $series_attendee_id, false, $event_id ),
				"The check in of the Series Pass Attendee from an Event part of the Series should be successful. | {$set}"
			);
			$this->assertNotNull(
				$cloned_attendee_id,
				"On checkin, the Series Pass Attendee should have been cloned. | {$set}"
			);
			$this->assertEquals( '', get_post_meta( $series_attendee_id, $checkin_key, true ),
				"The original Series Pass Attendee should not be checked in. | {$set}"
			);
			$this->assertEquals(
				$attendee_target_id,
				get_post_meta( $cloned_attendee_id, $attendee_to_event_meta_key, true ),
				"The cloned Attendee should be related to the Event provisional ID | {$set}"
			);
			$this->assertEquals(
				$series_pass_id,
				get_post_meta( $cloned_attendee_id, $attendee_to_ticket_meta_key, true ),
				"The cloned Attendee should be related to the Series Pass | {$set}"
			);
			$this->assertEquals( '1', get_post_meta( $cloned_attendee_id, $checkin_key, true ),
				"The cloned Attendee should be checked in. | {$set}"
			);
		}

		// Checking in Series Pass Attendees from the Event ID of the Recurring Event part of the Series that only
		// has one Occurrence in the time window should succeed.
		$set = 'one_occurrence_recurring_event_id';
		// The Attendee should be checked into the first Occurrence.
		$attendee_target_id = $one_occurrence_recurring_event_provisional_ids[0];
		$cloned_attendee_id = null;
		$this->assertTrue(
			$commerce->checkin( $series_attendee_id, true, $one_occurrence_recurring_event_id ),
			"The check in of the Series Pass Attendee from an Event part of the Series should be successful. | {$set}"
		);
		$this->assertNotNull(
			$cloned_attendee_id,
			"On checkin, the Series Pass Attendee should have been cloned. | {$set}"
		);
		$this->assertEquals( '', get_post_meta( $series_attendee_id, $checkin_key, true ),
			"The original Series Pass Attendee should not be checked in. | {$set}"
		);
		$this->assertEquals(
			$attendee_target_id,
			get_post_meta( $cloned_attendee_id, $attendee_to_event_meta_key, true ),
			"The cloned Attendee should be related to the Event provisional ID | {$set}"
		);
		$this->assertEquals(
			$series_pass_id,
			get_post_meta( $cloned_attendee_id, $attendee_to_ticket_meta_key, true ),
			"The cloned Attendee should be related to the Series Pass | {$set}"
		);
		$this->assertEquals( '1', get_post_meta( $cloned_attendee_id, $checkin_key, true ),
			"The cloned Attendee should be checked in. | {$set}"
		);

		// Checking in Series Pass Attendees a second time should be handled by default logic and not clone again.
		// Use the provisional ID for one to make sure it works.
		foreach (
			[
				Occurrence::find( $past_series_event_id, 'post_id' )->provisional_id,
				$current_series_event_id,
				$near_future_series_event_id,
				$far_future_series_event_id,
			] as $event_id
		) {
			$cloned_attendee_id = null;
			$this->assertTrue(
				$commerce->checkin( $series_attendee_id, false, $event_id ),
				'A 2nd checkin of the Series Pass Attendee from an Event part of the Series should be successful.'
			);
			$this->assertNull(
				$cloned_attendee_id,
				'On a 2nd checkin, the Series Pass Attendee should not have been cloned again.'
			);
			$this->assertEquals( '', get_post_meta( $series_attendee_id, $checkin_key, true ),
				'On a 2nd checkin, the original Series Pass Attendee should not be checked in.'
			);
		}
	}

	/**
	 * It should correctly handle REST check-in requests when check-in is not restricted
	 *
	 * @test
	 */
	public function should_correctly_handle_rest_request_to_checkin_with_qr(): void {
		// Ensure check-in is not restricted.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now', false );
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_attendee_id = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create three current and upcoming events the Series Pass Attendee might check into.
		$event_1 = tribe_events()->set_args( [
			'title'      => 'Event 1',
			'status'     => 'publish',
			'start_date' => '+1 hour',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		$event_2 = tribe_events()->set_args( [
			'title'      => 'Event 2',
			'status'     => 'publish',
			'start_date' => '+2 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		$event_3 = tribe_events()->set_args( [
			'title'      => 'Event 3',
			'status'     => 'publish',
			'start_date' => '+3 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		// Create a Recurring Event part of the series.
		$recurring_event_id = tribe_events()->set_args( [
			'title'      => 'Recurring Event',
			'status'     => 'publish',
			'start_date' => '+4 hours',
			'duration'   => HOUR_IN_SECONDS,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
			'series'     => $series_id
		] )->create()->ID;
		$recurring_event_provisional_ids = Occurrence::where( 'post_id', '=', $recurring_event_id )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		// Ensure Tickets REST routes will register.
		if ( ! did_action( 'rest_api_init' ) ) {
			do_action( 'rest_api_init' );
		}
		$commerce = Module::get_instance();
		$api_key = 'secrett-api-key';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		$controller = $this->make_controller();
		$controller->register();

		// Become an app user trying to scan Attendees in.
		wp_set_current_user( 0 );

		// Set the time buffer to 6 hours.
		$time_buffer = 6 * HOUR_IN_SECONDS;
		add_filter( 'tec_tickets_flexible_tickets_series_checkin_time_buffer', static function () use ( &$time_buffer ) {
			return $time_buffer;
		} );

		// Try and scan in a Series pass Attendee from the context of a post.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
		$request->set_param( 'event_id', static::factory()->post->create() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403,
			$response->status,
			'Trying to check in a Series Pass Attendee from the context of a post that is not an Event should fail.'
		);

		// Trying to scan in a Series pass Attendee without providing an Event ID should fail if there are multiple candidates.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 300,
			$response->status,
			'Trying to check in a Series Pass Attendee with multiple candidate Events should fail and require a choice.'
		);
		$this->assertArrayHasKey( 'attendee_id', $response->data );
		$this->assertEquals( $series_attendee_id, $response->data['attendee_id'] );
		$this->assertArrayHasKey( 'candidates', $response->data );
		$this->assertEqualSets( [
			Occurrence::find( $event_1, 'post_id' )->provisional_id,
			Occurrence::find( $event_2, 'post_id' )->provisional_id,
			Occurrence::find( $event_3, 'post_id' )->provisional_id,
			$recurring_event_provisional_ids[0],
		], array_map(
			static fn( array $candidate ) => $candidate['id'],
			$response->data['candidates']
		) );

		// Set the time buffer to 1.5 hours.
		$time_buffer = 1.5 * HOUR_IN_SECONDS;

		// Trying to scan in a Series pass Attendee without providing an Event ID should succeed if there is only one
		// candidate in the checkin timeframe of 1.5 hours.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 201,
			$response->status,
			'Trying to check in a Series Pass Attendee with only one candidate Event should succeed.'
		);

		// Trying to scan in a Series Pass Attendee providing the ID of an Event outside of the current timeframe
		// of 1.5 hours that only has one candidate should succeed.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
		$request->set_param( 'event_id', $event_3 );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403,
			$response->status,
			'Trying to check in a Series Pass Attendee with an Event ID outside of the current timeframe should fail.'
		);

		// Trying to scan in a Series Pass Attendee providing the ID of a Recurring Event with no Occurrences in the
		// current timeframe of 1.5 hours should fail.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
		$request->set_param( 'event_id', $recurring_event_id );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->status );

		// Set the time buffer to 36 hours.
		$time_buffer = 36 * HOUR_IN_SECONDS;

		// Trying to scan in a Series Pass Attendee providing the ID of a Recurring Event with multiple Occurrences
		// in the current timeframe of 36 hours should fail and require to pick one.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
		$request->set_param( 'event_id', $recurring_event_id );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 300, $response->status );

		// Trying to scan in a Series Pass Attendee providing the Provisional ID of an Occurrence outside time frame
		// of 36 hours should fail.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
		$request->set_param( 'event_id', $recurring_event_provisional_ids[2] );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->status );
	}

	/**
	 * It should correctly handle REST check-in requests when check-in is restricted
	 *
	 * @test
	 */
	public function should_correctly_handle_rest_check_in_requests_when_check_in_is_restricted(): void {
		// Ensure check-in is restricted.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now', true );
		// And that the check-in time window is 6 hours.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now-time-buffer', 6 * HOUR_IN_SECONDS );

		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_attendee_id = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create three current and upcoming events the Series Pass Attendee might check into.
		$event_1 = tribe_events()->set_args( [
			'title'      => 'Event 1',
			'status'     => 'publish',
			'start_date' => '+1 hour',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		$event_2 = tribe_events()->set_args( [
			'title'      => 'Event 2',
			'status'     => 'publish',
			'start_date' => '+12 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		$event_3 = tribe_events()->set_args( [
			'title'      => 'Event 3',
			'status'     => 'publish',
			'start_date' => '+36 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		// Create a Recurring Event part of the series.
		$recurring_event_id = tribe_events()->set_args( [
			'title'      => 'Recurring Event',
			'status'     => 'publish',
			'start_date' => '+4 hours',
			'duration'   => HOUR_IN_SECONDS,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
			'series'     => $series_id
		] )->create()->ID;
		$recurring_event_provisional_ids = Occurrence::where( 'post_id', '=', $recurring_event_id )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		// Ensure Tickets REST routes will register.
		if ( ! did_action( 'rest_api_init' ) ) {
			do_action( 'rest_api_init' );
		}
		$commerce = Module::get_instance();
		$api_key = 'secrett-api-key';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		$controller = $this->make_controller();
		$controller->register();

		// Become an app user trying to scan Attendees in.
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals(
			300,
			$response->status,
			'A request to check-in a Series Pass Attendee for a series with multiple candidates should fail and require a choice.'
		);

		foreach (
			[
				'event_1'                  => [ $event_1, 201 ],
				'event_2'                  => [ $event_2, 403 ],
				'event_3'                  => [ $event_3, 403 ],
				'recurring_event 1st occ.' => [ $recurring_event_provisional_ids[0], 201 ],
				'recurring_event 2nd occ.' => [ $recurring_event_provisional_ids[1], 403 ],
				'recurring_event 3rd occ.' => [ $recurring_event_provisional_ids[2], 403 ],
			] as $set => [$event_id, $expected_status]
		) {
			$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
			$request->set_param( 'api_key', $api_key );
			$request->set_param( 'ticket_id', (string) $series_attendee_id );
			$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
			$request->set_param( 'event_id', $event_id );

			$response = rest_get_server()->dispatch( $request );

			$this->assertEquals( $expected_status, $response->status, "Set: {$set}" );
		}
	}

	/**
	 * It should allow manual check-in of Series Pass Attendees from Events always
	 *
	 * @test
	 */
	public function should_allow_manual_check_in_of_series_pass_attendees_from_events_always(): void {
		// Ensure check-in is restricted.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now', true );
		// And that the check-in time window is 0 seconds. This prevents any QR check-in from succeeding.
		// But it should never prevent manual check-ins from succeeding.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now-time-buffer', 0 );

		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_attendee_id = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create three current and upcoming events the Series Pass Attendee might check into.
		$event_1 = tribe_events()->set_args( [
			'title'      => 'Event 1',
			'status'     => 'publish',
			'start_date' => '+1 hour',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		$event_2 = tribe_events()->set_args( [
			'title'      => 'Event 2',
			'status'     => 'publish',
			'start_date' => '+2 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		$event_3 = tribe_events()->set_args( [
			'title'      => 'Event 3',
			'status'     => 'publish',
			'start_date' => '+3 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		// Create a Recurring Event part of the series.
		$recurring_event_id = tribe_events()->set_args( [
			'title'      => 'Recurring Event',
			'status'     => 'publish',
			'start_date' => '+4 hours',
			'duration'   => HOUR_IN_SECONDS,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
			'series'     => $series_id
		] )->create()->ID;
		$recurring_event_provisional_ids = Occurrence::where( 'post_id', '=', $recurring_event_id )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		$commerce = Module::get_instance();

		$controller = $this->make_controller();
		$controller->register();

		foreach (
			[
				'event_1'                  => $event_1,
				'event_2'                  => $event_2,
				'event_3'                  => $event_3,
				'recurring_event 1st occ.' => $recurring_event_provisional_ids[0],
				'recurring_event 2nd occ.' => $recurring_event_provisional_ids[1],
				'recurring_event 3rd occ.' => $recurring_event_provisional_ids[2],
			] as $set => $event_id
		) {
			$this->assertTrue( $commerce->checkin( $series_attendee_id, false, $event_id ),
				"Manual checkin of a Series Pass Attendee should succeed even when the restricted check-in time is 0. | {$set}" );
		}
	}

	/**
	 * It should not allow any QR check-in when the restricted check-in time is 0
	 *
	 * @test
	 */
	public function should_not_allow_any_qr_check_in_when_the_restricted_check_in_time_is_0(): void {
		// Ensure check-in is restricted.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now', true );
		// And that the check-in time window is 6 hours.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now-time-buffer', 6 * HOUR_IN_SECONDS );

		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_attendee_id = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create three current and upcoming events the Series Pass Attendee might check into.
		$event_1 = tribe_events()->set_args( [
			'title'      => 'Event 1',
			'status'     => 'publish',
			'start_date' => '+1 hour',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		$event_2 = tribe_events()->set_args( [
			'title'      => 'Event 2',
			'status'     => 'publish',
			'start_date' => '+2 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		$event_3 = tribe_events()->set_args( [
			'title'      => 'Event 3',
			'status'     => 'publish',
			'start_date' => '+3 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		// Create a Recurring Event part of the series.
		$recurring_event_id = tribe_events()->set_args( [
			'title'      => 'Recurring Event',
			'status'     => 'publish',
			'start_date' => '+4 hours',
			'duration'   => HOUR_IN_SECONDS,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
			'series'     => $series_id
		] )->create()->ID;
		$recurring_event_provisional_ids = Occurrence::where( 'post_id', '=', $recurring_event_id )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		$commerce = Module::get_instance();

		$controller = $this->make_controller();
		$controller->register();

		$this->assertFalse( $commerce->checkin( $series_attendee_id ),
			'Checkin of a Series Pass Attendee should fail when the restricted check-in time is 0.' );
		foreach (
			[
				'event_1'                  => $event_1,
				'event_2'                  => $event_2,
				'event_3'                  => $event_3,
				'recurring_event 1st occ.' => $recurring_event_provisional_ids[0],
				'recurring_event 2nd occ.' => $recurring_event_provisional_ids[1],
				'recurring_event 3rd occ.' => $recurring_event_provisional_ids[2],
			] as $set => $event_id
		) {
			$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
			$request->set_param( 'ticket_id', (string) $series_attendee_id );
			$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
			$request->set_param( 'event_id', $event_id );

			$response = rest_get_server()->dispatch( $request );

			$this->assertEquals( 400, $response->status,
				"Checkin of a Series Pass Attendee should fail when the restricted check-in time is 0. | {$set}" );
		}
	}


	// @todo test for clone <> original updates

	/**
	 * It should update cloned Attendee when original updated
	 *
	 * @test
	 */
	public function should_update_cloned_attendee_when_original_updated(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$original = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create an Event part of the Series.
		$event_1 = tribe_events()->set_args( [
			'title'      => 'Test Event #1',
			'status'     => 'publish',
			'start_date' => '+3 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		$controller = $this->make_controller();
		// Clone the Attendee to the Event, it will also check the cloned Attendee in.
		$clone_1 = $controller->clone_attendee_to_event( $original, $event_1 );
		// Create a second Event part of the Series.
		$event_2 = tribe_events()->set_args( [
			'title'      => 'Test Event #2',
			'status'     => 'publish',
			'start_date' => '+27 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		$controller = $this->make_controller();
		// Clone the Attendee to the Event, it will also check the cloned Attendee in.
		$clone_2 = $controller->clone_attendee_to_event( $original, $event_2 );
		$commerce = Module::get_instance();
		$checkin_key = $commerce->checkin_key;

		$controller->register();

		$this->assertEquals( '', get_post_meta( $original, $checkin_key, true ),
			'The original Attendee should not be checked in at the start' );
		$this->assertEquals( '', get_post_meta( $clone_1, $checkin_key, true ),
			'The cloned Attendee should not be checked in at the start' );
		$this->assertEquals( '', get_post_meta( $clone_2, $checkin_key, true ),
			'The cloned Attendee should not be checked in at the start' );

		// Update the original: 1st and 2nd clone should be updated.
		foreach (
			[
				'post_title'   => 'Famous Bob',
				'post_excerpt' => 'That famous Bob from the movie no one saw',
				'post_parent'  => 2389, // Not really an existing post.
				'post_status'  => 'private',
			] as $post_field => $updated_post_field_value
		) {
			wp_update_post( [ 'ID' => $original, $post_field => $updated_post_field_value ] );

			$this->assertEquals( $updated_post_field_value, get_post_field( $post_field, $original ),
				'The original Attendee post field should have been updated following an original Attendee post field update.' );
			$this->assertEquals( $updated_post_field_value, get_post_field( $post_field, $clone_1 ),
				'The 1st cloned Attendee post field should have been updated following an original Attendee post field update.' );
			$this->assertEquals( $updated_post_field_value, get_post_field( $post_field, $clone_2 ),
				'The 2nd cloned Attendee post field should have been updated following an original Attendee post field update.' );
		}

		/*
		 * Note, the original Series Attendee cannot be checked in using the UI or the App (i.e. QR code)
		 * Here we trigger a low-level check-in/out flow based on meta functions to make sure the controller will
		 * handle them.
		 */
		update_post_meta( $original, $checkin_key, '1' );
		update_post_meta( $original, $checkin_key . '_details', 'some-details' );
		$this->assertEquals( '1', get_post_meta( $original, $checkin_key, true ) );
		$this->assertEquals( 'some-details', get_post_meta( $original, $checkin_key . '_details', true ) );
		$this->assertEquals( '', get_post_meta( $clone_1, $checkin_key, true ) );
		$this->assertEquals( '', get_post_meta( $clone_1, $checkin_key . '_details', true ) );
		$this->assertEquals( '', get_post_meta( $clone_2, $checkin_key, true ) );
		$this->assertEquals( '', get_post_meta( $clone_2, $checkin_key . '_details', true ) );

		// Adding a meta value to the original should add it to the clones.
		add_post_meta( $original, 'some_test_key', 23 );
		$this->assertEquals( 23, get_post_meta( $original, 'some_test_key', true ) );
		$this->assertEquals( 23, get_post_meta( $clone_1, 'some_test_key', true ) );
		$this->assertEquals( 23, get_post_meta( $clone_2, 'some_test_key', true ) );

		// Updating a meta value on the original should update the clones meta values.
		update_post_meta( $original, 'some_test_key', 89 );
		$this->assertEquals( 89, get_post_meta( $original, 'some_test_key', true ) );
		$this->assertEquals( 89, get_post_meta( $clone_1, 'some_test_key', true ) );
		$this->assertEquals( 89, get_post_meta( $clone_2, 'some_test_key', true ) );

		// Removing a meta value from the original should remove it from the clones.
		delete_post_meta( $original, 'some_test_key' );
		$this->assertEquals( '', get_post_meta( $original, 'some_test_key', true ) );
		$this->assertEquals( '', get_post_meta( $clone_1, 'some_test_key', true ) );
		$this->assertEquals( '', get_post_meta( $clone_2, 'some_test_key', true ) );

		// Trashing the original Attendee should trash the posts.
		wp_trash_post( $original );
		$this->assertEquals( 'trash', get_post_status( $original ) );
		$this->assertEquals( 'trash', get_post_status( $clone_1 ) );
		$this->assertEquals( 'trash', get_post_status( $clone_2 ) );

		// Deleting the original Attendee should trigger the deletion of the clones.
		wp_delete_post( $original, true );
		$this->assertNull( get_post( $original ) );
		$this->assertNull( get_post( $clone_1 ) );
		$this->assertNull( get_post( $clone_2 ) );
	}

	/**
	 * It should update original Attendee when cloned Attendee updated
	 *
	 * @test
	 */
	public function should_update_original_attendee_when_cloned_attendee_updated(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$original = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create an Event part of the Series.
		$event_1 = tribe_events()->set_args( [
			'title'      => 'Test Event #1',
			'status'     => 'publish',
			'start_date' => '+3 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		$controller = $this->make_controller();
		// Clone the Attendee to the Event, it will also check the cloned Attendee in.
		$clone_1 = $controller->clone_attendee_to_event( $original, $event_1 );
		// Create a second Event part of the Series.
		$event_2 = tribe_events()->set_args( [
			'title'      => 'Test Event #2',
			'status'     => 'publish',
			'start_date' => '+27 hours',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;
		$controller = $this->make_controller();
		// Clone the Attendee to the Event, it will also check the cloned Attendee in.
		$clone_2 = $controller->clone_attendee_to_event( $original, $event_2 );
		$commerce = Module::get_instance();
		$checkin_key = $commerce->checkin_key;

		$controller->register();

		$this->assertEquals( '', get_post_meta( $original, $checkin_key, true ),
			'The original Attendee should not be checked in at the start' );
		$this->assertEquals( '', get_post_meta( $clone_1, $checkin_key, true ),
			'The cloned Attendee should not be checked in at the start' );
		$this->assertEquals( '', get_post_meta( $clone_2, $checkin_key, true ),
			'The cloned Attendee should not be checked in at the start' );

		// Update the 1st clone: original and 2nd clone should be updated.
		foreach (
			[
				'post_title'   => 'Famous Bob',
				'post_excerpt' => 'That famous Bob from the movie no one saw',
				'post_parent'  => 2389, // Not really an existing post.
			] as $post_field => $updated_post_field_value
		) {
			wp_update_post( [ 'ID' => $clone_1, $post_field => $updated_post_field_value ] );

			$this->assertEquals( $updated_post_field_value, get_post_field( $post_field, $original ),
				'The original Attendee post field should have been updated following an original Attendee post field update.' );
			$this->assertEquals( $updated_post_field_value, get_post_field( $post_field, $clone_1 ),
				'The 1st cloned Attendee post field should have been updated following an original Attendee post field update.' );
			$this->assertEquals( $updated_post_field_value, get_post_field( $post_field, $clone_2 ),
				'The 2nd cloned Attendee post field should have been updated following an original Attendee post field update.' );
		}

		// Update the 2nd clone: original and 1st clone should be updated.
		foreach (
			[
				'post_title'   => 'Famous Alice',
				'post_excerpt' => 'That famous Alice from the movie everyone saw',
				'post_parent'  => 23892389, // Not really an existing post.
			] as $post_field => $updated_post_field_value
		) {
			wp_update_post( [ 'ID' => $clone_2, $post_field => $updated_post_field_value ] );

			$this->assertEquals( $updated_post_field_value, get_post_field( $post_field, $original ),
				'The original Attendee post field should have been updated following the 2nd Attendee clone post field update.' );
			$this->assertEquals( $updated_post_field_value, get_post_field( $post_field, $clone_1 ),
				'The 1st cloned Attendee post field should have been updated following the 2nd Attendee clone post field update.' );
			$this->assertEquals( $updated_post_field_value, get_post_field( $post_field, $clone_2 ),
				'The 2nd cloned Attendee post field should have been updated following the 2nd Attendee clone post field update.' );
		}

		// Adding a meta value to the 2nd clone should add it to the original and 1st clone.
		add_post_meta( $clone_2, 'some_test_key', 23 );
		$this->assertEquals( 23, get_post_meta( $original, 'some_test_key', true ) );
		$this->assertEquals( 23, get_post_meta( $clone_1, 'some_test_key', true ) );
		$this->assertEquals( 23, get_post_meta( $clone_2, 'some_test_key', true ) );

		// Updating a meta value on the 1st clone should update the original and 2nd clone meta_value
		update_post_meta( $clone_1, 'some_test_key', 89 );
		$this->assertEquals( 89, get_post_meta( $original, 'some_test_key', true ) );
		$this->assertEquals( 89, get_post_meta( $clone_1, 'some_test_key', true ) );
		$this->assertEquals( 89, get_post_meta( $clone_2, 'some_test_key', true ) );

		// Removing a meta value from the 2nd clone should remove it from the original and 2nd clone.
		delete_post_meta( $clone_2, 'some_test_key' );
		$this->assertEquals( '', get_post_meta( $original, 'some_test_key', true ) );
		$this->assertEquals( '', get_post_meta( $clone_1, 'some_test_key', true ) );
		$this->assertEquals( '', get_post_meta( $clone_2, 'some_test_key', true ) );

		// Updating or removing the check-in, or details, meta_key of a clone should not affect any other Attendee.
		update_post_meta( $clone_1, $checkin_key, '1' );
		update_post_meta( $clone_1, $checkin_key . '_details', 'some-details' );
		$this->assertEquals( '', get_post_meta( $original, $checkin_key, true ) );
		$this->assertEquals( '', get_post_meta( $original, $checkin_key . '_details', true ) );
		$this->assertEquals( '', get_post_meta( $clone_2, $checkin_key, true ) );
		$this->assertEquals( '', get_post_meta( $clone_2, $checkin_key . '_details', true ) );

		// Trashing the 1st clone should trash the original or the 2nd clone.
		wp_trash_post( $clone_1 );
		$this->assertEquals( 'publish', get_post_status( $original ) );
		$this->assertEquals( 'trash', get_post_status( $clone_1 ) );
		$this->assertEquals( 'publish', get_post_status( $clone_2 ) );

		// Deleting the 2nd clone should not affect the original or the 1st clone.
		wp_delete_post( $clone_2, true );
		$this->assertInstanceOf( WP_Post::class, get_post( $original ) );
		$this->assertEquals( 'trash', get_post_status( $clone_1 ) );
		$this->assertNull( get_post( $clone_2 ) );
	}

	// @todo test that provisional ID upping will trigger update of the Series Pass Attendee <> Event meta value

	// @todo queries!
}