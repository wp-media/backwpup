<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Upload;

/**
 * Interface FileUploadInterface.
 */
interface FileUploadInterface
{
    /**
     * This method is called to start an upload of a backup file.
     *
     * @throws \Exception in case isn't possible to upload the file
     */
    public function run(): void;

    /**
     * Method return absolute path to upload file.
     *
     * @return string Absolute path to upload file
     */
    public function get_abs_file_path(): string;

    /**
     * Helper function to check if uploaded file is an archive.
     *
     * @param string $path Absolute path to uploaded file
     */
    public static function upload_is_archive(string $path): bool;

    /**
     * Helper function to check if uploaded file is a sql file.
     *
     * @param string $path Absolute path to uploaded file
     */
    public static function upload_is_sql(string $path): bool;
}
