<?php

use TEC\Tickets\Settings;

/**
 * Checks whether out new Tickets system should load.
 *
 * In order the function will check the `TEC_TICKETS_COMMERCE` constant,
 * the `TEC_TICKETS_COMMERCE` environment variable and, finally, the `Manager::$option_enabled` option.
 *
 * @since 5.1.6
 *
 * @return bool Whether Tickets Commerce is enabled or not.
 */
function tec_tickets_commerce_is_enabled() {
	if ( defined( 'TEC_TICKETS_COMMERCE' ) ) {
		return (bool) TEC_TICKETS_COMMERCE;
	}

	$env_var = getenv( 'TEC_TICKETS_COMMERCE' );
	if ( false !== $env_var ) {
		return (bool) $env_var;
	}

	$enabled = (bool) tribe_get_option( Settings::$tickets_commerce_enabled, false );

	/**
	 * Allows filtering of the Tickets Commerce provider, doing so will render
	 * the methods and classes no longer load-able so keep that in mind.
	 *
     * @since 5.1.6
	 *
	 * @param boolean $enabled Determining if Tickets Commerce is enabled..
	 */
	return apply_filters( 'tec_tickets_commerce_is_enabled', $enabled );
}


/**
 * Determine whether Tickets Commerce is in test mode.
 *
 * @since 5.1.6
 *
 * @return bool Whether Tickets Commerce is in test mode.
 */
function tribe_tickets_commerce_is_test_mode() {
	/**
	 * @todo This method likely should be focused on paypal only usage as it's gateway specific conditional.
	 */
	return \TEC\Tickets\Commerce\Gateways\PayPal\Gateway::is_test_mode();
}

/**
 * Determine whether the legacy TribeCommerce should be shown or not.
 *
 * @since TBD
 *
 * @return boolean
 */
function tec_tribe_commerce_is_available() {

	if ( defined( 'TEC_TRIBE_COMMERCE_AVAILABLE' ) ) {
		return (bool) TEC_TRIBE_COMMERCE_AVAILABLE;
	}

	$env_var = getenv( 'TEC_TRIBE_COMMERCE_AVAILABLE' );

	if ( false !== $env_var ) {
		return (bool) $env_var;
	}

	// Todo, @juanfra @rafsuntaskin decide the proper version.
	$active = tribe_installed_before( 'Tribe__Tickets__Main', '5.1' );

	/**
	 * Filter whether we should disable TribeCommerce PayPal or not.
	 *
	 * @since TBD
	 *
	 * @param boolean $available should be available or not.
	 */
	return apply_filters( 'tec_tribe_commerce_is_available', $available );
}