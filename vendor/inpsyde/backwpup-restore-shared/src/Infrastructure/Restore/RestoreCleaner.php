<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Infrastructure\Restore;

use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Cleans up the restore working directory after a successful restore.
 *
 * Deletes the known sensitive and temporary files left in `project_temp` after
 * `Registry::reset_registry()` has run. Never deletes `project_temp` itself.
 * All filesystem errors are caught, logged, and never rethrown so that the
 * restore success response is unaffected by cleanup failures.
 */
class RestoreCleaner
{
    /** @var string */
    private $projectTemp;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param string          $projectTemp Absolute path to the restore working directory.
     * @param LoggerInterface $logger      PSR-3 logger for error reporting.
     */
    public function __construct(string $projectTemp, LoggerInterface $logger)
    {
        $this->projectTemp = $projectTemp;
        $this->logger = $logger;
    }

    /**
     * Delete the known restore working-directory targets.
     *
     * Targets:
     *   - {projectTemp}/uploads  (directory, recursive)
     *   - {projectTemp}/extract  (directory, recursive)
     *   - {projectTemp}/restore.dat
     *   - {projectTemp}/restore.dat.bkp
     *   - {projectTemp}/restore.log
     *
     * Each target is guarded by an existence check before deletion.
     * `$projectTemp` itself is never deleted.
     */
    public function cleanup(): void // phpcs:ignore
    {
        if ($this->projectTemp === '') {
            $this->logger->warning('BackWPup restore cleanup skipped: project_temp path is empty.');
            return;
        }

        $this->logger->info(
            'BackWPup restore cleanup started.',
            ['project_temp' => $this->projectTemp]
        );

        foreach ([$this->projectTemp . '/uploads', $this->projectTemp . '/extract'] as $dir) {
            try {
                if (!is_dir($dir)) {
                    $this->logger->debug(
                        'BackWPup restore cleanup: directory not found, skipping.',
                        ['path' => $dir]
                    );
                    continue;
                }
                $this->logger->info(
                    'BackWPup restore cleanup: deleting directory.',
                    ['path' => $dir]
                );
                $this->deleteDir($dir);
                $this->logger->info(
                    'BackWPup restore cleanup: directory deleted.',
                    ['path' => $dir]
                );
            } catch (\Throwable $e) {
                $this->logger->error(
                    'BackWPup restore cleanup: failed to delete directory: ' . $e->getMessage(),
                    ['path' => $dir, 'exception' => $e]
                );
            }
        }

        foreach (
            [
            $this->projectTemp . '/restore.dat',
            $this->projectTemp . '/restore.dat.bkp',
            $this->projectTemp . '/restore.log',
            ] as $file
        ) {
            try {
                if (!file_exists($file)) {
                    $this->logger->debug(
                        'BackWPup restore cleanup: file not found, skipping.',
                        ['path' => $file]
                    );
                    continue;
                }
                $this->logger->info('BackWPup restore cleanup: deleting file.', ['path' => $file]);
                if (!unlink($file)) { // phpcs:ignore
                    $this->logger->error(
                        'BackWPup restore cleanup: failed to delete file.',
                        ['path' => $file]
                    );
                    continue;
                }
                $this->logger->info('BackWPup restore cleanup: file deleted.', ['path' => $file]);
            } catch (\Throwable $e) {
                $this->logger->error(
                    'BackWPup restore cleanup: failed to delete file: ' . $e->getMessage(),
                    ['path' => $file, 'exception' => $e]
                );
            }
        }

        $this->logger->info('BackWPup restore cleanup finished.');
    }

    // phpcs:ignore
    private function deleteDir(string $dir): void
    {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            $path = $file->getRealPath();

            if ($path === false) {
                $this->logger->warning(
                    'BackWPup restore cleanup: could not resolve real path, skipping entry.',
                    ['pathname' => $file->getPathname()]
                );
                continue;
            }

            if ($file->isDir()) {
                if (!rmdir($path)) { // phpcs:ignore
                    $this->logger->warning(
                        'BackWPup restore cleanup: failed to remove subdirectory.',
                        ['path' => $path]
                    );
                }
                continue;
            }

            if (!unlink($path)) { // phpcs:ignore
                $this->logger->warning(
                    'BackWPup restore cleanup: failed to delete file inside directory.',
                    ['path' => $path]
                );
            }
        }

        if (!rmdir($dir)) { // phpcs:ignore
            $this->logger->error(
                'BackWPup restore cleanup: failed to remove root directory.',
                ['path' => $dir]
            );
        }
    }
}
