<?php
namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class Template_TagsTest extends \Codeception\TestCase\WPTestCase {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->factory()->event = new Event();
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * Wrapper function to create RSVPs
	 *
	 * @since TBD
	 *
	 * @param int $event_id
	 * @param int $capacity
	 * @param int $stock
	 *
	 * @return int $ticket_id
	 */
	protected function make_sales_rsvp( $event_id, $capacity, $stock = false ) {
		$ticket_id = $this->make_ticket( $event_id, 'tribe_rsvp_tickets', $capacity, $stock );

		return $ticket_id;
	}

	/**
	 * Wrapper function to create tribe-commerce tickets
	 *
	 * @since TBD
	 *
	 * @param int $event_id
	 * @param int $capacity
	 * @param int $price
	 * @param int $stock
	 *
	 * @return int $ticket_id
	 */
	protected function make_sales_ticket( $event_id, $capacity, $price, $stock = false ) {
		$ticket_id = $this->make_ticket( $event_id, 'tribe_tpp_tickets', $capacity, $stock, $price );

		return $ticket_id;
	}

	/**
	 * Create a ticket attached to an event
	 *
	 * @param int $event_id
	 * @param string $ticket_type
	 * @param int $capacity
	 * @param int $stock
	 * @param int $price
	 *
	 * @return int $ticket_id
	 */
	protected function make_ticket( $event_id, $ticket_type, $capacity, $stock, $price = false ) {
		// set stock to capacity if not passed
		$stock = ( false === $stock ) ? $capacity : $stock;

		$for_event = '_' . str_replace( 'tickets', 'for_event', $ticket_type );

		$args = [
			'post_type'   => $ticket_type,
			'post_status' => 'publish',
			'meta_input'  => [
				$for_event  => $event_id,
				'_tribe_ticket_capacity' => $capacity,
				'_stock'                 => $stock,
			]
		];

		if ( $price ) {
			$args[ 'meta_input' ][ '_price' ] = $price;
		}

		$ticket_id = $this->factory()->post->create( $args );

		return $ticket_id;
	}

	/**
	 * @test
	 * it not should allow tickets on posts by default
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_post_type_enabled()
	 */
	public function it_should_not_allow_tickets_on_posts_by_default() {
		$allowed = tribe_tickets_post_type_enabled( 'post' );

		$this->assertFalse( $allowed );
	}

	/**
	 * @test
	 * it should allow tickets on posts when enabled
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_post_type_enabled()
	 */
	public function it_should_allow_tickets_on_posts_when_enabled() {
		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
			'post',
		] );

		$allowed = tribe_tickets_post_type_enabled( 'post' );

		$this->assertTrue( $allowed );
	}

	/**
	 * @test
	 * it should return the post id - events support tickets by default
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_parent_post()
	 */
	public function it_should_return_the_post_id_events_support_tickets_by_default() {
		$event_id = $this->factory()->event->create();
		$parent   = tribe_tickets_parent_post( $event_id );

		$this->assertEquals( $event_id, $parent->ID );
	}

	 /**
	 * @test
	 * it should return the non event post id if it supports tickets
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_parent_post()
	 */
	public function it_should_return_the_non_event_post_id_if_it_supports_tickets() {
		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
			'post',
		] );

		$non_event_id = wp_insert_post( ['id' => 1337] );
		$parent       = tribe_tickets_parent_post( $non_event_id );

		$this->assertEquals( $non_event_id, $parent );
	}

	/**
	* @test
	* it should return null if it does not supports tickets
	*
	* @since TBD
	*
	* @covers tribe_tickets_parent_post()
	*/
	public function it_should_return_null_if_it_does_not_supports_tickets() {
		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
		] );

		$non_event_id = wp_insert_post( ['id' => 1337] );
		$parent       = tribe_tickets_parent_post( $non_event_id );

		$this->assertNull( $parent );
	}

	/**
	* @test
	* it should return true if event has tickets
	*
	* @since TBD
	*
	* @covers tribe_events_has_tickets()
	*/
	public function it_should_return_true_if_event_has_rsvps() {
		$event_id = $this->factory()->event->create();
		$this->create_rsvp_ticket( $event_id );

		$tickets = tribe_events_has_tickets( $event_id );

		$this->assertTrue( $tickets );
	}

	/**
	* @test
	* it should return true if event has tickets
	*
	* @since TBD
	*
	* @covers tribe_events_has_tickets()
	*/
	public function it_should_return_true_if_event_has_tickets() {
		$event_id = $this->factory()->event->create();
		$this->create_paypal_ticket( $event_id, 2 );

		$tickets = tribe_events_has_tickets( $event_id );

		$this->assertTrue( $tickets );
	}

	/**
	* @test
	* it should return true if non-event post has tickets
	*
	* @since TBD
	*
	* @covers tribe_events_has_tickets()
	*/
	public function it_should_return_true_if_non_event_post_has_rsvps() {
		// Mkae sure it's allowed first!
		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
			'post',
		] );

		$event_id = $this->factory()->post->create();
		$this->create_rsvp_ticket( $event_id );

		$tickets = tribe_events_has_tickets( $event_id );

		$this->assertTrue( $tickets );
	}

	/**
	* @test
	* it should return false if event has no tickets
	*
	* @since TBD
	*
	* @covers tribe_events_has_tickets()
	*/
	public function it_should_return_false_if_event_has_no_tickets() {
		$event_id = $this->factory()->post->create();
		$tickets  = tribe_events_has_tickets( $event_id );

		$this->assertFalse( $tickets );
	}

	/**
	* @test
	* it should return correct number of tickets on sold out event
	*
	* @since TBD
	*
	* @covers tribe_events_count_available_tickets()
	*/
	public function it_should_return_correct_number_of_rsvps_on_sold_out_event() {
		$event_id = $this->factory()->event->create();

		// sold out
		$this->make_sales_rsvp( $event_id, 5, 0 );
		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 0, $count );
	}

	/**
	* @test
	* it should return correct number of tickets on event with no sales
	*
	* @since TBD
	*
	* @covers tribe_events_count_available_tickets()
	*/
	public function it_should_return_correct_number_of_rsvps_on_event_with_no_sales() {
		$event_id = $this->factory()->event->create();

		// no sales
		$this->make_sales_rsvp( $event_id, 5 );
		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 5, $count );
	}

	/**
	* @test
	* it should return correct number of tickets on event with some sales
	*
	* @since TBD
	*
	* @covers tribe_events_count_available_tickets()
	*/
	public function it_should_return_correct_number_of_rsvps_on_event_with_some_sales() {
		$event_id = $this->factory()->event->create();

		// not sold out
		$this->make_sales_rsvp( $event_id, 5, 3 );
		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 3, $count );
	}

	/**
	* @test
	* it should return correct number of tickets on event with multiple tickets
	*
	* @since TBD
	*
	* @covers tribe_events_count_available_tickets()
	*/
	public function it_should_return_correct_number_of_rsvps_on_event_with_multiple_rsvps() {
		$event_id = $this->factory()->event->create();

		// multiple rsvp
		$this->make_sales_rsvp( $event_id, 5, 4 );
		$this->make_sales_rsvp( $event_id, 5, 3 );
		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 7, $count );
	}

	/**
	* @test
	* it should return correct number of tickets on event with mixed tickets
	*
	* @since TBD
	*
	* @covers tribe_events_count_available_tickets()
	*/
	public function it_should_return_correct_number_of_tickets_on_event_with_mixed_tickets() {
		$event_id = $this->factory()->event->create();

		// mixed rsvp/ticket
		$this->make_sales_rsvp( $event_id, 5, 4 );
		$this->make_sales_ticket( $event_id, 5, 2, 3 );
		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 7, $count );
	}

	/**
	* @test
	* it should return true if event has unlimited rsvps
	*
	* @since TBD
	*
	* @covers tribe_events_count_available_tickets()
	*/
	public function it_should_return_true_if_event_has_unlimited_rsvps(){
		$event_id = $this->factory()->event->create();
		$this->make_sales_rsvp( $event_id, -1 );

		$unlimited = tribe_events_has_unlimited_stock_tickets( $event_id );

		$this->assertTrue( $unlimited );
	}

	/**
	* @test
	* it should return true if event has unlimited tickets
	*
	* @since TBD
	*
	* @covers tribe_events_count_available_tickets()
	*/
	public function it_should_return_true_if_event_has_unlimited_tickets(){
		$event_id = $this->factory()->event->create();
		$this->make_sales_ticket( $event_id, -1, 1 );

		$unlimited = tribe_events_has_unlimited_stock_tickets( $event_id );

		$this->assertTrue( $unlimited );
	}

	/**
	* @test
	* it should return false if event has no unlimited rsvps
	*
	* @since TBD
	*
	* @covers tribe_events_count_available_tickets()
	*/
	public function it_should_return_false_if_event_has_no_unlimited_tickets(){
		$event_id = $this->factory()->event->create();
		$this->make_sales_rsvp( $event_id, 5 );

		$unlimited = tribe_events_has_unlimited_stock_tickets( $event_id );

		$this->assertFalse( $unlimited );
	}

}
