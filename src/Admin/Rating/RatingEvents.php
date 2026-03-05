<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Rating;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;

class RatingEvents {
	public const BANNER_DISPLAYED                = 'Banner displayed';
	public const LEAVE_REVIEW_CLICKED            = 'Leave review clicked';
	public const REMIND_LATER_CLICKED            = 'Remind me later clicked';
	public const BANNER_DISMISSED                = 'Banner dismissed';
	public const TRIGGER_FIRST_SUCCESSFUL_BACKUP = 'first_successful_backup';
	public const TRIGGER_LEAVE_REVIEW            = 'leave_review';
	public const TRIGGER_REMIND_LATER            = 'remind_later';
	public const TRIGGER_DISMISSED               = 'dismissed';
	public const TRIGGERS                        = [
		self::TRIGGER_FIRST_SUCCESSFUL_BACKUP,
		self::TRIGGER_LEAVE_REVIEW,
		self::TRIGGER_REMIND_LATER,
		self::TRIGGER_DISMISSED,
	];

	/**
	 * Adapter for BackWPup plugin data.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup;

	/**
	 * Constructor.
	 *
	 * @param BackWPupAdapter $backwpup BackWPupAdapter instance.
	 */
	public function __construct( BackWPupAdapter $backwpup ) {
		$this->backwpup = $backwpup;
	}

	/**
	 * Tracks rating prompt events.
	 *
	 * @param string $trigger Trigger identifier.
	 * @phpstan-param value-of<self::TRIGGERS> $trigger
	 * @psalm-param value-of<self::TRIGGERS> $trigger
	 * @throws \InvalidArgumentException When trigger is invalid.
	 * @return void
	 */
	public function do_tracking( string $trigger ): void {
		$mapping_events = [
			self::TRIGGER_FIRST_SUCCESSFUL_BACKUP => [
				self::BANNER_DISPLAYED,
				[
					'trigger_context' => self::TRIGGER_FIRST_SUCCESSFUL_BACKUP,
				],
			],
			self::TRIGGER_LEAVE_REVIEW            => [
				self::LEAVE_REVIEW_CLICKED,
				[
					'action_type' => self::TRIGGER_LEAVE_REVIEW,
				],
			],
			self::TRIGGER_REMIND_LATER            => [
				self::REMIND_LATER_CLICKED,
				[
					'action_type' => self::TRIGGER_REMIND_LATER,
				],
			],
			self::TRIGGER_DISMISSED               => [
				self::BANNER_DISMISSED,
				[],
			],
		];

		if ( ! isset( $mapping_events[ $trigger ] ) ) {
			throw new \InvalidArgumentException( 'Invalid rating event trigger.' );
		}

		[ $event_name, $event_props ] = $mapping_events[ $trigger ];

		do_action(
		'backwpup_link_clicked',
		$event_name,
		array_merge( $this->get_base_event_props(), $event_props )
		);
	}

	/**
	 * Builds base event properties.
	 *
	 * @return array<string,string>
	 */
	private function get_base_event_props(): array {
		return [
			'application'    => 'backwpup',
			'user_type'      => $this->backwpup->is_pro() ? 'pro' : 'free',
			'plugin_version' => (string) $this->backwpup->get_plugin_data( 'Version' ),
		];
	}
}
