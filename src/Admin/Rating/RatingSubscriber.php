<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Rating;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;

/**
 * Hooks the rating notice into wp-admin.
 */
class RatingSubscriber implements SubscriberInterface {
	/**
	 * Rating notice id used in markup and JS.
	 *
	 * @var string
	 */
	private const NOTICE_ID = 'backwpup_rate_notice';

	/**
	 * Rating instance.
	 *
	 * @var Rating
	 */
	private Rating $rating;

	/**
	 * RatingNoticeDecider instance.
	 *
	 * @var RatingNoticeDecider
	 */
	private RatingNoticeDecider $decider;

	/**
	 * RatingActions instance.
	 *
	 * @var RatingActions
	 */
	private RatingActions $actions;

	/**
	 * RatingEvents instance.
	 *
	 * @var RatingEvents
	 */
	private RatingEvents $events;

	/**
	 * RatingNoticeMessageProvider instance.
	 *
	 * @var RatingNoticeMessageProvider
	 */
	private RatingNoticeMessageProvider $message_provider;

	/**
	 * Adapter for BackWPup plugin data.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup;

	/**
	 * RatingInstallStateInitializer instance.
	 *
	 * @var RatingInstallStateInitializer
	 */
	private RatingInstallStateInitializer $initializer;

	/**
	 * Constructor.
	 *
	 * @param Rating                        $rating Rating instance.
	 * @param RatingNoticeDecider           $decider RatingNoticeDecider instance.
	 * @param RatingActions                 $actions RatingActions instance.
	 * @param RatingNoticeMessageProvider   $message_provider RatingNoticeMessageProvider instance.
	 * @param RatingEvents                  $events RatingEvents instance.
	 * @param BackWPupAdapter               $backwpup BackWPupAdapter instance.
	 * @param RatingInstallStateInitializer $install_state_initializer RatingInstallStateInitializer instance.
	 */
	public function __construct(
		Rating $rating,
		RatingNoticeDecider $decider,
		RatingActions $actions,
		RatingNoticeMessageProvider $message_provider,
		RatingEvents $events,
		BackWPupAdapter $backwpup,
		RatingInstallStateInitializer $install_state_initializer
	) {
		$this->rating           = $rating;
		$this->decider          = $decider;
		$this->actions          = $actions;
		$this->message_provider = $message_provider;
		$this->events           = $events;
		$this->backwpup         = $backwpup;
		$this->initializer      = $install_state_initializer;
	}

	/**
	 * Returns the events this subscriber wants to listen to.
	 *
	 * @return array<string,string>
	 */
	public static function get_subscribed_events(): array {
		return [
			'admin_init'                                  => 'maybe_initialize_install_state',
			'admin_enqueue_scripts'                       => 'enqueue',
			'admin_notices'                               => 'render_rating_notice',
			'admin_post_backwpup_rating_notice_dismiss'   => 'dismiss',
			'admin_post_backwpup_rating_notice_remind'    => 'remind',
			'wp_ajax_backwpup_rating_notice_leave_review' => 'leave_review',
		];
	}

	/**
	 * Maybe install state options.
	 *
	 * @return void
	 */
	public function maybe_initialize_install_state(): void {
		$this->initializer->maybe_initialize();
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook
	 * @return void
	 */
	public function enqueue( string $hook ): void {

		if ( ! $this->should_display() ) {
			return;
		}

		$plugin_url  = $this->backwpup->get_plugin_data( 'URL' );
		$assets_path = $plugin_url . '/assets/js/admin-rating.js';

		wp_register_script( 'backwpup-admin-rating',  $assets_path,  [],  $this->backwpup->get_plugin_data( 'Version' ),  true );
		wp_enqueue_script( 'backwpup-admin-rating' );
		wp_localize_script(
			'backwpup-admin-rating',
			'BackWPupRating',
			[
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'backwpup_rating_notice_leave_review' ),
				'notice_id' => self::NOTICE_ID,
			]
		);
	}

	/**
	 * Renders notice when conditions are met.
	 */
	public function render_rating_notice(): void {

		if ( ! $this->should_display() ) {
			return;
		}

		$user_id = (int) get_current_user_id();
		$now     = time();

		if ( ! $this->decider->should_show_for_user( $user_id, $now ) ) {
			return;
		}

		$this->decider->mark_shown( $user_id );
		$this->events->do_tracking( RatingEvents::TRIGGER_FIRST_SUCCESSFUL_BACKUP );

		$dismiss_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=backwpup_rating_notice_dismiss' ),
			'backwpup_rating_notice_dismiss'
		);

		$remind_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=backwpup_rating_notice_remind' ),
			'backwpup_rating_notice_remind'
		);

		$leave_url = 'https://wordpress.org/support/plugin/backwpup/reviews/#new-post';

		$content = $this->message_provider->get_message( RatingNoticeMessageProvider::CTX_AFTER_ONBOARDING );

		$this->rating->render(
			$content['title'],
			$content['message'],
			$dismiss_url,
			$remind_url,
			$leave_url,
			self::NOTICE_ID
		);
	}

	/**
	 * Dismiss action.
	 *
	 * @return void
	 */
	public function dismiss(): void {
		$this->actions->dismiss();
	}

	/**
	 * Remind me later action.
	 *
	 * @return void
	 */
	public function remind(): void {
		$this->actions->remind();
	}

	/**
	 * Leave Review action.
	 *
	 * @return void
	 */
	public function leave_review(): void {
		check_ajax_referer( 'backwpup_rating_notice_leave_review' );
		$this->decider->dismiss_forever( (int) get_current_user_id() );
		$this->events->do_tracking( RatingEvents::TRIGGER_LEAVE_REVIEW );
		wp_send_json_success();
	}

	/**
	 * Determines whether the rating notice should be displayed on the current screen.
	 *
	 * @return bool
	 */
	private function should_display(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen instanceof \WP_Screen ) {
			return false;
		}

		if ( ! in_array( $screen->id, [ 'toplevel_page_backwpup', 'toplevel_page_backwpup-network' ], true ) ) {
			return false;
		}

		$user_id = (int) get_current_user_id();
		if ( 0 >= $user_id ) {
			return false;
		}

		$now   = time();
		$state = $this->decider->get_user_notice_state( $user_id, $now );
		if ( $state[ RatingNoticeDecider::STATE_DISMISSED ] ) {
			return false;
		}

		$referer = wp_get_referer();
		if ( ! empty( $referer ) ) {
			$referer_query = wp_parse_url( $referer, PHP_URL_QUERY );
			if ( is_string( $referer_query ) && '' !== $referer_query ) {
				parse_str( $referer_query, $referer_args );
				$referer_page = '';
				if ( isset( $referer_args['page'] ) && is_string( $referer_args['page'] ) ) {
					$referer_page = sanitize_key( $referer_args['page'] );
				}
				if ( 'backwpupfirstbackup' === $referer_page ) {
					return true;
				}
			}
		}

		if ( $state[ RatingNoticeDecider::STATE_REMIND_DUE ] ) {
			return true;
		}
		if ( $state[ RatingNoticeDecider::STATE_DISMISSED_UNTIL_DUE ] ) {
			return true;
		}

		return false;
	}
}
