<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin;

use BackWPup;
use WPMedia\BackWPup\Admin\Notices\Subscriber as NoticeSubscriber;
use WPMedia\BackWPup\Admin\Notices\Notices\Notice513;
use Inpsyde\BackWPup\Notice\NoticeView;
use WPMedia\BackWPup\Admin\Settings\Subscriber as SettingSubscriber;
use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;

class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Service provided by this provider
	 *
	 * @var array
	 */
	protected $provides = [
		'notice_subscriber',
		'notice_view_factory',
		'backwpup_adapter',
		SettingSubscriber::class,
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		SettingSubscriber::class,
		'notice_subscriber',
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

		// Register BackWPupAdapter.
		$this->getContainer()->add( 'backwpup_adapter', BackWPupAdapter::class );

		// Register Notice513 with its NoticeView and BackWPupAdapter dependencies.
		$notice513_view = new NoticeView( Notice513::ID );
		$this->getContainer()->addShared( 'notice_513', Notice513::class )
			->addArgument( $notice513_view )
			->addArgument( $this->getContainer()->get( 'backwpup_adapter' ) );

		// Register the Subscriber with an array of notice instances.
		$this->getContainer()->addShared( 'notice_subscriber', NoticeSubscriber::class )
			->addArgument(
				[
					$this->getContainer()->get( 'notice_513' ),
				]
				);
		$this->getContainer()->addShared( SettingSubscriber::class );
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
