<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module;

use Exception;
use Inpsyde\Restore\Api\Exception\FileSystemException;
use Inpsyde\Restore\Utils\SanitizePath;
use InvalidArgumentException;

/**
 * A persistnt registry.
 *
 * Saves Data in an PHP serialized file.
 *
 * @author  ap
 *
 * @since   1.0.0
 *
 * @property string                $dbhost                  database connection host
 * @property string                $dbuser                  database connection user
 * @property string                $dbpassword              database connection user
 * @property string                $dbname                  database name
 * @property string                $dbcharset               database connection charset
 * @property string                $dbdumpfile              file to import to Database
 * @property DbPos                 $dbdumppos               position where the import at moment
 * @property int                   $dbdumpsize              the size of the database file
 * @property string|null           $locale                  language locale, e.g. de_DE
 * @property int                   $migration_progress      migration_progress
 * @property string                $restore_strategy        states what strategy is chosen (DB only restore, complete restore)
 * @property string                $project_root            absolute path to project root
 * @property string                $project_temp            absolute path to project temp folder
 * @property string                $uploaded_file           file name of upload
 * @property string                $file_prefix             The random prefix for the uploaded file
 * @property string                $upload_dir              absolute path to upload directory
 * @property string                $extract_folder          absolute path to decompressed backup directory
 * @property DecompressionState    $decompression_state     the state of the decompression process
 * @property string                $manifest_file           path to manifest.json
 * @property string[]              $extra_files             array holds files to ignore during file restore
 * @property string[]              $restore_list            list of directories seen so far to restore.
 * @property string                $restore_file_start_from the file to begin restoring from
 * @property string                $restore_file_skip       file to skip in case of error
 * @property array<string, string> $restore_finished        array where jobs can mark themselves as finished
 * @property string                $uploads_folder          the uploads folder within the restore dir.
 * @property string|null           $service_name
 * @property int|null              $job_id
 * @property string                $old_url                 the old URL to migrate from
 * @property string                $new_url                 the new URL to migrate to
 *
 * @psalm-import-type DbPos from \Inpsyde\Restore\Api\Module\Database\ImportFileInterface
 * @psalm-import-type DecompressionState from \Inpsyde\Restore\Api\Module\Decompress\StateUpdater
 */
class Registry
{
    /**
     * @var string
     */
    private $filePath;

    /**
     * Internal registry.
     *
     * @var mixed[]
     */
    private $registry = [];

    /**
     * @param string $path The Path to the save file
     *
     * @throws InvalidArgumentException
     */
    public function __construct($path)
    {
        $sanitizedPath = SanitizePath::sanitize($path);

        if ($sanitizedPath !== $path) {
            throw new InvalidArgumentException(
                'Given Path seems corrupted when construct the registry instance.'
            );
        }

        $this->filePath = $path;
    }

    /**
     * Add/Update a value in the registry.
     *
     * @param string $key   The registry key
     * @param mixed  $value Value associated with the key
     *
     * @throws Exception In case the registry cannot be saved
     */
    public function __set($key, $value): void
    {
        $this->registry[$key] = $value;

        $this->save();
    }

    /**
     * Check if value is set in registry.
     *
     * @param string $name The registry key
     *
     * @return bool
     */
    public function __isset($name)
    {
        return \array_key_exists($name, $this->registry);
    }

    /**
     * Perform the basic tasks to make the registry work properly.
     *
     * @throws FileSystemException
     */
    public function init(): void
    {
        if (file_exists($this->filePath)) {
            $data = file_get_contents($this->filePath) ?: '';
            $unserializedData = unserialize($data);
            $this->registry = \is_array($unserializedData) ? $unserializedData : [];

            return;
        }

        if (!\defined('FS_CHMOD_DIR')) {
            \define('FS_CHMOD_DIR', 0775);
        }

        if (!file_exists(\dirname($this->filePath))) {
            // Attempt to create the directories if not exists.
            mkdir(\dirname($this->filePath), FS_CHMOD_DIR, true);
        }

        // If file doesn't exists let's try to create it.
        $handle = fopen($this->filePath, 'a+');

        // If file cannot be created log it for support.
        if (!$handle) {
            throw new FileSystemException(
                'Cannot possible to create the registry file. Restore will not work properly.'
            );
        }

        // Release the resource.
        fclose($handle);
    }

