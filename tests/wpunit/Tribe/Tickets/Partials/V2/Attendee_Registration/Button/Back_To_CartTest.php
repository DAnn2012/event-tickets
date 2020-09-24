<?php

namespace Tribe\Tickets\Partials\V2\Attendee_Registration\Button;

use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe__Tickets__Editor__Template;

/**
 * Class Back_To_CartTest.
 * @package Tribe\Tickets\Partials\V2\Attendee_Registration\Button
 */
class Back_To_CartTest extends V2TestCase {

	/** @var string Relative path to V2 template file. */
	private $partial_path = 'v2/attendee-registration/button/back-to-cart';

	/**
	 * @test
	 */
	public function test_should_render_nothing_if_cart_url_is_empty() {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'cart_url'     => '',
			'checkout_url' => $this->base_url . 'checkout/?anything',
			'provider'     => 'any-provider',
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), $this->base_url );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_nothing_if_cart_and_checkout_url_match() {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'cart_url'     => $this->base_url . 'checkout/?anything',
			'checkout_url' => $this->base_url . 'checkout/?anything',
			'provider'     => 'any-provider',
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), $this->base_url );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_successfully() {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'cart_url'     => $this->base_url . 'cart/?anything',
			'checkout_url' => $this->base_url . 'checkout/?something-else',
			'provider'     => 'any-provider',
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), $this->base_url );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
