<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Restore;

use Inpsyde\Restore\AjaxHandler;
use Inpsyde\Restore\Api\Exception\ExceptionLinkHelper;
use Inpsyde\Restore\Api\Exception\FileSystemException;
use Inpsyde\Restore\Api\Module\Registry;
use Inpsyde\Restore\Api\Module\Restore\Exception\ConfigFileException;
use Inpsyde\Restore\Api\Module\Restore\Exception\ConfigFileNotFoundException;
use Inpsyde\Restore\Api\Module\Restore\Exception\RestorePathException;
use Inpsyde\Restore\Infrastructure\EventSourceTrait;
use Psr\Log\LoggerInterface;

/**
 * Class RestoreFiles.
 */
final class RestoreFiles implements ConfigRewriterInterface, RestoreInterface
{
    use EventSourceTrait;

    /**
     * @var array<string>
     */
    private static $ignore_files_directories = [
        '.',
        'manifest.json',
        '..',
        'backwpup_readme.txt',
        'restore',
        'restore_temp',
    ];

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Archive Path Length.
     *
     * It's used to remember the current archive path length.
     *
     * @var int<0, max> The length of the archive path
     */
    private $current_archive_extracted_path_length = 0;

    /**
     * Context.
     *
     * The context in which the instance operates. Default is `event_source` means
     * the instance is used in a EventSource request.
     *
     * @var string
     */
    private $context = AjaxHandler::EVENT_SOURCE_CONTEXT;

    public function __construct(
        Registry $registry,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
    }

    public function restore(): int
    {
        $errors = 0;

        do {
            // Ignore extra files.
            $extra_ignored_files = $this->registry->extra_files;
            $ignore = array_merge(self::$ignore_files_directories, $extra_ignored_files);

            // Set archive path and length used during copy files.
            $archive_extracted_path = $this->registry->extract_folder;
            $this->current_archive_extracted_path_length = \strlen($archive_extracted_path);

            // The next directory to restore.
            $next_dir = $this->registry->next_dir_in_restore_list();
            $archive_extracted_path = $this->append_path($archive_extracted_path, $next_dir);

            // Create the path where the files must be restored.
            $restore_path = $this->registry->project_root ?? '';
            $restore_path = $this->append_path($restore_path, $next_dir);

            if (!$archive_extracted_path || !$restore_path) {
                throw new RestorePathException(
                    ExceptionLinkHelper::translateWithAppropiatedLink(
                        sprintf(
                            __(
                                'Archive Path and/or Restore Path is not set; Archive Path: %1$s; Restore Path: %2$s',
                                'backwpup'
                            ),
                            $archive_extracted_path ?: '(empty string)',
                            $restore_path ?: '(empty string)'
                        ),
                        'ARCHIVE_RESTORE_PATH_CANNOT_BE_SET'
                    )
                );
            }

            $this->logger->info(
                sprintf(
                    __('Restoring: %1$s', 'backwpup'),
                    $archive_extracted_path
                )
            );

            try {
                // Copy all files that are within the archive extracted path.
                $this->copy_files($archive_extracted_path, $restore_path, $ignore);
            } catch (FileSystemException $exc) {
                $this->logger->error($exc->getMessage());
                ++$errors;
            }
        } while (!$this->file_restore_done());

        return $errors;
    }

    public function rewriteConfig(): void
    {
        $configPath = $this->registry->extract_folder . DIRECTORY_SEPARATOR . 'wp-config.php';
        $this->logger->info(
            sprintf(
                __('Attempting to rewrite config file at %s', 'backwpup'),
                $configPath
            )
        );

        if (!file_exists($configPath)) {
            throw new ConfigFileNotFoundException(
                sprintf(
                    __('Config file not found at %s', 'backwpup'),
                    $configPath
                )
            );
        }
        if (!is_writable($configPath)) {
            throw new FileSystemException(
                sprintf(
                    __('Config file not writable at %s', 'backwpup'),
                    $configPath
                )
            );
        }

        // See if we can replace all credentials.
        $credentials = [
            'DB_NAME' => $this->registry->dbname,
            'DB_USER' => $this->registry->dbuser,
            'DB_PASSWORD' => $this->registry->dbpassword,
            'DB_HOST' => $this->registry->dbhost,
            'DB_CHARSET' => $this->registry->dbcharset,
        ];
        array_walk($credentials, static function ($value, $key): void {
            if ($key !== 'DB_CHARSET' && empty($value)) {
                throw new ConfigFileException(
                    sprintf(
                        __('No value found for %s', 'backwpup'),
                        $key
                    )
                );
            }
        });

        $config = file_get_contents($configPath) ?: '';

        foreach ($credentials as $key => $value) {
            // It's OK if charset is not set.
            // But continue without replacing it in that case.
            if (empty($value)) {
                continue;
            }

            $config = preg_replace(
                '/(define\s*\(\s*([\'"])' . preg_quote($key, '/') . '\2\s*,\s*([\'"])).*?(\3\s*\)\s*;)/i',
                '${1}' . $value . '${4}',
                (string) $config,
                -1,
                $count
            );
            if ($count === 0) {
                throw new ConfigFileException(
                    sprintf(
                        __('Could not replace value of %s', 'backwpup'),
                        $key
                    )
                );
            }
        }

        file_put_contents($configPath, $config);

        $this->logger->info(
            __('Config has been rewritten successfully.', 'backwpup')
        );
    }

