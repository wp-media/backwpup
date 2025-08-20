<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Beacon;

use WPMedia\BackWPup\Common\AbstractRender;

class Beacon extends AbstractRender {

	/**
	 * Returns the link for corresponding section.
	 * TODO:: Add language option, we could have separate link for diff languages.
	 *
	 * @since  5.4
	 *
	 * @param string $doc_id Section identifier.
	 *
	 * @return string|array
	 */
	public function get_suggest( $doc_id ) {
		$suggest = [
			'include_extra_files' => [
				'url'   => 'https://backwpup.com/backwpup-5-4/',
				'title' => 'Welcome to BackWPup 5.4!',
			],
		];

		return $suggest[ $doc_id ] ?? [];
	}
}
