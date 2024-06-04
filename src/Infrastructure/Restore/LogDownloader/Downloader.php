<?php
/**
 * Log Downloader.
 *
 * @since   3.5.0
 */

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Restore\LogDownloader;

/**
 * Class Downloader.
 *
 * @since   3.5.0
 */
final class Downloader
{
    /**
     * View.
     *
     * @since 3.5.0
     *
     * @var View Instance of view
     */
    private $view;

    /**
     * The Zip Generator.
     *
     * @since 3.5.0
     *
     * @var ZipGenerator The instance of ZipGenerator
     */
    private $zip_generator;

    /**
     * Files list.
     *
     * @since 3.5.0
     *
     * @var string[] The files list to zip
     */
    private $files;

    /**
     * File System.
     *
     * @since 3.5.0
     *
     * @var \WP_Filesystem_Base A WP filesystem instance
     */
    private $filesystem;

    /**
     * Downloader constructor.
     *
     * @since 3.5.0
     *
     * @param View                $view          instance of view
     * @param ZipGenerator        $zip_generator the instance of ZipGenerator
     * @param string[]            $files         the files list to zip
     * @param \WP_Filesystem_Base $filesystem    a WP filesystem instance
     */
    public function __construct(View $view, ZipGenerator $zip_generator, \WP_Filesystem_Base $filesystem, array $files)
    {
        $this->view = $view;
        $this->zip_generator = $zip_generator;
        $this->files = $files;
        $this->filesystem = $filesystem;
    }

    /**
     * Zip.
     *
     * @since 3.5.0
     *
     * @throws \RuntimeException If the zip cannot be created
     */
    public function zip(): void
    {
        $this->zip_generator->zip();
    }

    /**
     * Zip File Path.
     *
     * @since 3.5.0
     *
     * @return string The file path
     */
    public function path()
    {
        return $this->zip_generator->path();
    }

    /**
     * Clean the Controller.
     *
     * @since 3.5.0
     */
    public function clean(): void
    {
        $this->filesystem->delete(
            $this->zip_generator->path()
        );
    }

    /**
     * Downloader View.
     *
     * @since 3.5.0
     */
    public function view(): void
    {
        $this->view->view();
    }

    /**
     * Can be Downloaded.
     *
     * @since 3.5.0
     *
     * @return bool true if the file can be downloaded, false otherwise
     */
    public function can_be_downloaded(): bool
    {
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                return true;
            }
        }

        return false;
    }
}
