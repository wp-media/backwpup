<?php

declare(strict_types=1);

/*
 * This file is part of the BackWPup Restore Shared package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Restore\Api\Module\Decompress;

use Archive_Tar;
use Exception;
use Inpsyde\BackWPup\Archiver\CurrentExtractInfo;
use Inpsyde\BackWPup\Archiver\Extractor;
use Inpsyde\Restore\AjaxHandler;
use Inpsyde\Restore\Api\Exception\ExceptionLinkHelper;
use Inpsyde\Restore\Api\Exception\FileSystemException;
use Inpsyde\Restore\Api\Module\Decompress\Exception\DecompressException;
use Inpsyde\Restore\Api\Module\Registry;
use InvalidArgumentException;
use OutOfBoundsException;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use UnexpectedValueException;

/**
 * @psalm-type TarFile=array{filename: string, mode: int, uid: int, guid: int, size: int, mtime: int, typeflag: string, link: string, checksum: int}
 */
class Decompressor
{
    /**
     * Supported archive extensions.
     *
     * @var array<string> The extension of the supported archives
     */
    private static $supported_archives = [
        'zip',
        'tar',
        'gz',
        'bz2',
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
     * @var string The path of the file to extract
     */
    private $file_path;

    /**
     * Context.
     *
     * The context in which the instance operates. Default is `event_source` means
     * the instance is used in a EventSource request.
     *
     * @var string
     */
    private $context = AjaxHandler::EVENT_SOURCE_CONTEXT;

    /**
     * @var Extractor
     */
    private $extractor;

    /**
     * @var State
     */
    private $decompressionState;

    /**
     * @var StateUpdater
     */
    private $decompressionStateUpdater;

    public function __construct(
        Registry $registry,
        LoggerInterface $logger,
        Extractor $extractor,
        State $decompressionState,
        StateUpdater $decompressionStateUpdater
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        // TODO May be can be retrieved by the Decompress\State. See where this is used.
        $this->file_path = $this->registry->uploaded_file;
        $this->extractor = $extractor;
        $this->decompressionState = $decompressionState;
        $this->decompressionStateUpdater = $decompressionStateUpdater;
    }

    /**
     * Run.
     *
     * Checks for the extraction/decompressing requirements
     * and attempts extracting the archive to the destination.
     *
     * @throws DecompressException
     * @throws RuntimeException
     * @throws UnexpectedValueException
     */
    public function run(): void
    {
        // Check if it's possible to decompress the archive.
        $this->check_if_can_decompressed();

        // Clean previously created files. May be user is trying to perform the decompression again.
        // Only the first time.
        if ($this->registry->decompression_state === null) {
            $this->clean_old_decompressed_files();
        }

        $this->extract(
            pathinfo($this->file_path, PATHINFO_EXTENSION)
        );
    }

    public function set_file_path(string $file_path): void
    {
        $this->file_path = $file_path;
    }

    public function get_file_path(): string
    {
        return $this->file_path;
    }

    public function get_extract_folder(): string
    {
        return $this->registry->extract_folder;
    }

    /**
     * Set the permission of the parent folder.
     *
     * @throws FileSystemException
     * @throws InvalidArgumentException
     *
     * @return bool True on success, false otherwise
     */
    public function try_set_parent_decompress_dir_permissions(): bool
    {
        // phpcs:ignore
        $response = chmod(\dirname($this->registry->extract_folder), 0755);

        if (!$response) {
            throw new FileSystemException(
                ExceptionLinkHelper::translateWithAppropiatedLink(
                    sprintf(
                        __('Impossible to set permissions for parent directory %s.', 'backwpup'),
                        $this->registry->extract_folder
                    ),
                    'DIR_CANNOT_BE_CREATED'
                )
            );
        }

        return $response;
    }

    /**
     * Create Decompress Directory.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function create_extract_folder(): void
    {
        $created = mkdir($this->registry->extract_folder, 0755); // phpcs:ignore

        if (!$created) {
            throw new RuntimeException(
                ExceptionLinkHelper::translateWithAppropiatedLink(
                    __(
                        'Destination directory does not exist and is not possible to create it.',
                        'backwpup'
                    ),
                    'DIR_CANNOT_BE_CREATED'
                )
            );
        }
    }

    /**
     * Clean Old Decompressed Files.
     *
     * @throws UnexpectedValueException
     *
     * @return bool true on success, false in case the dir is not readable or not a directory
     */
    private function clean_old_decompressed_files(): bool
    {
        // If current directory is not a directory or empty don't do anything else.
        if (
            !is_readable($this->registry->extract_folder)
            || !is_dir($this->registry->extract_folder)
        ) {
            return false;
        }

        $it = new RecursiveDirectoryIterator(
            $this->registry->extract_folder,
            RecursiveDirectoryIterator::SKIP_DOTS
        );
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

        // Clean files.
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath()); // phpcs:ignore

                continue;
            }

