<?php
/**
 * Restore Log Zip Generator.
 *
 * @since   3.5.0
 */

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Restore\LogDownloader;

/**
 * Class RestoreLogZipGenerator.
 *
 * @since   3.5.0
 */
final class ZipGenerator
{
    /**
     * File.
     *
     * @since 3.5.0
     *
     * @var string The zip file path
     */
    private $filePath;

    /**
     * Files.
     *
     * @since 3.5.0
     *
     * @var string[] The files list to zip
     */
    private $files;

    /**
     * Zip.
     *
     * @since 3.5.0
     *
     * @var \PclZip The zip instance
     */
    private $zip;

    /**
     * ZipGenerator constructor.
     *
     * @since 3.5.0
     *
     * @param \PclZip  $zip      the zip instance
     * @param string   $filePath the path of the zip file
     * @param string[] $files    the files list to zip
     *
     * @throws \InvalidArgumentException in case one of the parameter isn't valid
     */
    public function __construct(\PclZip $zip, string $filePath, array $files)
    {
        if (!$filePath) {
            throw new \InvalidArgumentException('Wrong value for file path.');
        }

        $this->zip = $zip;
        $this->files = $files;
        $this->filePath = $filePath;
    }

    /**
     * Zip.
     *
     * @since 3.5.0
     *
     * @throws \RuntimeException In case the zip file cannot be opened
     */
    public function zip(): void
    {
        foreach ($this->files as $file) {
            file_exists($file) && $this->zip->add($file, PCLZIP_OPT_REMOVE_ALL_PATH);
        }
    }

    /**
     * Zip File Path.
     *
     * @since 3.5.0
     *
     * @return string The file path of the zip
     */
    public function path(): string
    {
        return $this->filePath;
    }
}
