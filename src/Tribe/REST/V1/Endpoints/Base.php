<?php


abstract class Tribe__Tickets__REST__V1__Endpoints__Base {

	/**
	 * @var Tribe__REST__Messages_Interface
	 */
	protected $messages;

	/**
	 * @var array
	 */
	protected $supported_query_vars = array();

	/**
	 * @var Tribe__Tickets__REST__Interfaces__Post_Repository
	 */
	protected $post_repository;

	/**
	 * @var Tribe__Tickets__REST__V1__Validator__Interface
	 */
	protected $validator;

	/**
	 * @var array An array of default query args to customize the tickets query.
	 */
	protected $ticket_query_args = array(
		/**
		 * By default tickets would show in ASC `menu_order` order.
		 * We drop this UI-related order to use a consistent one.
		 */
		'orderby' => array( 'date', 'ID' ),
		'order'   => 'ASC',
	);

	/**
	 * @var int A property to keep track of the tickets found during ticket queries.
	 */
	protected $found_tickets = 0;

	/**
	 * Tribe__Tickets__REST__V1__Endpoints__Base constructor.
	 *
	 * @since 4.7.5
	 *
	 * @param Tribe__REST__Messages_Interface                   $messages
	 * @param Tribe__Tickets__REST__Interfaces__Post_Repository $post_repository
	 * @param Tribe__Tickets__REST__V1__Validator__Interface    $validator
	 */
	public function __construct(
		Tribe__REST__Messages_Interface $messages,
		Tribe__Tickets__REST__Interfaces__Post_Repository $post_repository,
		Tribe__Tickets__REST__V1__Validator__Interface $validator
	) {
		$this->messages        = $messages;
		$this->post_repository = $post_repository;
		$this->validator       = $validator;
	}

	/**
	 * Converts an array of arguments suitable for the WP REST API to the Swagger format.
	 *
	 * @since 4.7.5
	 *
	 * @param array $args
	 * @param array $defaults
	 *
	 * @return array The converted arguments.
	 */
	public function swaggerize_args( array $args = array(), array $defaults = array() ) {
		if ( empty( $args ) ) {
			return $args;
		}

		$no_description = __( 'No description provided', 'event-tickets' );
		$defaults = array_merge( array(
			'in'          => 'body',
			'schema'      => array(
				'type'    => 'string',
				'default' => '',
			),
			'description' => $no_description,
			'required'    => false,
			'items'       => array(
				'type' => 'integer',
			),
		), $defaults );


		$swaggerized = array();
		foreach ( $args as $name => $info ) {
			if ( isset( $info['swagger_type'] ) ) {
				$type = $info['swagger_type'];
			} else {
				$type = isset( $info['type'] ) ? $info['type'] : false;
			}

			$type = $this->convert_type( $type );

			$read = array(
				'name'             => $name,
				'description'      => isset( $info['description'] ) ? $info['description'] : false,
				'in'               => isset( $info['in'] ) ? $info['in'] : false,
				'collectionFormat' => isset( $info['collectionFormat'] ) ? $info['collectionFormat'] : false,
				'schema'           => array(
					'type'    => $type,
					'default' => isset( $info['default'] ) ? $info['default'] : false,
				),
				'items'            => isset( $info['items'] ) ? $info['items'] : false,
				'required'         => isset( $info['required'] ) ? $info['required'] : false,
			);

			if ( isset( $info['swagger_type'] ) ) {
				$read['schema']['type'] = $info['swagger_type'];
			}

			if ( $read['schema']['type'] !== 'array' ) {
				unset( $defaults['items'] );
			}

			$merged = array_merge( $defaults, array_filter( $read ) );

			unset( $merged['type'], $merged['default'] );

			$swaggerized[] = $merged;
		}

		return $swaggerized;
	}

	/**
	 * Falls back on an allowed post status in respect to the user user capabilities of publishing.
	 *
	 * @since 4.7.5
	 *
	 * @param string $post_status
	 * @param string $post_type
	 *
	 * @return string
	 */
	public function scale_back_post_status( $post_status, $post_type ) {
		$post_type_object = get_post_type_object( $post_type );

		if ( current_user_can( $post_type_object->cap->publish_posts ) ) {
			return ! empty( $post_status ) ? $post_status : 'publish';
		}
		if ( in_array( $post_status, array( 'publish', 'future' ) ) ) {
			return 'pending';
		}

		return ! empty( $post_status ) ? $post_status : 'draft';
	}