    /**
     * Appends `$next` to `$path` as a valid path.
     */
    private function append_path(string $path, string $next): string
    {
        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim(
            $next,
            DIRECTORY_SEPARATOR
        );
    }

    /**
     * Helper function to determine type of given file.
     *
     * @param string        $file   File in source path
     * @param array<string> $ignore Array of files to ignore
     *
     * @return string File type
     */
    private function file_type(string $file, array $ignore): string
    {
        if (\in_array(basename($file), $ignore, true)) {
            return 'ignore';
        }

        if (is_link($file)) {
            return 'link';
        }

        if (is_dir($file)) {
            return 'dir';
        }

        if (is_file($file)) {
            return 'file';
        }

        return 'unknown';
    }

    /**
     * Check if more directories are available to restore.
     */
    private function file_restore_done(): bool
    {
        // More to do if there are files left,
        // or if a file is currently being restored,
        // or if a file is being skipped
        return !(
            !empty($this->registry->restore_list)
            || $this->registry->has('restore_file_start_from')
            || $this->registry->has('restore_file_skip')
        );
    }

    /**
     * Handles actual copy operation of files to the restore path.
     *
     * @param array<string> $ignore
     *
     * @throws FileSystemException
     */
    private function copy_files(
        string $source,
        string $dest,
        array $ignore = ['.', '..'],
        bool $del = false,
        int $perm = 0755
    ): void {
        // Get files in directory
        $files = scandir($source);
        // If directory cannot be open, log and return.
        if ($files === false) {
            throw new FileSystemException(
                sprintf(
                    __('The directory %1$s cannot be open. Skip this one.', 'backwpup'),
                    $source
                )
            );
        }

        // 1. Create dir in destination if it does not exists
        if (!is_dir($dest)) {
            // phpcs:ignore
            mkdir($dest, $perm);
        }

        // phpcs:ignore
        foreach ($files as $file) {
            // Set the source file.
            $src_file = $this->append_path($source, $file);

            // Looking for the last file copied if the previous
            // request failed because of a time limit or something else.
            // The `restore_file_start_from` act as a control variable.
            if (
                $this->registry->has('restore_file_start_from')
                && $src_file !== $this->registry->restore_file_start_from
            ) {
                continue;
            }
            $this->registry->delete('restore_file_start_from');

            // Check if we have to skip a file that errored
            if ($this->registry->has('restore_file_skip')) {
                if ($src_file === $this->registry->restore_file_skip) {
                    $this->registry->delete('restore_file_skip');
                }

                continue;
            }

            switch ($this->file_type($src_file, $ignore)) {
                case 'file':
                    // Store the current file, in case we need it for the next request.
                    $this->registry->restore_file_start_from = $src_file;

                    // Set the destination.
                    $destinationAbsoluteFilePath = $this->append_path($dest, $file);

                    if (!is_writable($dest)) {
                        $this->registry->delete('restore_file_start_from');

                        throw new FileSystemException(
                            sprintf(
                                __(
                                    'File %s cannot be restored because it is not writable or the directory doesn\'t have the right permissions',
                                    'backwpup'
                                ),
                                $destinationAbsoluteFilePath
                            )
                        );
                    }

                    // 2.a. Restore files in destination path
                    $result = @copy($src_file, $destinationAbsoluteFilePath);
                    // If restore wasn't performed, let's add a line of debug, so we can know which files cannot be copied.
                    if (!$result) {
                        // Skip this file on the next iteration
                        $this->registry->restore_file_skip = $src_file;
                        $this->registry->delete('restore_file_start_from');

                        throw new FileSystemException(
                            sprintf(
                                __('Failed to restore file %1$s.', 'backwpup'),
                                $src_file
                            )
                        );
                    }

                    if ($this->context === AjaxHandler::EVENT_SOURCE_CONTEXT) {
                        $this->echoEventData(
                            'message',
                            [
                                'message' => $src_file,
                                'state' => 'progress',
                            ]
                        );
                    }

                    // phpcs:ignore
                    if ($del) {
                        unlink($src_file);
                    }

                    // If the file has been copied correctly, we can delete it.
                    $this->registry->delete('restore_file_start_from');
                    break;

                case 'dir':
                    // 2.b. Put subdirs in Registry::restore_list for next steps
                    $folder = substr($src_file, $this->current_archive_extracted_path_length);
                    $this->registry->add_to_restore_list($folder);
                    break;

                default:
                    break;
            }// endswitch
        }// endforeach
    }
}
