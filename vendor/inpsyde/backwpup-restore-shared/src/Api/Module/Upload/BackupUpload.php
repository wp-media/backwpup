<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Upload;

use Inpsyde\Restore\Api\Exception\FileSystemException;
use Inpsyde\Restore\Api\Module\Registry;

/**
 * Class Upload. Responsible for retrieving a file and save it into upload folder.
 *
 * @author  Hans-Helge Buerger
 *
 * @since   1.0.0
 *
 * @psalm-type PathInfo=array{
 *     dirname: string,
 *     basename: string,
 *     filename: string,
 *     extension?: string
 * }
 */
final class BackupUpload implements FileUploadInterface
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
     * @var string file name of uploaded backup archive
     */
    private $file_name;

    /**
     * @var string|null absolute path to upload folder
     */
    private $upload_folder;

    /**
     * @var int number of current chunk, which is uploaded
     */
    private $current_chunk;

    /**
     * @var int total number of chunks
     */
    private $total_chunks;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * BackupUpload constructor.
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function run(): void
    {
        $tmp_file_name = $_FILES['file']['tmp_name'];
        if (!is_uploaded_file($tmp_file_name)) {
            throw new UploadException(
                __('Failed to move uploaded file.', 'backwpup')
            );
        }

        if (!$this->files_var_exists()) {
            throw new UploadException(__('Failed to move uploaded file.', 'backwpup'));
        }

        $chunk = isset($_REQUEST['chunk'])
            ? filter_var($_REQUEST['chunk'], FILTER_SANITIZE_NUMBER_INT)
            : 0;
        $chunks = isset($_REQUEST['chunks'])
            ? filter_var($_REQUEST['chunks'], FILTER_SANITIZE_NUMBER_INT)
            : 0;

        $this->set_current_chunk((int) $chunk);
        $this->set_total_chunks((int) $chunks);

        $file_name = $_REQUEST['name'] ?? $_FILES['file']['name'] ?? '';

        if (!$file_name) {
            throw new UploadException(
                __('No File Name Found. Cannot upload.', 'backwpup')
            );
        }

        $this->set_file_name($file_name);

        $file_path = $this->get_abs_file_path();

        // Open temp file
        $out = $this->open_file($file_path);
        if (!\is_resource($out)) {
            throw new FileSystemException(
                __('Failed to open output stream during upload.', 'backwpup')
            );
        }

        // Read binary input stream and append it to temp file
        $in = @fopen($tmp_file_name, 'r');
        if (!$in) {
            throw new FileSystemException(
                __('Failed to open input stream during upload.', 'backwpup')
            );
        }

        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }

        fclose($in);
        fclose($out);

        @unlink($tmp_file_name);

        // Check if file has been uploaded
        if ($this->upload_finished()) {
            // Strip the temp .part suffix off
            rename("{$file_path}.part", $file_path);

            $this->registry->delete('file_prefix');
        }
    }

    public function get_abs_file_path(): string
    {
        return $this->get_upload_folder() . '/' . $this->get_file_name();
    }

    public static function upload_is_archive(string $path): bool
    {
        return self::upload_is_type(self::$supported_archives, pathinfo($path));
    }

    public static function upload_is_sql(string $path): bool
    {
        return self::upload_is_type(['sql'], pathinfo($path));
    }

    /**
     * Method checks if file available in request.
     */
    private function files_var_exists(): bool
    {
        return !(
            empty($_FILES)
            || !isset($_FILES['file'])
            || (isset($_FILES['file']['error']) && $_FILES['file']['error'])
        );
    }

    /**
     * Helper function which opens the file stream for uploading.
     *
     * This helper function has only one purpose up to now: Testing.
     * Excluding this part from $this->run() makes it easier to test.
     *
     * @param string $file_path Path of file to upload
     *
     * @return resource|bool Returns a file pointer resource on success, or FALSE on error
     */
    private function open_file(string $file_path)
    {
        return fopen("{$file_path}.part", $this->get_current_chunk() === 0 ? 'wb' : 'ab');
    }

    /**
     * Method to check if current upload was the last one and upload is finish.
     */
    private function upload_finished(): bool
    {
        return $this->get_total_chunks() === 0 || $this->get_current_chunk() === $this->get_total_chunks() - 1;
    }

    private function get_upload_folder(): string
    {
        if ($this->upload_folder === null) {
            $this->upload_folder = $this->registry->uploads_folder;

            // Delete if it's a file.
            if (is_file($this->upload_folder)) {
                unlink($this->upload_folder);
            }

            if (!file_exists($this->upload_folder) && !is_dir($this->upload_folder)) {
                mkdir($this->upload_folder);
            }
        }

        return $this->upload_folder;
    }

    private function get_current_chunk(): int
    {
        return $this->current_chunk;
    }

    private function get_total_chunks(): int
    {
        return $this->total_chunks;
    }

    private function set_current_chunk(int $current_chunk): void
    {
        $this->current_chunk = $current_chunk;
    }

    private function set_total_chunks(int $total_chunks): void
    {
        $this->total_chunks = $total_chunks;
    }

    private function get_file_name(): string
    {
        return $this->file_name;
    }

    /**
     * Sets the file name, with a random prefix.
     *
     * The random prefix is added so a malicious file cannot be accessed by the user.
     */
    private function set_file_name(string $file_name): void
    {
        if (!$this->registry->has('file_prefix')) {
            $this->registry->file_prefix = bin2hex(openssl_random_pseudo_bytes(4));
        }

        $this->file_name = $this->sanitize_file_path("{$this->registry->file_prefix}-{$file_name}");
    }

    /**
     * Sanitize File Path.
     *
     * @param string $path The file path
     *
     * @return string The sanitized filename
     */
    private function sanitize_file_path(string $path): string
    {
        $filename = basename($path);
        $path = str_replace($filename, '', $path);

        // Trailing slash it.
        if ($path !== '') {
            $path = rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
        }

        // Clean the filename.
        $filename = trim(preg_replace('/[^a-zA-Z0-9\/\-\_\.]+/', '', $filename) ?? '');

        while (strpos($filename, '..') !== false) {
            $filename = str_replace('..', '', $filename);
        }
        $filename = ($filename !== '/') ? $filename : '';

        return $path . $filename;
    }

    /**
     * Check if uploaded file extension is in provided array and hence of type.
     *
     * @param array<string> $extensions File types to check for
     * @param PathInfo      $path_parts Pathinfos
     */
    private static function upload_is_type(array $extensions, array $path_parts): bool
    {
        if (!isset($path_parts['extension'])) {
            return false;
        }

        return \in_array($path_parts['extension'], $extensions, true);
    }
}
