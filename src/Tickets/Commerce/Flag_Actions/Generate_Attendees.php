<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Status\Status_Abstract;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Status\Status_Interface;
use Tribe__Utils__Array as Arr;
use Tribe\Tickets\Plus\Attendee_Registration\IAC;

/**
 * Class Attendee_Generation
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Generate_Attendees extends Flag_Action_Abstract {
	/**
	 * {@inheritDoc}
	 */
	protected $flags = [
		'generate_attendees',
	];

	/**
	 * {@inheritDoc}
	 */
	protected $post_types = [
		Order::POSTTYPE
	];

	/**
	 * Hooks any WordPress filters related to this Flag Action.
	 *
	 * @since 5.1.10
	 */
	public function hook() {
		parent::hook();

		$status = $this->get_status_when_to_trigger();
		add_filter( "tec_tickets_commerce_order_status_{$status->get_slug()}_get_flags", [ $this, 'modify_status_with_attendee_generation_flag' ], 10, 3 );
	}

	/**
	 * Returns the instance of the status we trigger attendee generation.
	 *
	 * @since 5.1.10
	 *
	 * @return Status_Abstract
	 */
	public function get_status_when_to_trigger() {
		return tribe( Status_Handler::class )->get_inventory_decrease_status();
	}

	/**
	 * Include generate_attendee flag to either Completed or Pending
	 *
	 * @since 5.1.10
	 *
	 * @param string[]        $flags  Which flags will trigger this action.
	 * @param \WP_Post        $post   Post object.
	 * @param Status_Abstract $status Instance of action flag we are triggering.
	 *
	 * @return string[]
	 */
	public function modify_status_with_attendee_generation_flag( $flags, $post, $status ) {
		$flags[] = 'generate_attendees';

		return $flags;
	}

	/**
	 * {@inheritDoc}
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $order ) {
		// @todo we need an error handling piece here.
		if ( empty( $order->cart_items ) ) {
			return;
		}

		$default_currency = tribe_get_option( Settings::$option_currency_code, 'USD' );

		// @todo @bordoni move to ET+
		/* @var $iac IAC */
		$iac             = tribe( 'tickets-plus.attendee-registration.iac' );
		$iac_name_field  = $iac->get_iac_ticket_field_slug_for_name();
		$iac_email_field = $iac->get_iac_ticket_field_slug_for_email();

		foreach ( $order->cart_items as $ticket_id => $item ) {
			$ticket = \Tribe__Tickets__Tickets::load_ticket_object( $item['ticket_id'] );
			if ( null === $ticket ) {
				continue;
			}

			$extra    = Arr::get( $item, 'extra', [] );
			$quantity = Arr::get( $item, 'quantity', 1 );

			// Skip generating for zero-ed items.
			if ( 0 >= $quantity ) {
				continue;
			}

			$attendees = [];

			for ( $i = 0; $i < $quantity; $i ++ ) {
				$args = [
					'opt_out'       => Arr::get( $extra, 'optout' ),
					'price_paid'    => Arr::get( $item, 'price' ),
					'currency'      => Arr::get( $item, 'currency', $default_currency ),
					'security_code' => tribe( Module::class )->generate_security_code( time() . '-' . $i ),
				];

				$args['fields'] = Arr::get( $extra, [ 'attendees', $i + 1, 'meta' ], [] );

				// @todo @bordoni move to ET+
				if ( IAC::NONE_KEY !== $iac->get_iac_setting_for_ticket( $ticket_id ) ) {
					$full_name = Arr::get( $args['fields'], $iac_name_field );
					if ( ! empty( $full_name ) ) {
						$args['full_name'] = $full_name;
					}

					$email = Arr::get( $args['fields'], $iac_email_field );
					if ( ! empty( $email ) ) {
						$args['email'] = $email;
					}
				}

				/**
				 * Filters the attendee data before it is saved.
				 *
				 * @since TBD
				 *
				 * @param array<mixed>             $args       The attendee creation args.
				 * @param \Tribe__Tickets__Tickets $ticket     The ticket the attendee is generated for.
				 * @param \WP_Post                 $order      The order the attendee is generated for.ww
				 * @param Status_Interface         $new_status New post status.
				 * @param Status_Interface|null    $old_status Old post status.
				 */
				$args = apply_filters( 'tec_tickets_attendee_generation_args', $args, $ticket, $order, $new_status, $old_status );

				$attendee = tribe( Attendee::class )->create( $order, $ticket, $args );

				/**
				 * Fires after an attendee is generated for an order.
				 *
				 * @since TBD
				 *
				 * @param Attendee                 $attendee   The generated attendee.
				 * @param \Tribe__Tickets__Tickets $ticket     The ticket the attendee is generated for.
				 * @param \WP_Post                 $order      The order the attendee is generated for.
				 * @param Status_Interface         $new_status New post status.
				 * @param Status_Interface|null    $old_status Old post status.
				 */
				do_action( 'tec_tickets_attendee_generated', $attendee, $ticket, $order, $new_status, $old_status );

				$attendees[] = $attendee;
			}

			/**
			 * Fires after all attendees are generated for an order.
			 *
			 * @since TBD
			 *
			 * @param array<Attendee>          $attendees  The generated attendees.
			 * @param \Tribe__Tickets__Tickets $ticket     The ticket the attendee is generated for.
			 * @param \WP_Post                 $order      The order the attendee is generated for.
			 * @param Status_Interface         $new_status New post status.
			 * @param Status_Interface|null    $old_status Old post status.
			 */
			do_action( 'tec_tickets_attendees_generated', $attendees, $ticket, $order, $new_status, $old_status );
		}
	}
}