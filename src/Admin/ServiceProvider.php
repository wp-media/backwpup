<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin;

use BackWPup;
use WPMedia\BackWPup\Admin\Beacon\Beacon;
use WPMedia\BackWPup\Admin\Messages\API\Rest;
use WPMedia\BackWPup\Admin\Notices\Notices;
use WPMedia\BackWPup\Admin\Notices\Notices\Notice52;
use WPMedia\BackWPup\Admin\Notices\Notices\Notice522;
use WPMedia\BackWPup\Admin\Notices\Notices\NoticeDataCorrupted;
use WPMedia\BackWPup\Admin\Notices\Notices\NoticeTracking;
use WPMedia\BackWPup\Admin\Notices\Subscriber as NoticeSubscriber;
use WPMedia\BackWPup\Admin\Notices\Notices\Notice513;
use Inpsyde\BackWPup\Notice\NoticeView;
use WPMedia\BackWPup\Admin\Settings\Subscriber as SettingSubscriber;
use WPMedia\BackWPup\Admin\Frontend\Subscriber as AdminFrontendSubscriber;
use WPMedia\BackWPup\Common\ErrorSignals\ErrorSignalsStore;
use WPMedia\BackWPup\Dependencies\League\Container\Argument\Literal\StringArgument;
use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use WPMedia\Mixpanel\Optin;

