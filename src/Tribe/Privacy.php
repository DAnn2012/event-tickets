<?php

/**
 * Class Tribe__Tickets__Privacy
 */
class Tribe__Tickets__Privacy {

	/**
	 * Class initialization
	 *
	 * @since TBD
	 */
	public function hook() {
		add_action( 'admin_init', array( $this, 'privacy_policy_content' ), 20 );
	}

	/**
	 * Add the suggested privacy policy text to the policy postbox.
	 *
	 * @since TBD
	 */
	public function privacy_policy_content() {

		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			$content = $this->default_privacy_policy_content( true );
			wp_add_privacy_policy_content( __( 'Event Tickets', 'event-tickets' ), $content );
		}
	}

	/**
	 * Return the default suggested privacy policy content.
	 *
	 * @param bool $descr Whether to include the descriptions under the section headings. Default false.
	 *
	 * @since TBD
	 *
	 * @return string The default policy content.
	 */
	public function default_privacy_policy_content( $descr = false ) {

		ob_start();
		include_once Tribe__Tickets__Main::instance()->plugin_path . 'src/admin-views/privacy.php';
		$content = ob_get_clean();

		/**
		 * Filters the default content suggested for inclusion in a privacy policy.
		 *
		 * @since TBD
		 *
		 * @param $content string The default policy content.
		 */
		return apply_filters( 'tribe_tickets_default_privacy_policy_content', $content );

	}
}