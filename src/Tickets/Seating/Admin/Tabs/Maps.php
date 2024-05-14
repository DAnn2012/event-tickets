<?php
/**
 * The tab used to display the current site Maps.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin\Tabs;
 */

namespace TEC\Tickets\Seating\Admin\Tabs;

use TEC\Tickets\Seating\Admin;
use TEC\Tickets\Seating\Service\Service;

/**
 * Class Maps.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin\Tabs;
 */
class Maps extends Tab {
	/**
	 * Returns the title of this tab. The one that will be displayed on the top of the page.
	 *
	 * @since TBD
	 *
	 * @return string The title of this tab.
	 */
	public function get_title(): string {
		return _x( 'Seating Maps', 'Tab title', 'event-tickets' );
	}

	/**
	 * Returns the ID of this tab, used in the URL and CSS/JS attributes.
	 *
	 * @since TBD
	 *
	 * @return string The CSS/JS id of this tab.
	 */
	public static function get_id(): string {
		return 'maps';
	}

	/**
	 * Renders the tab.
	 *
	 * @since TBD
	 *
	 * @return void The rendered HTML of this tab is passed to the output buffer.
	 */
	public function render(): void {
		$service = tribe( Service::class );
		$context = [
			'cards'       => $service->get_map_cards(),
			'add_new_url' => add_query_arg(
				[
					'page' => Admin::get_menu_slug(),
					'tab'  => Map_Edit::get_id(),
				] 
			),
		];

		$this->template->template( 'tabs/maps', $context );
	}
}