class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Service provided by this provider
	 *
	 * @var array
	 */
	protected $provides = [
		'notice_subscriber',
		'notice_view_factory',
		SettingSubscriber::class,
		AdminFrontendSubscriber::class,
		'options',
		\WPMedia\BackWPup\Admin\Messages\API\Subscriber::class,
		\WPMedia\BackWPup\Admin\Chatbot\API\ChatbotRestSubscriber::class,
		\WPMedia\BackWPup\Admin\Chatbot\ChatbotSubscriber::class,
		\WPMedia\BackWPup\Common\ErrorSignals\ErrorSignalsSubscriber::class,
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		SettingSubscriber::class,
		'notice_subscriber',
		AdminFrontendSubscriber::class,
		\WPMedia\BackWPup\Admin\Messages\API\Subscriber::class,
		\WPMedia\BackWPup\Admin\Chatbot\API\ChatbotRestSubscriber::class,
		\WPMedia\BackWPup\Admin\Chatbot\ChatbotSubscriber::class,
		\WPMedia\BackWPup\Common\ErrorSignals\ErrorSignalsSubscriber::class,
	];

	/**
	 * Check if the service provider provides a specific service.
	 *
	 * @param string $id The id of the service.
	 *
	 * @return bool
	 */
	public function provides( string $id ): bool {
		return in_array( $id, $this->provides, true );
	}

	/**
	 * Registers items with the container
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->addShared( 'options', OptionData::class )
			->addArgument( [ $this->getContainer()->get( 'options_api' )->get( 'settings', [] ) ] );

		$this->getContainer()->addShared( 'beacon', Beacon::class )
			->addArgument( new StringArgument( $this->getContainer()->get( 'template_path' ) . '/notice' ) );

		// Register notices.
		$this->getContainer()->addShared( 'admin_notices', Notices::class )
			->addArguments(
				[
					'options',
					'backwpup_adapter',
					'beacon',
				]
				);

		$this->getContainer()->addShared( 'admin_subscriber', Subscriber::class )
			->addArgument( 'options' );

		// Deprecate, remove old container of notices.
		// Register Notice513 with its NoticeView and BackWPupAdapter dependencies.
		$notice513_view = new NoticeView( Notice513::ID );
		$this->getContainer()->addShared( 'notice_513', Notice513::class )
			->addArgument( $notice513_view )
			->addArgument( $this->getContainer()->get( 'backwpup_adapter' ) );

		// Notice for 5.2.
		$this->getContainer()->add( 'notice_52_view', NoticeView::class )
			->addArgument( Notice52::ID );
		$this->getContainer()->addShared( 'notice_52', Notice52::class )
			->addArguments(
				[
					'notice_52_view',
					'backwpup_adapter',
				]
			);
		// Notice for 5.2.2.
		$this->getContainer()->add( 'notice_522_view', NoticeView::class )
			->addArgument( Notice522::ID );
		$this->getContainer()->addShared( 'notice_522', Notice522::class )
			->addArguments(
				[
					'notice_522_view',
					'backwpup_adapter',
				]
			);
		// Notice for data corrupted alert.
		$this->getContainer()->add( 'notice_data_corrupted_view', NoticeView::class )
			->addArgument( NoticeDataCorrupted::ID );
		$this->getContainer()->addShared( 'notice_datacorrupted', NoticeDataCorrupted::class )
			->addArguments(
				[
					'notice_data_corrupted_view',
					'job_adapter',
				]
			);

		$this->getContainer()->add( Notices\NoticeMissingCurl::ID . '_view', NoticeView::class )
			->addArgument( Notices\NoticeMissingCurl::ID );
		$this->getContainer()->addShared( Notices\NoticeMissingCurl::class, Notices\NoticeMissingCurl::class )
			->addArgument( Notices\NoticeMissingCurl::ID . '_view' );

		// Register the Subscriber with an array of notice instances.
		$this->getContainer()->addShared( 'notice_subscriber', NoticeSubscriber::class )
			->addArguments(
				[
					'admin_notices',
					[
						$this->getContainer()->get( 'notice_522' ),
						$this->getContainer()->get( 'notice_52' ),
						$this->getContainer()->get( 'notice_513' ),
						$this->getContainer()->get( 'notice_datacorrupted' ),
						$this->getContainer()->get( Notices\NoticeMissingCurl::class ),
					],
				]
				);
		$this->getContainer()->addShared( SettingSubscriber::class );
		$this->getContainer()->addShared( AdminFrontendSubscriber::class )
			->addArguments(
				[
					'backwpup_adapter',
				]
			);
		$this->getContainer()->addShared( Rest::class );
		$this->getContainer()->addShared( \WPMedia\BackWPup\Admin\Messages\API\Subscriber::class )
			->addArguments(
				[
					Rest::class,
				]
			);

		$this->getContainer()->addShared(
			'error_signals_store',
			\WPMedia\BackWPup\Common\ErrorSignals\ErrorSignalsStore::class
		);

		$this->getContainer()->addShared(
			'mixpanel_optin',
			Optin::class
			)->addArguments(
				[
					'backwpup',
					'manage_options',
				]
			);

		$this->getContainer()->addShared(
			'chatbot_context_snapshot_builder',
			\WPMedia\BackWPup\Admin\Chatbot\ContextSnapshotBuilder::class
		)->addArguments(
			[
				'backwpup_adapter',
				'error_signals_store',
				'mixpanel_optin',
			]
			);

		$this->getContainer()->addShared(
			\WPMedia\BackWPup\Admin\Chatbot\API\ChatbotRest::class
		)->addArguments(
			[
				'chatbot_context_snapshot_builder',
			]
		);

		$this->getContainer()->addShared(
			\WPMedia\BackWPup\Admin\Chatbot\API\ChatbotRestSubscriber::class
		)->addArguments(
			[
				\WPMedia\BackWPup\Admin\Chatbot\API\ChatbotRest::class,
			]
		);

		$this->getContainer()->addShared( \WPMedia\BackWPup\Admin\Chatbot\Chatbot::class )
			->addArguments(
				[
					new StringArgument( $this->getContainer()->get( 'template_path' ) . '/chatbot' ),
					'chatbot_context_snapshot_builder',
				]
			);

		$this->getContainer()->addShared(
			\WPMedia\BackWPup\Admin\Chatbot\ChatbotSubscriber::class
		)->addArguments(
			[
				'backwpup_adapter',
				\WPMedia\BackWPup\Admin\Chatbot\Chatbot::class,
			]
		);

		$this->getContainer()->addShared(
			\WPMedia\BackWPup\Common\ErrorSignals\ErrorSignalsSubscriber::class
		)->addArgument( 'error_signals_store' );
	}

	/**
	 * Returns the subscribers array
	 *
	 * @return array
	 */
	public function get_subscribers() {
		return $this->subscribers;
	}
}
