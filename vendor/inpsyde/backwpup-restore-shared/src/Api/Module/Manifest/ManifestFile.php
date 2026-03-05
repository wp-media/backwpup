<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Manifest;

use Inpsyde\Restore\Api\Module\Manifest\Exception\ManifestFileException;
use Inpsyde\Restore\Api\Module\Registry;

/**
 * ManifestFile Class for handling operations regarding manifest.json in backups.
 */
class ManifestFile
{
    /**
     * @var \stdClass|null content of manifest.json decoded from json into php array
     */
    private $manifest;

    /**
     * @var Registry object holding general information for the app
     */
    private $registry;

    /**
     * @param Registry $registry Object holding general information for the app
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Setter method to set manifest file from extracted backup.
     *
     * @param string $file Path to manifest file
     *
     * @throws ManifestFileException If file is not readable
     */
    public function set_manifest_file(string $file): void
    {
        if (!is_readable($file)) {
            throw new ManifestFileException(__('Manifest file not readable', 'backwpup'));
        }

        $manifest = json_decode(file_get_contents($file) ?: '');
        if (!$manifest instanceof \stdClass) {
            throw new ManifestFileException(__('The manifest file is not formatted properly', 'backwpup'));
        }

        $this->manifest = $manifest;
    }

    /**
     * Fetch dumpfile from manifest file.
     *
     * @throws ManifestFileException If the manifest is not a valid object
     *
     * @return string The file name or empty string
     */
    public function get_dump_file(): string
    {
        if ($this->manifest === null) {
            throw new ManifestFileException(
                __(
                    'Manifest file not found. Please check the file exists within the backup and extraction folder.',
                    'backwpup'
                )
            );
        }

        // Some job settings may use an invalid dump extension such as "mysqldump".
        // So, if we found the `sql` substring in the database dump type we assue the extension is `.sql`.
        $dump_ext = $this->manifest->job_settings->dbdumptype;
        if (strpos($this->manifest->job_settings->dbdumptype, 'sql') !== false) {
            $dump_ext = 'sql';
        }

        $dump_name = $this->manifest->job_settings->dbdumpfile;
        $dump_comp = $this->manifest->job_settings->dbdumpfilecompression;

        if ($dump_ext === 'xml') {
            return '';
        }

        return $dump_name . '.' . $dump_ext . $dump_comp;
    }

    /**
     * Helper method for finding charset, which is used in Manifest.
     *
     * @throws ManifestFileException If it's not possible to retrieve the database dump file
     *
     * @return string DB Charset from job_settings or empty string if not set
     */
    public function get_charset(): string
    {
        // Firstly look if charset is set in manifest.json.
        // If that fails try to find the charset in SQL dump file.
        // Each MySQL dump should contain a comment, which states the charset.
        if (!empty($this->manifest->job_settings->dbdumpdbcharset)) {
            return $this->manifest->job_settings->dbdumpdbcharset;
        }

        $file = $this->registry->dbdumpfile;

        if (empty($file)) {
            throw new ManifestFileException(__('No DB Dump File found in Registry.', 'backwpup'));
        }

        // Fetch first 1000 chars of sql dump and look for 'SET NAMES'
        $content = file_get_contents($file, false, null, 0, 1000) ?: ''; // phpcs:ignore
        $start = strpos($content, 'SET NAMES') ?: 0;
        $charset_comment = substr($content, $start, 20);
        $charset_comment_array = [];

        if ($charset_comment !== false) {
            $charset_comment_array = explode(' ', $charset_comment);
        }

        if (!isset($charset_comment_array[2])) {
            return '';
        }

        return $charset_comment_array[2];
    }

    /**
     * Get the URL from the manifest file.
     *
     * @return string The blog URL
     */
    public function get_url(): string
    {
        if ($this->manifest === null || !property_exists($this->manifest, 'blog_info') || $this->manifest->blog_info === null) {
            return '';
        }

        return $this->manifest->blog_info->url;
    }

    /**
     * @return string[]
     */
    public function get_job_types(): array
    {
        if ($this->manifest === null) {
            throw new ManifestFileException(
                __('Manifest file not found. Please check the file exists within the backup and extraction folder.', 'backwpup')
            );
        }

        if (
            !property_exists($this->manifest, 'job_settings')
            || !$this->manifest->job_settings instanceof \stdClass
            || !property_exists($this->manifest->job_settings, 'type')
        ) {
            return [];
        }

        $typesRaw = $this->manifest->job_settings->type;

        if (is_string($typesRaw) && $typesRaw !== '') {
            return [$typesRaw];
        }

        if (is_array($typesRaw)) {
            return array_values(array_filter($typesRaw, 'is_string'));
        }

        return [];
    }

    public function has_db(): bool
    {
        $types = $this->get_job_types();
        return in_array('DBDUMP', $types, true);
    }

    public function has_files(): bool
    {
        $types = $this->get_job_types();
        return !empty(array_diff($types, ['DBDUMP']));
    }

    public function can_full_restore(): bool
    {
        return $this->has_db() && $this->has_files();
    }

    /**
     * @return 'full'|'db_only'|'files_only'|'unknown'
     */
    public function restore_mode(): string
    {
        $types = $this->get_job_types();
        $hasDb = in_array('DBDUMP', $types, true);
        $hasFiles = !empty(array_diff($types, ['DBDUMP']));

        if ($hasDb && $hasFiles) {
            return 'full';
        }
        if ($hasDb) {
            return 'db_only';
        }
        if ($hasFiles) {
            return 'files_only';
        }

        return 'unknown';
    }

    /**
     * Capabilities.
     *
     * @return object{
     *   has_db: bool,
     *   has_files: bool,
     *   can_full_restore: bool,
     *   mode: 'full'|'db_only'|'files_only'|'unknown',
     *   job_types: string[]
     * }
     */
    public function get_restore_capabilities(): object
    {
        return (object) [
            'has_db' => $this->has_db(),
            'has_files' => $this->has_files(),
            'can_full_restore' => $this->can_full_restore(),
            'mode' => $this->restore_mode(),
            'job_types' => $this->get_job_types(),
        ];
    }
}
