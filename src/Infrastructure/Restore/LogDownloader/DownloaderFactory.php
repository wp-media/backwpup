<?php
/**
 * Log Downloader Factory.
 *
 * @since   3.5.0
 */

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Restore\LogDownloader;

use function backwpup_wpfilesystem;
use function Inpsyde\BackWPup\Infrastructure\Restore\restore_container;
use Pimple\Container;
use Pimple\Exception\FrozenServiceException;

/**
 * Class DownloderFactory.
 *
 * @since   3.5.0
 */
final class DownloaderFactory
{
    /**
     * Files List.
     *
     * @since 3.5.0
     *
     * @var string[] The files to compress
     */
    private static $files = [
        'debug.log',
        'restore.log',
        'restore.dat.bkp',
        'restore.dat',
    ];

    /**
     * Container.
     *
     * @since 3.5.0
     *
     * @var Container The container instance
     */
    private $container;

    /**
     * DownloaderFactory constructor.
     *
     * @since 3.5.0
     *
     * @throws FrozenServiceException if the service has been marked as frozen,
     *                                indicating that it has already been retrieved
     *                                and cannot be modified
     * @throws \OutOfBoundsException  if the provided name does not exist in the container
     */
    public function __construct()
    {
        $this->container = restore_container(null);
    }

    /**
     * Create the Downloader instance.
     *
     * @since 3.5.0
     *
     * @throws \RuntimeException in case the function `gzopen` doesn't exists
     * @throws \RuntimeException in case temporary directory isn't readable or writable
     *
     * @return Downloader A instance of the Downloader class
     */
    public function create()
    {
        if (!\function_exists('gzopen')) {
            throw new \RuntimeException(
                'gzopen function doesn\'t exist, cannot create a Downloader instance.'
            );
        }

        /** @var string $dir */
        $dir = $this->container['project_temp'] ?? '';
        if (!$dir || !is_readable($dir) || !is_writable($dir)) {
            throw new \RuntimeException(
                'Project temporary directory doesn\'t exist or is not readable/writable'
            );
        }

        $this->ensurePclZip();
        $this->createFilesPath();

        $filePath = trailingslashit($dir) . 'log.zip';

        $view = new View(
            esc_html__('Download Log', 'backwpup'),
            self_admin_url('admin.php?page=backwpuprestore'),
            self::$files
        );
        $zipGenerator = new ZipGenerator(
            new \PclZip($filePath),
            $filePath,
            self::$files
        );

        return new Downloader($view, $zipGenerator, backwpup_wpfilesystem(), self::$files);
    }

    /**
     * Generate the absolute files path.
     *
     * @since 3.5.0
     */
    private function createFilesPath(): void
    {
        foreach (self::$files as &$file) {
            $file = trailingslashit((string) $this->container['project_temp']) . $file;
        }
    }

    /**
     * Require PclZip if needed.
     */
    private function ensurePclZip(): void
    {
        if (!class_exists(\PclZip::class)) {
            require_once ABSPATH . '/wp-admin/includes/class-pclzip.php';
        }
    }
}
