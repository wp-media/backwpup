<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Rating;

use WPMedia\BackWPup\Common\AbstractRender;

/**
 * Rating notice view.
 */
class Rating extends AbstractRender {

	/**
	 * Render notice.
	 *
	 * @param string $title
	 * @param string $message
	 * @param string $dismiss_url
	 * @param string $remind_url
	 * @param string $leave_url
	 * @param string $notice_id
	 */
	public function render(
		string $title,
		string $message,
		string $dismiss_url,
		string $remind_url,
		string $leave_url,
		string $notice_id
	): void {
		$data = [
			'title'       => $title,
			'message'     => $message,
			'dismiss_url' => $dismiss_url,
			'remind_url'  => $remind_url,
			'leave_url'   => $leave_url,
			'notice_id'   => $notice_id,
		];

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->generate( 'rating', $data );
	}
}
