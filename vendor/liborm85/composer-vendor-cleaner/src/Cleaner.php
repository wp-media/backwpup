<?php

namespace Liborm85\ComposerVendorCleaner;

use Composer\IO\IOInterface;

class Cleaner
{

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var bool
     */
    private $matchCase;

    /**
     * @var string[][]
     */
    private $devFiles;

    /**
     * @var bool
     */
    private $removeEmptyDirs;

    /**
     * @var int
     */
    private $packagesCount;

    /**
     * @var int
     */
    private $removedDirectories = 0;

    /**
     * @var int
     */
    private $removedFiles = 0;

    /**
     * @var int
     */
    private $removedEmptyDirectories = 0;

    /**
     * @var bool
     */
    private $analyseDevFiles = false;

    /**
     * @var string[][]
     */
    private $notUsedDevFiles;

    /**
     * @param IOInterface $io
     * @param Filesystem $filesystem
     * @param string[][] $devFiles
     * @param bool $matchCase
     * @param bool $removeEmptyDirs
     */
    public function __construct($io, $filesystem, $devFiles, $matchCase, $removeEmptyDirs)
    {
        $this->io = $io;
        $this->filesystem = $filesystem;
        $this->devFiles = $devFiles;
        $this->matchCase = $matchCase;
        $this->removeEmptyDirs = $removeEmptyDirs;
        $this->notUsedDevFiles = $this->devFiles;
    }

    /**
     * @return void
     */
    public function enableAnalyseDevFiles()
    {
        $this->analyseDevFiles = true;
    }

    /**
     * @param Package[] $packages
     * @return void
     */
    public function cleanupPackages($packages)
    {
        $this->io->write("");
        $this->io->write("Composer vendor cleaner: <info>Cleaning packages in vendor directory</info>");

        $this->packagesCount = count($packages);

        $devFilesFinder = new DevFilesFinder($this->devFiles, $this->matchCase);

        foreach ($packages as $package) {
            $devFilesPatternsForPackage = $devFilesFinder->getGlobPatternsForPackage($package->getPrettyName());
            if (empty($devFilesPatternsForPackage)) {
                continue;
            }

            $allFiles = $this->getDirectoryEntries($package->getInstallPath());
            $filesToRemove = $devFilesFinder->getFilteredEntries($allFiles, $devFilesPatternsForPackage);

            $this->processNotUsedDevFiles($package->getPrettyName(), $allFiles, $filesToRemove);

            $this->removeFiles($package->getPrettyName(), $package->getInstallPath(), $filesToRemove);
        }

        if ($this->removeEmptyDirs) {
            foreach ($packages as $package) {
                $this->removeEmptyDirectories($package->getPrettyName(), $package->getInstallPath());
            }
        }
    }

    /**
     * @param string $binDir
     * @return void
     */
    public function cleanupBinary($binDir)
    {
        if (!file_exists($binDir)) {
            return;
        }

        $this->io->write("Composer vendor cleaner: <info>Cleaning vendor binary directory</info>");

        $devFilesFinder = new DevFilesFinder($this->devFiles, $this->matchCase);

        $devFilesPatternsForBin = $devFilesFinder->getGlobPatternsForPackage('bin');
        if (!empty($devFilesPatternsForBin)) {
            $allFiles = $this->getDirectoryEntries($binDir);
            $filesToRemove = $devFilesFinder->getFilteredEntries($allFiles, $devFilesPatternsForBin);

            $this->processNotUsedDevFiles('bin', $allFiles, $filesToRemove);

            $this->removeFiles('bin', $binDir, $filesToRemove);
        }

        if ($this->removeEmptyDirs) {
            $this->removeEmptyDirectories('bin', $binDir);
        }
    }