    /**
     * Save the registry to the save file.
     *
     * @throws FileSystemException In case the registry cannot be saved
     */
    public function save(): void
    {
        $data = serialize($this->registry); // phpcs:ignore

        // When writing get an exclusive lock on the file to avoid conflicts
        if (file_put_contents($this->filePath, $data, LOCK_EX) === false) { // phpcs:ignore
            throw new FileSystemException(
                sprintf(
                    'Could not write Registry file to %s',
                    $this->filePath
                )
            );
        }
    }

    /**
     * Get a value from the registry.
     *
     * @param string $key The registry key
     *
     * @return mixed
     */
    public function &__get($key)
    {
        if (!\array_key_exists($key, $this->registry)) {
            $this->registry[$key] = null;
        }

        return $this->registry[$key];
    }

    /**
     * Check if registry has a specific key set.
     *
     * @param string $key Name of registry key
     */
    public function has(string $key): bool
    {
        return $this->__isset($key);
    }

    /**
     * Remove an entry from the registry.
     *
     * @param string $key The registry key
     *
     * @throws Exception In case the registry cannot be saved
     */
    public function delete(string $key): void
    {
        unset($this->registry[$key]);

        $this->save();
    }

    /**
     * Add file to blacklist.
     *
     * Helper function to add file names to a blacklist for file restore
     *
     * @param string $file_name The file name to exclude
     *
     * @throws Exception In case the registry cannot be saved
     */
    public function add_to_blacklist(string $file_name): void
    {
        if ($this->extra_files === null) {
            $this->extra_files = [];
        }

        $this->extra_files[] = $file_name;

        $this->save();
    }

    /**
     * Store the finished jobs.
     *
     * @param string $job_name The job to store
     *
     * @throws Exception In case the registry cannot be saved
     */
    public function finish_job(string $job_name): void
    {
        if (
            $this->restore_finished === null
            || !\is_array($this->restore_finished)
        ) {
            $this->restore_finished = [];
        }

        $this->restore_finished[$job_name] = 'finished';

        $this->save();
    }

    /**
     * Helper function to add dirs to restore_list.
     *
     * @param string $dir The dir to store in the registry
     *
     * @throws Exception In case the registry cannot be saved
     */
    public function add_to_restore_list(string $dir): void
    {
        if ($this->restore_list === null || !\is_array($this->restore_list)) {
            $this->restore_list = [];
        }

        $this->restore_list[] = $dir;

        $this->save();
    }

    /**
     * Helper function to remove first element in restore_list.
     *
     * @throws Exception In case the registry cannot be saved
     *
     * @return string The directory to restore
     */
    public function next_dir_in_restore_list(): string
    {
        if (empty($this->restore_list)) {
            return '';
        }

        $current = array_shift($this->restore_list);

        $this->save();

        return $current;
    }

    /**
     * Update the migration progress.
     *
     * @param int $percent The progress percentage
     *
     * @throws Exception In case the registry cannot be saved
     */
    public function update_progress(int $percent): void
    {
        $this->migration_progress = $percent;

        $this->save();
    }

    /**
     * Reset registry to start a new restore process.
     *
     * A hard and soft reset is possible. A hard one will delete the complete registry and the app will
     * start from scratch. A soft reset will delete only information regarding the last restore. I.e.
     * information about language translation, etc. are kept b/c this does not influence the restore itself.
     *
     * @throws Exception In case the registry cannot be saved
     *
     * @return $this Registry for concatenation
     */
    public function reset_registry(): self
    {
        copy($this->filePath, "{$this->filePath}.bkp");

        $this->registry = [];
        $this->save();

        return $this;
    }

    /**
     * Is Restore Finished?
     *
     * @return bool True on success, false on error
     */
    public function is_restore_finished(): bool
    {
        if (!($this->restore_strategy !== null && $this->restore_finished !== null)) {
            return false;
        }

        $jobs = 1;
        if ($this->restore_strategy === 'complete restore') {
            $jobs = 2;
        }

        return $jobs === \count($this->restore_finished);
    }
}
