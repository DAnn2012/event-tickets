<?php
/**
 * Tickets Commerce: Checkout Page Must Login Registration link
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/must-login/registration.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template $this                  [Global] Template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var array[]          $items                 [Global] List of Items on the cart to be checked out.
 * @var string           $paypal_attribution_id [Global] What is our PayPal Attribution ID.
 * @var bool             $must_login            [Global] Whether login is required to buy tickets or not.
 * @var string           $login_url             [Global] The site's login URL.
 * @var string           $registration_url      [Global] The site's registration URL.
 */

// Bail if WordPress is not open to registering.
if ( empty( get_option( 'users_can_register' ) ) ) {
	return;
}

?>
<div class="tribe-common-b1 tribe-tickets__commerce-checkout-must-login-registration">
	<?php
	echo wp_kses_post(
		sprintf(
			// Translators: %1$s: Opening a tag for "create a new account" link; %2$s: Closing </a> tag for "create a new account" link.
			__( 'or %1$screate a new account%2$s', 'event-tickets' ),
			'<a class="tribe-common-cta tribe-common-cta--alt tribe-common-b2 tribe-tickets__commerce-checkout-must-login-registration-link">',
			'</a>'
		)
	);
	?>
</div>
