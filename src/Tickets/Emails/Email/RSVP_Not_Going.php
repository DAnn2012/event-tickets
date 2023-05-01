<?php
/**
 * Class RSVP_Not_Going
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Email;

use TEC\Tickets\Emails\Dispatcher;
use TEC\Tickets\Emails\Email_Template;
use TEC\Tickets\Emails\Email_Abstract;

/**
 * Class RSVP_Not_Going
 *
 * @since   5.5.10
 *
 * @package TEC\Tickets\Emails
 */
class RSVP_Not_Going extends Email_Abstract {

	/**
	 * Email ID.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public static string $id = 'tec_tickets_emails_rsvp_not_going';

	/**
	 * Email slug.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public static string $slug = 'rsvp-not-going';

	/**
	 * Email template.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $template = 'rsvp-not-going';

	/**
	 * @inheritDoc
	 */
	public function get_default_data(): array {
		$data = [
			'to'    => esc_html__( 'Attendee(s)', 'event-tickets' ),
			'title' => esc_html__( 'RSVP "Not Going" Email', 'event-tickets' ),
		];

		return array_merge( parent::get_default_data(), $data );
	}

	/**
	 * @inheritDoc
	 */
	public function prepare_settings(): array {
		$default_heading = esc_html__( 'You confirmed you will not be attending', 'event-tickets' );
		$default_subject = esc_html__( 'You confirmed you will not be attending', 'event-tickets' );

		$settings        = [
			[
				'type' => 'html',
				'html' => '<div class="tribe-settings-form-wrap">',
			],
			[
				'type' => 'html',
				'html' => '<h2>' . esc_html__( 'RSVP "Not Going" Email Settings', 'event-tickets' ) . '</h2>',
			],
			[
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'Registrants will receive an email confirming that they will not be attending. Customize the content of this specific email using the tools below. The brackets {event_name}, {event_date}, and {rsvp_name} can be used to pull dynamic content from the RSVP into your email. Learn more about customizing email templates in our Knowledgebase.' ) . '</p>',
			],
			'enabled'            => [
				'type'            => 'toggle',
				'label'           => esc_html__( 'Enabled', 'event-tickets' ),
				'default'         => true,
				'validation_type' => 'boolean',
			],
			'subject'            => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject', 'event-tickets' ),
				'default'             => $default_subject,
				'placeholder'         => $default_subject,
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			'heading'            => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading', 'event-tickets' ),
				'default'             => $default_heading,
				'placeholder'         => $default_heading,
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			'additional_content' => [
				'type'            => 'wysiwyg',
				'label'           => esc_html__( 'Additional content', 'event-tickets' ),
				'default'         => '',
				'tooltip'         => esc_html__( 'Additional content will be displayed below the information in your email.', 'event-tickets' ),
				'validation_type' => 'html',
				'settings'        => [
					'media_buttons' => false,
					'quicktags'     => false,
					'editor_height' => 200,
					'buttons'       => [
						'bold',
						'italic',
						'underline',
						'strikethrough',
						'alignleft',
						'aligncenter',
						'alignright',
					],
				],
			],
		];

		return $settings;
	}

	/**
	 * Get default preview context for email.
	 *
	 * @since 5.5.11
	 *
	 * @param array $args The arguments.
	 *
	 * @return array $args The modified arguments
	 */
	public function get_default_preview_context( $args = [] ): array {
		$defaults = [
			'email'              => $this,
			'is_preview'         => true,
			'title'              => $this->get( 'heading' ),
			'heading'            => $this->get( 'heading' ),
			'additional_content' => $this->get( 'additional_content' ),
		];

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Get default template context for email.
	 *
	 * @since 5.5.11
	 *
	 * @return array $args The default arguments
	 */
	public function get_default_template_context(): array {
		$defaults = [
			'email'              => $this,
			'title'              => $this->get( 'title' ),
			'heading'            => $this->get( 'heading' ),
			'additional_content' => $this->get( 'additional_content' ),
		];

		return $defaults;
	}

	/**
	 * Get email content.
	 *
	 * @since 5.5.10
	 *
	 * @param array $args The arguments.
	 *
	 * @return string The email content.
	 */
	public function get_content( $args = [] ): string {
		// @todo: Parse args, etc.
		$is_preview = ! empty( $args['is_preview'] ) ? tribe_is_truthy( $args['is_preview'] ) : false;
		$args       = $this->get_template_context( $args );

		$email_template = tribe( Email_Template::class );
		$email_template->set_preview( $is_preview );

		return $email_template->get_html( $this->template, $args );
	}

	/**
	 * Send the email.
	 *
	 * @since 5.5.11
	 *
	 * @return bool Whether the email was sent or not.
	 */
	public function send() {
		$recipient = $this->get( 'recipient' );

		// Bail if there is no email address to send to.
		if ( empty( $recipient ) ) {
			return false;
		}

		if ( ! $this->is_enabled() ) {
			return false;
		}

		$tickets = $this->get( 'tickets' );
		$post_id = $this->get( 'post_id' );

		// Bail if there's no tickets or post ID.
		if ( empty( $tickets ) || empty( $post_id ) ) {
			return false;
		}

		$placeholders = [
			'{attendee_name}'  => $tickets[0]['holder_name'],
			'{attendee_email}' => $tickets[0]['holder_email'],
		];

		$this->set_placeholders( $placeholders );

		return Dispatcher::from_email( $this )->send();
	}
}