    /**
     * @param string $packageName
     * @param string[] $allFiles
     * @param string[] $filesToRemove
     * @return void
     */
    private function processNotUsedDevFiles($packageName, $allFiles, $filesToRemove)
    {
        if (!$this->analyseDevFiles) {
            return;
        }

        $devFilesFinder = new DevFilesFinder($this->devFiles, $this->matchCase);
        foreach ($this->notUsedDevFiles as $packageGlob => &$devFile) {
            if ($devFilesFinder->isGlobPatternForPackage($packageName, $packageGlob)) {
                foreach ($devFile as $key => $item) {
                    if (substr($item, 0, 1) === '!') {
                        if (!empty($devFilesFinder->getFilteredEntries($allFiles, [substr($item, 1)]))) {
                            unset($devFile[$key]);
                            if (empty($devFile)) {
                                unset($this->notUsedDevFiles[$packageGlob]);
                            }
                        }
                    } else {
                        if (!empty($devFilesFinder->getFilteredEntries($filesToRemove, [$item]))) {
                            unset($devFile[$key]);
                            if (empty($devFile)) {
                                unset($this->notUsedDevFiles[$packageGlob]);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    public function finishCleanup()
    {
        if ($this->analyseDevFiles && !empty($this->notUsedDevFiles)) {
            $this->io->write("");
            $this->io->write(
                "Composer vendor cleaner: <warning>Found {$this->getNotUsedDevFilesCount()} unused cleanup patterns.</warning>"
            );
            $this->io->write("");
            $this->io->write(
                "Composer vendor cleaner: <warning>List of unused cleanup patterns:</warning>"
            );
            foreach ($this->notUsedDevFiles as $packageGlob => $devFile) {
                foreach ($devFile as $item) {
                    $this->io->write(
                        "Composer vendor cleaner: <warning> - '$packageGlob' -> '$item'</warning>"
                    );
                }
            }
            $this->io->write("");
        }

        if ($this->removedEmptyDirectories) {
            $this->io->write(
                "Composer vendor cleaner: <info>Removed {$this->removedFiles} files and {$this->removedDirectories} (of which {$this->removedEmptyDirectories} are empty) directories from {$this->packagesCount} packages</info>"
            );
        } else {
            $this->io->write(
                "Composer vendor cleaner: <info>Removed {$this->removedFiles} files and {$this->removedDirectories} directories from {$this->packagesCount} packages</info>"
            );
        }
        $this->io->write("");
    }

    /**
     * @return int
     */
    private function getNotUsedDevFilesCount()
    {
        $count = 0;
        foreach ($this->notUsedDevFiles as $devFile) {
            $count += count($devFile);
        }

        return $count;
    }

    /**
     * @param string $packageName
     * @param ?string $path
     * @return void
     */
    private function removeEmptyDirectories($packageName, $path)
    {
        if ($path === '' || $path === null) {
            return;
        }

        $directory = new Directory();
        $directory->addPath($path);
        $directories = $directory->getDirectories();
        rsort($directories);

        foreach ($directories as $directory) {
            $filepath = $path . $directory;
            if (!$this->filesystem->isEmptyDirectory($filepath)) {
                continue;
            }

            $this->filesystem->removeDirectory($filepath);

            $this->io->write(
                "Composer vendor cleaner: Empty directory '<info>{$directory}</info>' from package <info>{$packageName}</info> removed",
                true,
                IOInterface::VERBOSE
            );
            $this->removedDirectories++;
            $this->removedEmptyDirectories++;
        }

        if ($this->filesystem->isEmptyDirectory($path)) {
            $this->filesystem->removeDirectory($path);

            $directory = basename($path);
            $this->io->write(
                "Composer vendor cleaner: Empty directory '<info>{$directory}</info>' from package <info>{$packageName}</info> removed",
                true,
                IOInterface::VERBOSE
            );
            $this->removedDirectories++;
            $this->removedEmptyDirectories++;
        }
    }

    /**
     * @param string $packageName
     * @param ?string $rootDir
     * @param string[] $filesToRemove
     * @return void
     */
    private function removeFiles($packageName, $rootDir, $filesToRemove)
    {
        foreach ($filesToRemove as $fileToRemove) {
            $filepath = (string)$rootDir . $fileToRemove;
            if (is_dir($filepath)) {
                if (!$this->filesystem->isEmptyDirectory($filepath)) {
                    $this->io->write(
                        "Composer vendor cleaner: Directory '<info>{$fileToRemove}</info>' from package <info>{$packageName}</info> not removed, because isn't empty",
                        true,
                        IOInterface::VERBOSE
                    );
                    continue;
                }

                $this->filesystem->removeDirectory($filepath);

                $this->io->write(
                    "Composer vendor cleaner: Directory '<info>{$fileToRemove}</info>' from package <info>{$packageName}</info> removed",
                    true,
                    IOInterface::VERBOSE
                );
                $this->removedDirectories++;
            } else {
                $this->filesystem->removeFile($filepath);

                $this->removedFiles++;
                $this->io->write(
                    "Composer vendor cleaner: File '<info>{$fileToRemove}</info>' from package <info>{$packageName}</info> removed",
                    true,
                    IOInterface::VERBOSE
                );
            }
        }
    }

    /**
     * @param ?string $path
     * @return string[]
     */
    private function getDirectoryEntries($path)
    {
        if ($path === '' || $path === null) {
            return [];
        }

        $directory = new Directory();
        $directory->addPath($path);

        return $directory->getEntries();
    }

}
