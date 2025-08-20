<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin;

use BackWPup;
use WPMedia\BackWPup\Admin\Beacon\Beacon;
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
use WPMedia\BackWPup\Dependencies\League\Container\Argument\Literal\StringArgument;
use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;

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
