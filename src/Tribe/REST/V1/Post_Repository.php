<?php


class
Tribe__Tickets__REST__V1__Post_Repository
	extends Tribe__REST__Post_Repository
	implements Tribe__Tickets__REST__Interfaces__Post_Repository {

	/**
	 * A post type to get data request handler map.
	 *
	 * @var array
	 */
	protected $types_get_map = array();

	/**
	 * @var Tribe__REST__Messages_Interface
	 */
	protected $messages;

	/**
	 * @var string
	 */
	protected $global_id_key = '_tribe_global_id';

	/**
	 * @var string
	 */
	protected $global_id_lineage_key = '_tribe_global_id_lineage';

	/**
	 * @var int Cached current ticket id.
	 */
	protected $current_ticket_id;

	/**
	 * @var Tribe__Tickets__Ticket_Object Cached current ticket object;
	 */
	protected $current_ticket_object;

	/**
	 * @var Tribe__Tickets__Tickets Cached current ticket provider.
	 */
	protected $current_ticket_provider;

	/**
	 * @var WP_Post Cached current ticket post.
	 */
	protected $current_ticket_post;

	public function __construct( Tribe__REST__Messages_Interface $messages = null ) {
		$this->types_get_map = array(
			Tribe__Tickets__RSVP::ATTENDEE_OBJECT => array( $this, 'get_attendee_data' ),
		);

		$this->messages = $messages ? $messages : tribe( 'tickets.rest-v1.messages' );
	}

	/**
	 * Retrieves an array representation of the post.
	 *
	 * @since TBD
	 *
	 * @param int    $id      The post ID.
	 * @param string $context Context of data.
	 *
	 * @return array An array representation of the post.
	 */
	public function get_data( $id, $context = '' ) {
		$post = get_post( $id );

		if ( empty( $post ) ) {
			return array();
		}

		if ( ! isset( $this->types_get_map[ $post->post_type ] ) ) {
			return (array) $post;
		}

		return call_user_func( $this->types_get_map[ $post->post_type ], $id, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_attendee_data( $attendee_id, $context = '' ) {
		$attendee_post = get_post( $attendee_id );

		if ( ! $attendee_post instanceof WP_Post ) {
			// the attendee post does not exist, user error
			return new WP_Error( 'attendee-not-found', $this->messages->get_message( 'attendee-not-found' ), array( 'status' => 404 ) );
		}

		$attendee_id = $attendee_post->ID;

		/** @var Tribe__Tickets__Data_API $data_api */
		$data_api = tribe( 'tickets.data_api' );
		/** @var Tribe__Tickets__Tickets $provider */
		$provider = $data_api->get_ticket_provider( $attendee_id );

		if ( false === $provider ) {
			// the attendee post does exist but it does not make sense on the server, server error
			return new WP_Error( 'attendee-not-found', $this->messages->get_message( 'attendee-not-found' ), array( 'status' => 500 ) );
		}

		// The return value of this function will always be an array even if we only want one object.
		$attendee = $provider->get_attendees_by_attendee_id( $attendee_id );

		if ( empty( $attendee ) ) {
			// the attendee post does exist but it does not make sense on the server, server error
			return new WP_Error( 'attendee-not-found', $this->messages->get_message( 'attendee-not-found' ), array( 'status' => 500 ) );
		}

		// See note above, this is an array with one element in it
		$attendee = $attendee[0];

		return $this->build_attendee_data( $attendee );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_ticket_data( $ticket_id, $context = '' ) {
		$ticket = $this->get_ticket_object( $ticket_id );

		if ( $ticket instanceof WP_Error ) {
			return $ticket;
		}

		// make sure the data is a nested array
		$data = json_decode( json_encode( $ticket ), true );

		$data['post_id']  = $ticket->get_event_id();
		$data['provider'] = $this->get_provider_slug( $ticket->provider_class );

		try {
			$this->reformat_ticket_data( $data );
			$this->add_ticket_global_id_data( $data );
			$this->add_ticket_post_data( $data );
			$this->add_ticket_meta_data( $data );
			$this->add_ticket_attendees_data( $data );
			$this->add_ticket_rest_data( $data );
			$this->clean_ticket_data( $data );
		} catch ( Exception $e ) {
			if ( $e instanceof Tribe__REST__Exceptions__Exception ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}

			/** @var Tribe__REST__Exceptions__Exception $e */
			return new WP_Error(
				'error',
				__( 'An error happened while building the response: ', 'event-tickets' ) . $e->getMessage(),
				array( 'status' => $e->getStatus() )
			);
		}

		/**
		 * Filters the data that will be returned for a ticket.
		 *
		 * @since TBD
		 *
		 * @param array  $data
		 * @param int    $ticket_id
		 * @param string $context
		 */
		$data = apply_filters( 'tribe_tickets_rest_api_ticket_data', $data, $ticket_id, $context );

		return $data;
	}

	/**
	 * Gets the ticket object from a ticket ID.
	 *
	 * @since TBD
	 *
	 * @param int|WP_Post $ticket_id
	 *
	 * @return Tribe__Tickets__Ticket_Object|bool The ticket object or `false`
	 */
	protected function get_ticket_object( $ticket_id ) {
		if ( isset( $this->current_ticket_id ) && $ticket_id != $this->current_ticket_id ) {
			$this->reset_ticket_cache();
		}

		if (
			isset( $this->current_ticket_object )
			&& $this->current_ticket_object instanceof Tribe__Tickets__Ticket_Object
		) {
			return $this->current_ticket_object;
		}

		if ( $ticket_id instanceof WP_Post ) {
			$ticket_id = $ticket_id->ID;
		}

		/** @var Tribe__Tickets__Tickets $provider */
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( ! $provider instanceof Tribe__Tickets__Tickets ) {
			return new WP_Error( 'ticket-provider-not-found', $this->messages->get_message( 'ticket-provider-not-found' ), array( 'status' => 500 ) );
		}

		$this->current_ticket_provider = $provider;

		$post = $provider->get_event_for_ticket( $ticket_id );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error( 'ticket-post-not-found', $this->messages->get_message( 'ticket-post-not-found' ), array( 'status' => 500 ) );
		}

		$this->current_ticket_post = $post;

		/** @var Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $provider->get_ticket( $post->ID, $ticket_id );

		if ( ! $ticket instanceof Tribe__Tickets__Ticket_Object ) {
			return new WP_Error( 'ticket-object-not-found', $this->messages->get_message( 'ticket-object-not-found' ), array( 'status' => 500 ) );
		}

		$this->current_ticket_object = $ticket;

		return $ticket;
	}

	/**
	 * Resets the current ticket caches.
	 *
	 * @since TBD
	 */
	public function reset_ticket_cache() {
		unset( $this->current_ticket_id, $this->current_ticket_provider, $this->current_ticket_post, $this->current_ticket_object );
	}

	/**
	 * Returns the slug for provider.
	 *
	 * @since TBD
	 *
	 * @param string|object $provider_class The provider object or class.
	 *
	 * @return string
	 */
	protected function get_provider_slug( $provider_class ) {
		if ( is_object( $provider_class ) ) {
			$provider_class = get_class( $provider_class );
		}

		$map = array(
			'Tribe__Tickets__RSVP'                             => 'rsvp',
			'Tribe__Tickets__Commerce__PayPal__Main'           => 'tribe-commerce',
			'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' => 'woo',
			'Tribe__Tickets_Plus__Commerce__EDD__Main'         => 'edd',
		);

		/**
		 * Filters the provider class to slug map.
		 *
		 * @since TBD
		 *
		 * @param array $map A map in the shape [ <class> => <slug> ]
		 * @param string The provider class
		 */
		$map = apply_filters( 'tribe_tickets_rest_provider_slug_map', $map, $provider_class );

		$default = array_values( $map )[0];

		return Tribe__Utils__Array::get( $map, $provider_class, $default );
	}

	/**
	 * Reformats the data to stick with the expected format.
	 *
	 * @since TBD
	 *
	 * @param array $data
	 */
	protected function reformat_ticket_data( array &$data ) {
		$rename_map = array(
			'ID' => 'id',
		);

		foreach ( $rename_map as $from_key => $to_key ) {
			if ( isset( $data[ $from_key ] ) && ! isset( $data[ $to_key ] ) ) {
				$data[ $to_key ] = $data[ $from_key ];
				unset( $data[ $from_key ] );
			}
		}
	}

	/**
	 * Adds the global ID information to the ticket data.
	 *
	 * @since TBD
	 *
	 * @param array $data
	 *
	 * @throws Tribe__REST__Exceptions__Exception If the global ID generation fails.
	 */
	protected function add_ticket_global_id_data( array &$data ) {
		$provider_class = $data['provider_class'];
		$ticket_id      = $data['id'];

		$global_id = $this->get_ticket_global_id( $ticket_id, $provider_class );

		if ( false === $global_id ) {
			throw new Tribe__REST__Exceptions__Exception(
				$this->messages->get_message( 'error-global-id-generation' ),
				'error-global-id-generation',
				500
			);
		}

		$data['global_id']         = $global_id;
		$data['global_id_lineage'] = $this->get_ticket_global_id_lineage( $ticket_id, $global_id );
	}

	/**
	 * Returns a ticket global ID.
	 *
	 * If not set/updated for the attendee than the method will generate/update it.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id
	 * @param string $provider_class
	 *
	 * @return bool|string
	 */
	public function get_ticket_global_id( $ticket_id, $provider_class = null ) {
		$existing = get_post_meta( $ticket_id, $this->global_id_key, true );

		if ( ! empty( $existing ) ) {
			return $existing;
		}

		if ( null === $provider_class ) {
			/** @var Tribe__Tickets__Tickets $provider */
			$provider = tribe_tickets_get_ticket_provider( $ticket_id );

			if ( ! $provider instanceof Tribe__Tickets__Tickets ) {
				return false;
			}

			$provider_class = get_class( $provider );
		}

		$generator = new Tribe__Tickets__Global_ID();
		$generator->origin( home_url() );
		$type = $this->get_provider_slug( $provider_class );
		$generator->type( $type );

		$global_id = $generator->generate( array(
			'type' => $type,
			'id'   => $ticket_id,
		) );

		update_post_meta( $ticket_id, $this->global_id_key, $global_id );

		return $global_id;
	}

	/**
	 * Returns a ticket Global ID lineage.
	 *
	 * If not set/updated for the attendee than the method will generate/update it.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id
	 * @param string $global_id
	 *
	 * @return array|bool
	 */
	public function get_ticket_global_id_lineage( $ticket_id, $global_id = null ) {
		if ( null === $global_id ) {
			$global_id = $this->get_ticket_global_id( $ticket_id );

			if ( false === $global_id ) {
				return false;
			}
		}

		$existing = get_post_meta( $ticket_id, $this->global_id_lineage_key, true );

		$new = ! empty( $existing )
			? array_unique( array_merge( (array) $existing, array( $global_id ) ) )
			: array( $global_id );

		if ( $new !== $existing ) {
			update_post_meta( $ticket_id, $this->global_id_lineage_key, $new );
		}

		return $new;
	}

	/**
	 * Adds the ticket post information to the data.
	 *
	 * @since TBD
	 *
	 * @param array $data
	 *
	 * @throws Tribe__REST__Exceptions__Exception If the post fetch or parsing fails.
	 */
	protected function add_ticket_post_data( &$data ) {
		$ticket_id   = $data['id'];
		$ticket_post = get_post( $ticket_id );

		if ( ! $ticket_post instanceof WP_Post ) {
			throw new Tribe__REST__Exceptions__Exception(
				$this->messages->get_message( 'error-ticket-post' ),
				'error-ticket-post',
				500
			);
		}

		/** @var Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		$data['author']       = $ticket_post->post_author;
		$data['status']       = $ticket_post->post_status;
		$data['date']         = $ticket_post->post_date;
		$data['date_utc']     = $ticket_post->post_date_gmt;
		$data['modified']     = $ticket_post->post_modified;
		$data['modified_utc'] = $ticket_post->post_modified_gmt;
		$data['title']        = $ticket_post->post_title;
		$data['description']  = $ticket_post->post_content;

	}

	/**
	 * Adds the meta information to the ticket data.
	 *
	 * @since TBD
	 *
	 * @param array $data
	 */
	protected function add_ticket_meta_data( &$data ) {
		$ticket_id = $data['id'];

		$data['image']                   = $this->get_ticket_header_image( $ticket_id );
		$data['available_from']          = $this->get_ticket_start_date( $ticket_id );
		$data['available_from_details']  = $this->get_ticket_start_date( $ticket_id, true );
		$data['available_until']         = $this->get_ticket_end_date( $ticket_id );
		$data['available_until_details'] = $this->get_ticket_end_date( $ticket_id, true );
		$data['capacity']                = $this->get_ticket_capacity( $ticket_id );
		$data['capacity_details']        = $this->get_ticket_capacity( $ticket_id, true );
		$data['is_available']            = $data['capacity_details']['available_percentage'] > 0;
		$data['cost']                    = $this->get_ticket_cost( $ticket_id );
		$data['cost_details'] = $this->get_ticket_cost( $ticket_id, true );

		/**
		 * Since Attendee Information is a functionality provided by Event Tickets Plus
		 * we rely on Event Ticket Plus to filter the data to add attendee information
		 * to it.
		 */
		$data['supports_attendee_information'] = false;
	}

	/**
	 * Returns a ticket header image information if set.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id
	 *
	 * @return bool|array
	 */
	public function get_ticket_header_image( $ticket_id ) {
		$post = tribe_events_get_ticket_event( $ticket_id );

		if ( empty( $post ) ) {
			return false;
		}

		/** @var Tribe__Tickets__Tickets_Handler $handler */
		$handler  = tribe( 'tickets.handler' );
		$image_id = (int) get_post_meta( $post->ID, $handler->key_image_header, true );

		if ( empty( $image_id ) ) {
			return false;
		}

		$data = $this->get_image_data( $image_id );

		/**
		 * Filters the data that will returned for a ticket header image if set.
		 *
		 * @param array   $data      The ticket header image array representation.
		 * @param WP_Post $ticket_id The requested ticket.
		 * @param WP_Post $post      The post this ticket is related to.
		 */
		return apply_filters( 'tribe_rest_event_featured_image', $data, $ticket_id, $post );
	}

	/**
	 * Returns a ticket start date.
	 *
	 * @since TBD
	 *
	 * @param int  $ticket_id
	 * @param bool $get_details Whether to get the date in string format (`false`) or the full details (`true`).
	 *
	 * @return string|array
	 */
	public function get_ticket_start_date( $ticket_id, $get_details = false ) {
		/** @var Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		$start_date = get_post_meta( $ticket_id, $handler->key_start_date, true );

		return $get_details
			? $this->get_date_details( $start_date )
			: $start_date;
	}

	/**
	 * Returns a ticket end date.
	 *
	 * @since TBD
	 *
	 * @param int  $ticket_id
	 * @param bool $get_details Whether to get the date in string format (`false`) or the full details (`true`).
	 *
	 * @return string|array
	 */
	public function get_ticket_end_date( $ticket_id, $get_details = false ) {
		/** @var Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		$end_date = get_post_meta( $ticket_id, $handler->key_end_date, true );

		return $get_details
			? $this->get_date_details( $end_date )
			: $end_date;
	}

	/**
	 * Returns a ticket capacity or capacity details.
	 *
	 * @since TBD
	 *
	 * @param int  $ticket_id
	 * @param bool $get_details
	 *
	 * @return array|bool|int The ticket capacity, the details if `$get_details` is set to `true`
	 *                        or `false` on failure.
	 */
	public function get_ticket_capacity( $ticket_id, $get_details = false ) {
		$ticket = $this->get_ticket_object( $ticket_id );

		if ( $ticket instanceof WP_Error ) {
			return false;
		}

		$capacity = $ticket->capacity();

		if ( ! $get_details ) {
			return $capacity;
		}

		/**
		 * Here we use the `Tribe__Tickets__Ticket_Object::stock()` method in
		 * place of the `Tribe__Tickets__Ticket_Object::available()` one to make
		 * sure we get the value that users would see on the front-end in the
		 * ticket form.
		 */
		$available = $ticket->stock();

		$unlimited = - 1 === $available;
		if ( $unlimited ) {
			$available_percentage = 100;
		} else {
			$available_percentage = $capacity <= 0 || $available == 0 ? 0 : (int) floor( $available / $capacity * 100 );
		}

		// @todo here we need to uniform the return values to indicate unlimited and oversold!

		return array(
			'available_percentage' => $available_percentage,
			'max'                  => (int) $ticket->capacity(),
			'available'            => (int) $ticket->stock(), // see not above about why we use this
			'sold'                 => (int) $ticket->qty_sold(),
			'pending'              => (int) $ticket->qty_pending(),
		);
	}

	/**
	 * Returns a ticket cost or details.
	 *
	 * @since TBD
	 *
	 * @param int  $ticket_id
	 * @param bool $get_details Whether to get just the ticket cost (`false`) or
	 *                          the details too ('true').
	 *
	 * @return string|array|false The ticket formatted cost if `$get_details` is `false`, the
	 *                            ticket cost details otherwise; `false` on failure.
	 *
	 */
	public function get_ticket_cost( $ticket_id, $get_details = false ) {
		$ticket = $this->get_ticket_object( $ticket_id );

		if ( $ticket instanceof WP_Error ) {
			return false;
		}


		/** @var Tribe__Tickets__Commerce__Currency $currency */
		$currency = tribe( 'tickets.commerce.currency' );

		$price = $ticket->price;

		if ( ! is_numeric( $price ) ) {
			$price = 0; // free
		}

		$formatted_price = html_entity_decode( $currency->format_currency( $price, $ticket_id ) );

		if ( ! $get_details ) {
			return $formatted_price;
		}

		$details = array(
			'currency_symbol'   => html_entity_decode( $currency->get_currency_symbol( $ticket_id ) ),
			'currency_position' => $currency->get_currency_symbol_position( $ticket_id ),
			'values'            => array( $price ),
		);

		return $details;
	}

	/**
	 * Adds the attendees information to the ticket.
	 *
	 * @since TBD
	 *
	 * @param array $data
	 */
	protected function add_ticket_attendees_data( array &$data ) {
		$ticket_id = $data['id'];

		$data['attendees'] = $this->get_ticket_attendees( $ticket_id );

		$ticket_object = $this->get_ticket_object( $ticket_id );

		if (
			$ticket_object instanceof Tribe__Tickets__Ticket_Object
			&& $ticket_object->provider_class === 'Tribe__Tickets__RSVP'
			&& false !== $data['attendees']
		) {
			$going     = 0;
			$not_going = 0;

			foreach ( $data['attendees'] as $attendee ) {
				if ( true === $attendee['rsvp_going'] ) {
					$going ++;
				} else {
					$not_going ++;
				}
			}

			$data['rsvp'] = array(
				'rsvp_going'     => $going,
				'rsvp_not_going' => $not_going,
			);
		}
	}

	/**
	 * Returns a ticket attendees list.
	 *
	 * @param int $ticket_id
	 *
	 * @return array|bool An array of ticket attendees or `false` on failure.
	 */
	public function get_ticket_attendees( $ticket_id ) {
		$ticket_object = $this->get_ticket_object( $ticket_id );

		if ( ! $ticket_object instanceof Tribe__Tickets__Ticket_Object ) {
			return false;
		}

		$attendees = $this->current_ticket_provider->get_attendees_by_id( $ticket_id );
		$post      = $this->current_ticket_provider->get_event_for_ticket( $ticket_id );

		if ( empty( $attendees ) || ! $post instanceof WP_Post ) {
			return array();
		}

		$ticket_attendees = array();

		foreach ( $attendees as $attendee ) {
			$ticket_attendees[] = $this->build_attendee_data( $attendee );
		}

		return $ticket_attendees;
	}

	/**
	 * Returns an attendee Global ID.jA
	 *
	 * If not set/updated for the attendee than the method will generate/update it.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id
	 *
	 * @return string
	 */
	public function get_attendee_global_id( $attendee_id ) {
		$existing = get_post_meta( $attendee_id, $this->global_id_key, true );

		if ( ! empty( $existing ) ) {
			return $existing;
		}

		$generator = new Tribe__Tickets__Global_ID();
		$generator->origin( home_url() );
		$generator->type( 'attendee' );

		$global_id = $generator->generate( array(
			'type' => 'attendee',
			'id'   => $attendee_id,
		) );

		update_post_meta( $attendee_id, $this->global_id_key, $global_id );

		return $global_id;
	}

	/**
	 * Returns an attendee Global ID lineage.
	 *
	 * If not set/updated for the attendee than the method will generate/update it.
	 *
	 * @since TBD
	 *
	 * @param int    $attendee_id
	 * @param string $global_id
	 *
	 * @return array|bool The attendee Global ID lineage or `false` on failure.
	 */
	public function get_attendee_global_id_lineage( $attendee_id, $global_id = null ) {
		if ( null === $global_id ) {
			$global_id = $this->get_attendee_global_id( $attendee_id );
		}

		$existing = get_post_meta( $attendee_id, $this->global_id_lineage_key, true );

		$new = ! empty( $existing )
			? array_unique( array_merge( (array) $existing, array( $global_id ) ) )
			: array( $global_id );

		if ( $new !== $existing ) {
			update_post_meta( $attendee_id, $this->global_id_lineage_key, $new );
		}

		return $new;
	}

	/**
	 * Adds REST API related information to the returned data.
	 *
	 * @since TBD
	 *
	 * @param array $data
	 */
	protected function add_ticket_rest_data( &$data ) {
		/** @var Tribe__Tickets__REST__V1__Main $main */
		$main = tribe( 'tickets.rest-v1.main' );

		$data['rest_url'] = $main->get_url( '/tickets/' . $data['id'] );
	}

	/**
	 * Removes fields from the ticket data.
	 *
	 * @since TBD
	 *
	 * @param array $data
	 */
	protected function clean_ticket_data( array &$data ) {
		$unset_map = array(
			'name',
			'show_description',
			'price',
			'regular_price',
			'on_sale',
			'admin_link',
			'report_link',
			'frontend_link',
			'provider_class',
			'menu_order',
			'start_date',
			'start_time',
			'end_date',
			'end_time',
			'purchase_limit',
			'sku',
		);

		$data = array_diff_key( $data, array_combine( $unset_map, $unset_map ) );
	}

	/**
	 * Builds an attendee data from the attendee information.
	 *
	 * @since TBD
	 *
	 * @param array $attendee The attendee information.
	 *
	 * @return array
	 */
	protected function build_attendee_data( array $attendee ) {
		$attendee_id = $attendee['attendee_id'];
		/** @var Tribe__Tickets__Data_API $data_api */
		$data_api = tribe( 'tickets.data_api' );
		/** @var Tribe__Tickets__Tickets $provider */
		$provider = $data_api->get_ticket_provider( $attendee_id );
		/** @var Tribe__Tickets__REST__V1__Main $main */
		$main = tribe( 'tickets.rest-v1.main' );

		$attendee_post = get_post( $attendee_id );

		$checked_in      = (bool) $attendee['check_in'];
		$checkin_details = false;
		if ( $checked_in ) {
			$checkin_details = get_post_meta( $attendee_id, $this->current_ticket_provider->checkin_key . '_details', true );
			if ( isset( $checkin_details['date'], $checkin_details['source'], $checkin_details['author'] ) ) {
				$checkin_details = array(
					'date'         => $checkin_details['date'],
					'date_details' => $this->get_date_details( $checkin_details['date'] ),
					'source'       => $checkin_details['source'],
					'author'       => $checkin_details['author'],
				);
			} else {
				$checkin_details = false;
			}
		}

		$attendee_order_id = $this->get_attendee_order_id( $attendee_id, $provider );

		$attendee_data     = array(
			'id'                => $attendee_id,
			'post_id'           => (int) $attendee['event_id'],
			'ticket_id'         => (int) $attendee['product_id'],
			'global_id'         => $this->get_attendee_global_id( $attendee_id ),
			'global_id_lineage' => $this->get_attendee_global_id_lineage( $attendee_id ),
			'author'            => $attendee_post->post_author,
			'status'            => $attendee_post->post_status,
			'date'              => $attendee_post->post_date,
			'date_utc'          => $attendee_post->post_date_gmt,
			'modified'          => $attendee_post->post_modified,
			'modified_utc'      => $attendee_post->post_modified_gmt,
			'rest_url'          => $main->get_url( '/attendees/' . $attendee_id ),
			'provider'          => $this->get_provider_slug( $provider ),
			'order'             => $attendee_order_id,
			'sku'               => $this->get_attendee_sku( $attendee_id, $attendee_order_id, $provider ),
			'title'             => Tribe__Utils__Array::get( $attendee, 'holder_name', Tribe__Utils__Array::get( $attendee, 'purchaser_name', '' ) ),
			'email'             => Tribe__Utils__Array::get( $attendee, 'holder_email', Tribe__Utils__Array::get( $attendee, 'purchaser_email', '' ) ),
			'checked_id'        => $checked_in,
			'checkin_details'   => $checkin_details,
		);

		if ( $provider instanceof Tribe__Tickets__RSVP ) {
			$attendee_data['rsvp_going'] = tribe_is_truthy( $attendee['order_status'] );
		} else {
			$order_id = $attendee['order_id'];
			$order_data = method_exists( $provider, 'get_order_data' )
				? $provider->get_order_data( $order_id )
				: false;

			if ( ! empty( $order_data ) ) {
				/** @var Tribe__Tickets__Commerce__Currency $currency */
				$currency                 = tribe('tickets.commerce.currency');
				$ticket_object            = $this->get_ticket_object( $attendee['product_id'] );
				$purchase_time = Tribe__Utils__Array::get( $order_data, 'purchase_time', get_post_time( 'Y-m-d H:i:s', false, $attendee_id ) );
				$attendee_data['payment'] = array(
					'provider'     => Tribe__Utils__Array::get( $order_data, 'provider_slug', $this->get_provider_slug( $provider ) ),
					'price'        => $ticket_object->price,
					'currency'     => html_entity_decode( $currency->get_currency_symbol( $attendee['product_id'] ) ),
					'date'         => $purchase_time,
					'date_details' => $this->get_date_details( $purchase_time ),
				);
			}
		}

		/**
		 * Filters the single attendee data.
		 *
		 * @since TBD
		 *
		 * @param array $attendee_data
		 */
		$attendee_data = apply_filters( 'tribe_tickets_rest_api_attendee_data', $attendee_data );

		return $attendee_data;
	}

	/**
	 * Retrieves the ID of the Order associated with an attendee depending on the provider.
	 *
	 * @since TBD
	 *
	 * @param int                     $attendee_id
	 * @param Tribe__Tickets__Tickets $provider
	 *
	 * @return int|mixed
	 *
	 * @throws ReflectionException
	 */
	protected function get_attendee_order_id( $attendee_id, Tribe__Tickets__Tickets $provider ) {
		if ( $attendee_id instanceof WP_Post ) {
			$attendee_id = $attendee_id->ID;
		}

		// the order is the the attendee ID itself for RSVP orders
		if ( $provider instanceof Tribe__Tickets__RSVP ) {
			return (int) $attendee_id;
		}

		$key = '';
		if ( ! empty( $provider->attendee_order_key ) ) {
			$key = $provider->attendee_order_key;
		} else {
			$reflection = new ReflectionClass( $provider );
			$key        = $reflection->getConstant( 'ATTENDEE_ORDER_KEY' );
		}

		return get_post_meta( $attendee_id, $key, true );
	}

	/**
	 * Retrieves an Attendee ticket SKU.
	 *
	 * @since TBD
	 *
	 *
	 * @param                         int $attendee_id
	 * @param                         int $order_id
	 * @param Tribe__Tickets__Tickets     $provider
	 *
	 * @return string
	 */
	protected function get_attendee_sku( $attendee_id, $order_id, Tribe__Tickets__Tickets $provider ) {
		$sku = get_post_meta( $attendee_id, '_sku', true );

		if ( ! empty( $sku ) ) {
			return $sku;
		}

		if ( $provider instanceof Tribe__Tickets_Plus__Commerce__WooCommerce__Main ) {
			$sku = get_post_meta( $order_id, '_sku', true );
		}

		return $sku;
	}
}
