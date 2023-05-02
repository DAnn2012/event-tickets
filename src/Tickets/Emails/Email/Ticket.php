<?php
/**
 * Class Ticket
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Email;

use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Emails\Dispatcher;
use TEC\Tickets\Emails\Email_Template;
use TEC\Tickets\Emails\Email_Abstract;
use TEC\Tickets\Emails\Admin\Preview_Data;

/**
 * Class Ticket
 *
 * @since   5.5.9
 *
 * @package TEC\Tickets\Emails
 */
class Ticket extends Email_Abstract {

	/**
	 * Email ID.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	protected static string $id = 'tec_tickets_emails_ticket';

	/**
	 * Email slug.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	protected static string $slug = 'ticket';

	/**
	 * Email template.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public $template = 'ticket';

	/**
	 * @inheritDoc
	 */
	public function get_default_data(): array {
		$data = [
			'to' => esc_html__( 'Attendee(s)', 'event-tickets' ),
			'title' => esc_html__( 'Ticket Email', 'event-tickets' ),
		];

		return array_merge( parent::get_default_data(), $data );
	}

	/**
	 * @inheritDoc
	 */
	public function prepare_settings(): array {
		$default_subject = sprintf(
			// Translators: %s - Lowercase singular of tickets.
			esc_html__( 'Your %s from {site_title}', 'event-tickets' ),
			tribe_get_ticket_label_singular_lowercase()
		);

		// If they already had a subject set in Tickets Commerce, let's make it the default.
		$default_subject = tribe_get_option( Settings::$option_confirmation_email_subject, $default_subject );

		$default_subject_plural = sprintf(
			// Translators: %s - Lowercase plural of tickets.
			esc_html__( 'Your %s from {site_title}', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase()
		);

		$default_heading = sprintf(
			// Translators: %s Lowercase singular of ticket.
			esc_html__( 'Here\'s your %s, {attendee_name}!', 'event-tickets' ),
			tribe_get_ticket_label_singular_lowercase()
		);

		$default_heading_plural = sprintf(
			// Translators: %s Lowercase plural of tickets.
			esc_html__( 'Here are your %s, {attendee_name}!', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase()
		);

		return [
			[
				'type' => 'html',
				'html' => '<div class="tribe-settings-form-wrap">',
			],
			[
				'type' => 'html',
				'html' => '<h2>' . esc_html__( 'Ticket Email Settings', 'event-tickets' ) . '</h2>',
			],
			[
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'Ticket purchasers will receive an email including their ticket and additional info upon completion of purchase. Customize the content of this specific email using the tools below. The brackets {event_name}, {event_date}, and {ticket_name} can be used to pull dynamic content from the ticket into your email. Learn more about customizing email templates in our Knowledgebase.' ) . '</p>',
			],
			'enabled'        => [
				'type'            => 'toggle',
				'label'           => esc_html__( 'Enabled', 'event-tickets' ),
				'default'         => true,
				'validation_type' => 'boolean',
			],
			'subject'        => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject', 'event-tickets' ),
				'default'             => $default_subject,
				'placeholder'         => $default_subject,
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			'subject_plural' => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject (plural)', 'event-tickets' ),
				'default'             => $default_subject_plural,
				'placeholder'         => $default_subject_plural,
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			'heading'        => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading', 'event-tickets' ),
				'default'             => $default_heading,
				'placeholder'         => $default_heading,
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			'heading_plural' => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading (plural)', 'event-tickets' ),
				'default'             => $default_heading_plural,
				'placeholder'         => $default_heading_plural,
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			'additional_content'    => [
				'type'            => 'wysiwyg',
				'label'           => esc_html__( 'Additional content', 'event-tickets' ),
				'default'         => '',
				'tooltip'         => esc_html__( 'Additional content will be displayed below the tickets in your email.', 'event-tickets' ),
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
		$defaults = tribe( Email_Template::class )->get_preview_context( $args );

		$args['order'] = Preview_Data::get_order();
		$args['tickets'] = Preview_Data::get_tickets();
		$args['heading'] = $this->get_heading();

		// If more than one ticket, use plural heading.
		if ( count( $args['tickets'] ) > 1 ) {
			$args['heading'] = $this->get_heading_plural();
		}

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
			'post_id'            => $this->get( 'post_id' ),
			'tickets'            => $this->get( 'tickets' ),
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

		if ( ! empty( $tickets[0]['purchaser_name'] ) ) {
			$placeholders['{purchaser_name}'] = $tickets[0]['purchaser_name'];
		}

		if ( ! empty( $tickets[0]['purchaser_email'] ) ) {
			$placeholders['{purchaser_email}'] = $tickets[0]['purchaser_email'];
		}

		$this->set_placeholders( $placeholders );

		return Dispatcher::from_email( $this )->send();
	}
}
