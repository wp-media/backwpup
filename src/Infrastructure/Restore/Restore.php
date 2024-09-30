<?php
/**
 * Restore.
 *
 * @since   3.5.0
 */

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Restore;

use BackWPup_Download_File;
use function backwpup_wpfilesystem;
use Inpsyde\BackWPup\Infrastructure\Restore\LogDownloader\DownloaderFactory;
use Inpsyde\BackWPup\Infrastructure\Restore\LogDownloader\View;
use Inpsyde\Restore\AjaxHandler;
use Inpsyde\Restore\Api\Module\Session\Session;
use Inpsyde\Restore\LocalizeScripts;
use Pimple\Exception\FrozenServiceException;

/**
 * Class Restore.
 *
 * @since   3.5.0
 */
final class Restore
{
    /**
     * Booted Correctly.
     *
     * The control variable is needed because much code depends on parts that works with filesystem, if for
     * some reason some file or directory isn't created correctly or isn't writable we may encountered issues in the
     * booting fase.
     *
     * @var bool true if the restore has been booted correctly, false otherwise
     */
    private $booted_correctly = false;

    /**
     * Set Hooks.
     *
     * @since 3.5.0
     *
     * @return Restore for concatenation
     */
    public function set_hooks(): self
    {
        add_action('admin_init', [$this, 'ajax_handler']);
        add_action('admin_head', [$this, 'localize_scripts']);
        add_action('backwpup_page_restore', [$this, 'boot']);
        add_action('backwpup_page_restore', [$this, 'handle_restore_log_download_request']);

        return $this;
    }

    /**
     * Initialize.
     *
     * @return Restore for concatenation
     */
    public function init(): self
    {
        $this->requires();

        return $this;
    }

    /**
     * Booting the Restore.
     *
     * @since 3.5.0
     */
    public function boot(): void
    {
        try {
            restore_boot();

            $container = restore_container(null);
            /** @var Session $session */
            $session = $container['session'];

            if ($session->notifications()) {
                $notificator = new Notificator($session);
                $notificator->load();
            }

            // Mark as booted correctly.
            $this->booted_correctly = true;
        } catch (\Exception $e) {
            \BackWPup_Admin::message($e->getMessage(), true);
            \BackWPup_Admin::display_messages();
        }
    }

    /**
     * Handle ajax request.
     *
     * @since 3.5.0
     *
     * @throws FrozenServiceException if the service has been marked as frozen,
     *                                indicating that it has already been retrieved
     *                                and cannot be modified
     * @throws \OutOfBoundsException  if the provided name does not exist in the container
     */
    public function ajax_handler(): void
    {
        if (\defined('DOING_AJAX') && DOING_AJAX && \defined('WP_ADMIN') && WP_ADMIN) {
            restore_boot();
            /** @var AjaxHandler $ajaxHandler */
            $ajaxHandler = restore_container('ajax_handler');
            $ajaxHandler->load();
        }
    }

    /**
     * Handle Restore Log Download Request.
     *
     * @since 3.5.0
     */
    public function handle_restore_log_download_request(): void
    {
        // phpcs:ignore
        $request = isset($_GET['action']) ? filter_var($_GET['action']) : '';
        if ('download_restore_log' !== $request) {
            return;
        }

        $capability = 'backwpup_restore';

        try {
            $downloader_factory = new DownloaderFactory();
            $log_downloader = $downloader_factory->create();

            // Compress the log file.
            $log_downloader->zip();
        } catch (\RuntimeException $exc) {
            return;
        }

        // phpcs:ignore
        $download_handler = new \BackWpup_Download_Handler(
            new BackWPup_Download_File(
                $log_downloader->path(),
                static function (\BackWPup_Download_File_Interface $obj) use ($log_downloader): void {
                    $obj->clean_ob()
                        ->headers()
                    ;

                    // phpcs:ignore
                    echo backwpup_wpfilesystem()->get_contents($obj->filepath());

                    $log_downloader->clean();

                    exit();
                },
                $capability
            ),
            View::NONCE_NAME,
            $capability,
            View::NONCE_ACTION
        );

        $download_handler->handle();
    }

    /**
     * Localize Scripts.
     *
     * @since 3.5.0
     *
     * @throws \RuntimeException if the translator cannot be loaded
     * @psalm-suppress UnresolvableInclude
     */
    public function localize_scripts(): void
    {
        if (!$this->booted_correctly) {
            return;
        }

        // Retrieve the list of the text to translate.
        /** @var string[] $list */
        $list = include \BackWPup::get_plugin_data('plugindir') . '/vendor/inpsyde/backwpup-restore-shared/inc/localize-restore-api.php';

        $localizer = new LocalizeScripts($list);
        $localizer->output();
    }

    /**
     * Requirements.
     *
     * @since 3.5.0
     */
    private function requires(): void
    {
        $file = untrailingslashit(\BackWPup::get_plugin_data('plugindir'))
                . '/src/Infrastructure/Restore/commons.php';

        if ($file) {
            /** @psalm-suppress UnresolvableInclude */
            require_once $file;
        }
    }
}