	/**
	 * Filters the query arguments that will be used to fetch the tickets to allow any
	 * ticket post status if the user can edit the ticket post type and set a default
	 * order.
	 *
	 * @since TBD
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function filter_tickets_query_args( array $args = [] ) {
		if ( empty( $args['post_type'] ) || count( (array) $args['post_type'] ) > 1 ) {
			return $args;
		}

		$post_types = (array) $args['post_type'];

		$ticket_post_type_object = get_post_type_object( $post_types[0] );
		$edit_posts              = $ticket_post_type_object->cap->edit_posts;

		if ( current_user_can( $edit_posts ) ) {
			$args['post_status'] = 'any';
		}

		$args = array_merge( $args, $this->ticket_query_args );

		$query               = new WP_Query( $args );
		$this->found_tickets += (int) $query->found_posts;

		// let's avoid filtering the same args again
		remove_filter( 'tribe_tickets_get_tickets_query_args', array( $this, 'filter_tickets_query_args' ), 10 );

		return $args;
	}

	/**
	 * Returns the default value of posts per page.
	 *
	 * Cascading fallback is TEC `posts_per_page` option, `posts_per_page` option and, finally, 20.
	 *
	 * @since 4.7.5
	 *
	 * @return int
	 */
	protected function get_default_posts_per_page() {
		$posts_per_page = tribe_get_option( 'posts_per_page', get_option( 'posts_per_page' ) );

		return ! empty( $posts_per_page ) ? $posts_per_page : 20;
	}

	/**
	 * Modifies a request argument marking it as not required.
	 *
	 * @since 4.7.5
	 *
	 * @param array $arg
	 */
	protected function unrequire_arg( array &$arg ) {
		$arg['required'] = false;
	}

	/**
	 * Parses the arguments populated parsing the request filling out with the defaults.
	 *
	 * @since 4.7.5
	 *
	 * @param array $args
	 * @param array $defaults
	 *
	 * @return array
	 */
	protected function parse_args( array $args, array $defaults ) {
		foreach ( $this->supported_query_vars as $request_key => $query_var ) {
			if ( isset( $defaults[ $request_key ] ) ) {
				$defaults[ $query_var ] = $defaults[ $request_key ];
			}
		}

		$args = wp_parse_args( array_filter( $args, array( $this, 'is_not_null' ) ), $defaults );

		return $args;
	}

	/**
	 * Whether a value is null or not.
	 *
	 * @since 4.7.5
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function is_not_null( $value ) {
		return null !== $value;
	}

	/**
	 * Converts REST format type argument to the correspondant Swagger.io definition.
	 *
	 * @since 4.7.5
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	protected function convert_type( $type ) {
		$rest_to_swagger_type_map = array(
			'int'  => 'integer',
			'bool' => 'boolean',
		);

		return Tribe__Utils__Array::get( $rest_to_swagger_type_map, $type, $type );
	}

	/**
	 * Returns the ticket data accessible to the current user.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id
	 *
	 * @return array|WP_Error An array of ticket data accessible by the current user or a `WP_Error` if the user
	 *                        cannot access the current ticket at all.
	 */
	protected function get_readable_ticket_data( $ticket_id ) {
		$ticket_post = get_post( $ticket_id );

		if ( ! $ticket_post instanceof WP_Post ) {
			return new WP_Error( 'ticket-not-found', $this->messages->get_message( 'ticket-not-found' ), array( 'status' => 404 ) );
		}

		$ticket_post_type_object = get_post_type_object( $ticket_post->post_type );
		$read_cap                = $ticket_post_type_object->cap->read_post;

		if ( ! ( 'publish' === $ticket_post->post_status || current_user_can( $read_cap, $ticket_id ) ) ) {
			$message = $this->messages->get_message( 'ticket-not-accessible' );

			return new WP_Error( 'ticket-not-accessible', $message, array( 'status' => 401 ) );
		}

		/**
		 * Not only the ticket should be accessible by the user but the event too should be.
		 */
		$event = tribe_events_get_ticket_event( $ticket_id );

		if ( ! $event instanceof WP_Post ) {
			$message = $this->messages->get_message( 'ticket-not-accessible' );

			return new WP_Error( 'ticket-not-accessible', $message, array( 'status' => 401 ) );
		}

		$event_post_type_object = get_post_type_object( $event->post_type );
		$read_cap               = $event_post_type_object->cap->read_post;

		if ( ! ( 'publish' === $event->post_status || current_user_can( $read_cap, $event->ID ) ) ) {
			$message = $this->messages->get_message( 'ticket-not-accessible' );

			return new WP_Error( 'ticket-not-accessible', $message, array( 'status' => 401 ) );
		}

		return $this->post_repository->get_ticket_data( $ticket_id );
	}

	/**
	 * Filtered method to get the tickets for a post.
	 *
	 * The method will filter the query arguments using the
	 * `tribe_tickets_get_tickets_query_args` filter.
	 *
	 * @uses
	 *
	 * @since TBD
	 *
	 * @param int  $post_id
	 *
	 * @return array|int An array of found tickets.
	 */
	protected function get_tickets_for_post( $post_id ) {
		$post_id = $post_id instanceof WP_Post ? $post_id->ID : $post_id;

		// this filter auto-removes itself
		add_filter( 'tribe_tickets_get_tickets_query_args', array( $this, 'filter_tickets_query_args' ) );
		$tickets = Tribe__Tickets__Tickets::get_event_tickets( $post_id );

		return $tickets;
	}
}