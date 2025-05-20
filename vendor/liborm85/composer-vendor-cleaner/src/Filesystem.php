<?php

namespace Liborm85\ComposerVendorCleaner;

use Composer\Util\Filesystem as ComposerFilesystem;
use Composer\Util\Platform;
use FilesystemIterator;
use RecursiveDirectoryIterator;

class Filesystem
{

    /**
     * @var ComposerFilesystem
     */
    private $filesystem;

    public function __construct()
    {
        $this->filesystem = new ComposerFilesystem();
    }

    /**
     * @param string $directory
     * @return bool
     */
    public function removeDirectory($directory)
    {
        return $this->filesystem->removeDirectory($directory);
    }

    /**
     * @param string $file
     * @return bool
     */
    public function removeFile($file)
    {
        // fix for wrong writeable permission after clone from git on Windows
        if (Platform::isWindows() && !is_writable($file)) {
            @chmod($file, 0666);
        }

        return $this->filesystem->unlink($file);
    }

    /**
     * @param string $directory
     * @return bool
     */
    public function isEmptyDirectory($directory)
    {
        $iterator = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);

        return iterator_count($iterator) === 0;
    }
}
