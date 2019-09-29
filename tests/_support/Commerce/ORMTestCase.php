<?php

namespace Tribe\Tickets\Test\Commerce;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;

/**
 * Class ORMTestCase
 *
 * @package Tribe\Tickets\Test\Commerce
 */
class ORMTestCase extends Test_Case {

	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * The array of generated data, initiated with keys to hint what we do in this file and to avoid any fatal error.
	 *
	 * @var array
	 */
	protected $test_data = [
		'users',
		'events',
		'attendees',
		'rsvps',
		'paypal_tickets',
	];

	public function setUp() {
		parent::setUp();

		$this->factory()->event = new Event();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
			$paypal = tribe( 'tickets.commerce.paypal' );

			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = $paypal->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );

		// Setup test data here.
		$this->setup_test_data();
	}

	/**
	 * Get test matrix with all the assertions filled out.
	 *
	 * Method naming:
	 * "Match" means the filter finds what we expect it to with the created data.
	 * "Mismatch" means the filter should not find anything because we don't have a matching ID to find anything for,
	 * such as User2 is an attendee so NOT finding an attendee for them is an expected "mismatch".
	 */
	public function get_attendee_test_matrix() {
		// Event
		yield 'event match' => [ 'get_test_matrix_event_match' ];
		yield 'event mismatch' => [ 'get_test_matrix_event_mismatch' ];
		// Event Not In
		yield 'event not in match' => [ 'get_test_matrix_event_not_in_match' ];
		yield 'event not in mismatch' => [ 'get_test_matrix_event_not_in_mismatch' ];

		// RSVP
		yield 'rsvp match' => [ 'get_test_matrix_rsvp_match' ];
		yield 'rsvp mismatch' => [ 'get_test_matrix_rsvp_mismatch' ];
		// RSVP Not In
		yield 'rsvp not in match' => [ 'get_test_matrix_rsvp_not_in_match' ];
		yield 'rsvp not in mismatch' => [ 'get_test_matrix_rsvp_not_in_mismatch' ];

		// Tribe Commerce PayPal
		yield 'paypal match' => [ 'get_test_matrix_paypal_match' ];
		yield 'paypal mismatch' => [ 'get_test_matrix_paypal_mismatch' ];
		// Tribe Commerce PayPal Not In
		yield 'paypal not in match' => [ 'get_test_matrix_paypal_not_in_match' ];
		yield 'paypal not in mismatch' => [ 'get_test_matrix_paypal_not_in_mismatch' ];
	}

	/**
	 * EVENTS
	 */

	/**
	 * Get test matrix for Event match.
	 */
	public function get_test_matrix_event_match() {
		$expected = [
			$this->get_attendee_id( 0 ),
			$this->get_attendee_id( 1 ),
			$this->get_attendee_id( 2 ),
			$this->get_attendee_id( 3 ),
		];

	/**
	 * EVENTS
	 */

	/**
	 * Get test matrix for Event match.
	 */
	public function get_test_matrix_event_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'event',
			// Filter arguments to use.
			[
				$this->get_event_id( 0 ),
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees'] ),
		];
	}

	/**
	 * Get test matrix for Event mismatch.
	 */
	public function get_test_matrix_event_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'event',
			// Filter arguments to use.
			[
				$this->get_event_id( 1 ),
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for Event Not In match.
	 */
	public function get_test_matrix_event_not_in_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'event__not_in',
			// Filter arguments to use.
			[
				$this->get_event_id( 1 ),
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees'] ),
		];
	}

	/**
	 * Get test matrix for Event Not In mismatch.
	 */
	public function get_test_matrix_event_not_in_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'event__not_in',
			// Filter arguments to use.
			[
				$this->get_event_id( 0 ),
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * RSVPS
	 */

	/**
	 * Get test matrix for RSVP match.
	 */
	public function get_test_matrix_rsvp_match() {
		$expected = [
			$this->get_attendee_id( 0 ), // User2
			$this->get_attendee_id( 1 ), // User3
			$this->get_attendee_id( 2 ), // Guest
			$this->get_attendee_id( 3 ), // Guest
		];

		return [
			// Repository
			'rsvp',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				$this->get_rsvp_id( 0 ),
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for RSVP mismatch.
	 */
	public function get_test_matrix_rsvp_mismatch() {
		return [
			// Repository
			'rsvp',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				$this->get_rsvp_id( 1 ),
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for RSVP Not In match.
	 */
	public function get_test_matrix_rsvp_not_in_match() {
		$expected = [
			$this->get_attendee_id( 0 ), // User2
			$this->get_attendee_id( 1 ), // User3
			$this->get_attendee_id( 2 ), // Guest
			$this->get_attendee_id( 3 ), // Guest
		];

		return [
			// Repository
			'rsvp',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				$this->get_rsvp_id( 1 ),
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for RSVP Not In mismatch.
	 */
	public function get_test_matrix_rsvp_not_in_mismatch() {
		return [
			// Repository
			'rsvp',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				$this->get_rsvp_id( 0 ),
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Tribe Commerce
	 */

	/**
	 * Get test matrix for Tribe Commerce PayPal match.
	 */
	public function get_test_matrix_paypal_match() {
		$expected = [
			$this->get_attendee_id( 4 ), // User3
			$this->get_attendee_id( 5 ), // User4
			$this->get_attendee_id( 6 ), // Guest
			$this->get_attendee_id( 7 ), // Guest
		];

		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				$this->get_paypal_tickets_id( 0 ),
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for Tribe Commerce PayPal mismatch.
	 */
	public function get_test_matrix_paypal_mismatch() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				$this->get_paypal_tickets_id( 1 ),
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for Tribe Commerce PayPal Not In match.
	 */
	public function get_test_matrix_paypal_not_in_match() {
		$expected = [
			$this->get_attendee_id( 4 ), // User3
			$this->get_attendee_id( 5 ), // User4
			$this->get_attendee_id( 6 ), // Guest
			$this->get_attendee_id( 7 ), // Guest
		];

		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				$this->get_paypal_tickets_id( 1 ),
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for Tribe Commerce PayPal Not In mismatch.
	 */
	public function get_test_matrix_paypal_not_in_mismatch() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				$this->get_paypal_tickets_id( 0 ),
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Helpers
	 */

	protected function get_event_id( $index ) {
		if ( isset( $this->test_data['events'][ $index ] ) ) {
			return $this->test_data['events'][ $index ];
		}

		return 0;
	}

	protected function get_attendee_id( $index ) {
		if ( isset( $this->test_data['attendees'][ $index ] ) ) {
			return $this->test_data['attendees'][ $index ];
		}

		return 0;
	}

	protected function get_user_id( $index ) {
		if ( isset( $this->test_data['users'][ $index ] ) ) {
			return $this->test_data['users'][ $index ];
		}

		return 0;
	}

	protected function get_rsvp_id( $index ) {
		if ( isset( $this->test_data['rsvps'][ $index ] ) ) {
			return $this->test_data['rsvps'][ $index ];
		}

		return 0;
	}

	protected function get_paypal_tickets_id( $index ) {
		if ( isset( $this->test_data['paypal_tickets'][ $index ] ) ) {
			return $this->test_data['paypal_tickets'][ $index ];
		}

		return 0;
	}

	/**
	 * Setup list of test data.
	 *
	 * We create 2 events, one having an author and various types of tickets that have attendees,
	 * and the other having neither an author nor tickets (and therefore no attendees).
	 * Some ticket purchases are by valid users and others are by non-users (site guests as attendees).
	 * Event 1 has:
	 * - User1 is author
	 * - User2 is RSVP attendee
	 * - User3 is RSVP attendee and PayPal attendee
	 * - User4 is PayPal attendee
	 * - So 1 RSVP ticket having 4 attendees (2 guests) and 1 PayPal ticket having 4 attendees (2 guests)
	 *   for a total of 8 attendees
	 * - And 3 RSVP tickets and 3 PayPal tickets, each having zero attendees
	 * Event 2 has: no author, no tickets (therefore no attendees)
	 * Note that guest purchasers will still have User ID# zero saved to `_tribe_tickets_attendee_user_id` meta field.
	 */
	protected function setup_test_data() {
		$test_data = [
			'users'          => [],
			// 4 total: 1 = Event author, not Attendee; 2 = only RSVP attendee; 3 = RSVP & PayPal attendee; 4 = only PayPal attendee
			'events'         => [],
			// 2 total: 1 = has Author, Tickets, and Attendees; 2 = Author ID of zero and no Tickets (so no Attendees)
			'rsvps'          => [],
			// 4 total: 1 = 4 Attendees (users 2 & 3 + 2 guests); 2, 3, & 4 = no Attendees
			'paypal_tickets' => [],
			// 4 total: 1 = 4 Attendees (users 3 & 4 + 2 guests); 2, 3, & 4 = no Attendees
			'attendees'      => [],
			// 8 total (4 by logged in): 1 & 2 = RSVP by logged in; 3 & 4 = RSVP by logged out; 5 & 6 = PayPal by logged in; 7 & 8: PayPal by logged out
		];

		// Create test user 1. Author of one of the two Events.
		$test_data['users'][] = $user_id_one = $this->factory()->user->create( [ 'role' => 'author' ] );

		// Create test users 2, 3, and 4 as Attendees
		$test_data['users'][] = $user_id_two = $this->factory()->user->create();
		$test_data['users'][] = $user_id_three = $this->factory()->user->create();
		$test_data['users'][] = $user_id_four = $this->factory()->user->create();

		// Create test event 1, having tickets
		$event_id = $this->factory()->event->create( [
			'post_title'  => 'Test event 1',
			'post_author' => $user_id_one,
		] );

		$test_data['events'][] = $event_id;

		// Create test event 2, having no assigned author nor tickets
		$test_data['events'][] = $this->factory()->event->create( [
			'post_title'  => 'Test event 2',
			'post_author' => 0,
		] );

		// Create test RSVP ticket on Event1
		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );

		// Add User2 and User3 as RSVP attendees
		$test_data['attendees'][] = $this->create_attendee_for_ticket( $rsvp_ticket_id, $event_id, [ 'user_id' => $user_id_two ] );
		$test_data['attendees'][] = $this->create_attendee_for_ticket( $rsvp_ticket_id, $event_id, [ 'user_id' => $user_id_three ] );

		// Add 2 guest purchasers to RSVP Ticket already having other Attendees
		$test_data['attendees'][] = $this->create_attendee_for_ticket( $rsvp_ticket_id, $event_id );
		$test_data['attendees'][] = $this->create_attendee_for_ticket( $rsvp_ticket_id, $event_id );

		// Create more RSVP tickets that will never have any attendees
		$test_data['rsvps'] = array_merge( [ $rsvp_ticket_id ], $this->create_many_rsvp_tickets( 3, $event_id ) );

		// Create test PayPal ticket
		$paypal_ticket_id = $this->create_paypal_ticket( $event_id, 5 );

		// Add User3 and User4 as Tribe Commerce PayPal Ticket attendees
		$test_data['attendees'][] = $this->create_attendee_for_ticket( $paypal_ticket_id, $event_id, [ 'user_id' => $user_id_three ] );
		$test_data['attendees'][] = $this->create_attendee_for_ticket( $paypal_ticket_id, $event_id, [ 'user_id' => $user_id_four ] );

		// Add 2 guest purchasers to the PayPal Ticket already having other Attendees
		$test_data['attendees'][] = $this->create_attendee_for_ticket( $paypal_ticket_id, $event_id );
		$test_data['attendees'][] = $this->create_attendee_for_ticket( $paypal_ticket_id, $event_id );

		// Create more PayPal tickets that will never have any attendees
		$test_data['paypal_tickets'] = array_merge( [ $paypal_ticket_id ], $this->create_many_paypal_tickets( 3, $event_id ) );

		// Save test data to reference.
		$this->test_data = $test_data;
	}

	private function get_assertions_array( array $attendee_ids ) {
		// Assume 'count' and 'found' will always be the same, since ORM defaults to unlimited (-1) results.
		$total = count( $attendee_ids );

		return [
			'get_ids' => $attendee_ids,
			'all'     => array_map( 'get_post', $attendee_ids ),
			'count'   => $total,
			'found'   => $total,
		];
	}
}