            unlink($file->getRealPath()); // phpcs:ignore
        }

        return true;
    }

    /**
     * The default extractor that decompresses zip files.
     *
     * @throws RuntimeException         in case somethings goes wrong during extraction
     * @throws InvalidArgumentException
     */
    private function zip_extractor(): void
    {
        $errors = 0;
        $context = $this->context;
        $decompressionState = $this->decompressionState;
        $decompressionStateUpdater = $this->decompressionStateUpdater;
        $currentIndex = $decompressionState->index();

        try {
            $this->extractor->extractByOffset(
                $this->file_path,
                $this->registry->extract_folder,
                ++$currentIndex,
                static function (CurrentExtractInfo $info) use ($decompressionStateUpdater, $context): void {
                    $decompressionStateUpdater->updateStatus($info);
                    // Only for event source calls.
                    if ($context === AjaxHandler::EVENT_SOURCE_CONTEXT) {
                        echo "event: message\n";
                        printf("data: %s\n\n", wp_json_encode($info) ?: '');
                        flush();
                    }
                }
            );
        } catch (Exception $exc) {
            $this->logger->error($exc->getMessage());
            ++$errors;
        }

        $decompressionStateUpdater->clean();

        if ($errors) {
            throw new RuntimeException(
                __(
                    'Extracted with errors. Please, see the log for more information.',
                    'backwpup'
                )
            );
        }
    }

    /**
     * Extract Tar by index.
     *
     * @param Archive_Tar    $tar        The archive tar instance to use to extract the file
     * @param array<TarFile> $content    The content of the archive
     * @param int            $filesCount The total amount of files within the archive
     * @param int            $index      The index of the file to extract
     *
     * @throws OutOfBoundsException if the index doesn't exists within the tar archive
     * @throws DecompressException  in case the file cannot be decompressed
     * @throws Exception            if the registry cannot be saved
     */
    private function tar_extractor_by_index(Archive_Tar $tar, array $content, int $filesCount, int $index): CurrentExtractInfo
    {
        if (!isset($content[$index])) {
            throw new OutOfBoundsException(
                sprintf(
                    __('Impossible to extract file at index %d. Index does not exists', 'backwpup'),
                    $index
                )
            );
        }

        // Get the name of the file we want to extract.
        $fileName = $content[$index]['filename'];
        // If it's not possible to extract the file, log the file name.
        if (!$tar->extractList([$fileName], $this->registry->extract_folder)) {
            throw new DecompressException(
                sprintf(
                    __('Decompress %s failed. You need to copy the file manually.', 'backwpup'),
                    $fileName
                )
            );
        }

        $data = new CurrentExtractInfo(
            $filesCount,
            $index,
            $fileName,
            $this->registry->extract_folder
        );

        $this->decompressionStateUpdater->updateStatus($data);

        return $data;
    }

    /**
     * Tar Extractor.
     *
     * Decompresses tar files with gz and bz compressors
     *
     * @throws RuntimeException
     */
    private function tar_extractor(): void
    {
        $errors = 0;
        $tar = new Archive_Tar($this->file_path);
        $content = $tar->listContent();
        if (!\is_array($content)) {
            throw new RuntimeException(
                __('Could not extract the archive', 'backwpup')
            );
        }
        $filesCount = \count($content);
        $currentIndex = $this->decompressionState->index() + 1;

        for (; $currentIndex < $filesCount; ++$currentIndex) {
            try {
                $data = $this->tar_extractor_by_index($tar, $content, $filesCount, $currentIndex);

                if ($this->context === AjaxHandler::EVENT_SOURCE_CONTEXT) {
                    echo "event: message\n";
                    printf("data: %s\n\n", wp_json_encode($data) ?: '');
                    flush();
                }
            } catch (Exception $exc) {
                $this->logger->error($exc->getMessage());
                ++$errors;
            }
        }

        // Clean the registry. So we allow to upload and decompress a new archive.
        $this->decompressionStateUpdater->clean();

        if ($errors !== 0) {
            throw new RuntimeException(
                __(
                    'Extracted with error. Please, see the log for more information.',
                    'backwpup'
                )
            );
        }
    }

    /**
     * Check if the decompression can be performed.
     *
     * @throws RuntimeException
     */
    private function check_if_can_decompressed(): void
    {
        $file_ext = pathinfo($this->file_path, PATHINFO_EXTENSION);

        // Nothing to do if the file extension is not permitted.
        if (!$file_ext || !\in_array($file_ext, self::$supported_archives, true)) {
            throw new DecompressException(
                sprintf(
                    __('File .%s type not supported.', 'backwpup'),
                    ltrim($file_ext, '.')
                )
            );
        }

        // If file doesn't exists, we cannot perform any decompression.
        if (!file_exists($this->file_path)) {
            throw new FileSystemException(
                __('File does not exist or access is denied.', 'backwpup')
            );
        }

        $response = $this->create_extract_folder_if_not_exists();
        if ($response !== '') {
            throw new FileSystemException($response);
        }

        // Seems chmod may return a false positive in some situations.
        // @see http://php.net/manual/en/function.chmod.php for more info.
        chmod($this->registry->extract_folder, 0755); // phpcs:ignore

        if (!is_writable($this->registry->extract_folder)) { // phpcs:ignore
            throw new FileSystemException(
                ExceptionLinkHelper::translateWithAppropiatedLink(
                    sprintf(
                        __(
                            'Destination %s is not writable and is not possible to correct the permissions. Please double check it.',
                            'backwpup'
                        ),
                        $this->registry->extract_folder
                    ),
                    'DIR_CANNOT_BE_CREATED'
                )
            );
        }
    }

    /**
     * Extracts the archive to the destination according to the extension of the archive.
     *
     * @param string $ext Extension of archive
     *
     * @throws RuntimeException
     * @throws DecompressException      In case the backup file is a .bzip one
     * @throws InvalidArgumentException
     */
    private function extract(string $ext): void
    {
        switch ($ext) {
            case 'tar':
            case 'gz':
                $this->tar_extractor();
                break;

            case 'bz2':
                throw new DecompressException(
                    ExceptionLinkHelper::translateWithAppropiatedLink(
                        __(
                            'Sorry but bzip2 backups cannot be restored. You must convert the file to a .zip one in order to able to restore your backup.',
                            'backwpup'
                        ),
                        'BZIP2_CANNOT_BE_DECOMPRESSED'
                    )
                );

            case 'zip':
            default:
                $this->zip_extractor();
                break;
        }

        // Store the manifest json file path.
        $this->registry->manifest_file = $this->registry->extract_folder . '/manifest.json';
    }

    /**
     * Set Error Handler for Decompression directory.
     *
     * Try to set the parent directory permissions and recreate the decompress directory.
     * Log if fails.
     *
     * The error handler is removed at the beginning of the function to prevent possible loops.
     *
     * @uses set_error_handler() To set the error handler.
     */
    private function set_error_handler_for_decompression_directory(): void
    {
        // Old php versions.
        $self = $this;
        $logger = $this->logger;

        // `mkdir` emit a `E_WARNING` in case it's not possible to create the directory.
        set_error_handler(
            static function () use ($self, $logger): bool {
                // Restore the previous handler and return, avoid possible loops.
                restore_error_handler();

                $logger->warning(
                    __(
                        'Error during create decompression directory, trying to set permissions for parent directory.',
                        'backwpup'
                    )
                );
                $self->try_set_parent_decompress_dir_permissions();
                $self->create_extract_folder();

                // Try to run the process again since we have successfully created the decompress directory.
                $self->run();

                return false;
            },
            E_WARNING
        );
    }

    /**
     * Create the Decompress Directory if it does not exist.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException         In case it isn't possible to create the directory
     *
     * @return string The JSON response if the directory cannot be created for a reason, empty string otherwise
     */
    private function create_extract_folder_if_not_exists(): string
    {
        $msg = '';

        // Not Directory? Try to remove it.
        if (
            file_exists($this->registry->extract_folder)
            && !is_dir($this->registry->extract_folder)
            && !unlink($this->registry->extract_folder)
        ) {
            $msg = sprintf(
                'Invalid destination %s. Not a valid directory.',
                $this->registry->extract_folder
            );
        }

        // If directory doesn't exists, try to create it.
        if (!file_exists($this->registry->extract_folder)) {
            $this->set_error_handler_for_decompression_directory();
            $this->create_extract_folder();

            // Because we cannot know if the error handler has been called or not.
            restore_error_handler();
        }

        return $msg;
    }
}
