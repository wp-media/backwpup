<?php

/**
 * Base class for adding BackWPup destinations.
 *
 * @since 3.0.0
 */
abstract class BackWPup_Destinations
{
    /**
     * @var string
     */
    private const CAPABILITY = 'backwpup_backups_download';
    /**
     * @var string[]
     */
    private const EXTENSIONS = [
        '.tar.gz',
        '.tar',
        '.zip',
    ];

    abstract public function option_defaults(): array;

    abstract public function edit_tab(int $jobid): void;

    public function edit_auth(int $jobid): void
    {
    }

    abstract public function edit_form_post_save(int $jobid): void;

    /**
     * use wp_enqueue_script() here to load js for tab.
     */
    public function admin_print_scripts(): void
    {
    }

    public function edit_inline_js(): void
    {
    }

    public function edit_ajax(): void
    {
    }

    public function wizard_admin_print_styles(): void
    {
    }

    public function wizard_admin_print_scripts(): void
    {
    }

    public function wizard_inline_js(): void
    {
    }

    public function wizard_page(array $job_settings): void
    {
        echo '<br /><pre>';
        print_r($job_settings);
        echo '</pre>';
    }

    public function wizard_save(array $job_settings): array
    {
        return $job_settings;
    }

    public function admin_print_styles(): void
    {
    }

    public function file_delete(string $jobdest, string $backupfile): void
    {
    }

    public function file_download(int $jobid, string $file_path, ?string $local_file_path = null): void
    {
        $filename = untrailingslashit(BackWPup::get_plugin_data('temp')) . '/' . basename($local_file_path ?: $file_path);

        // Dynamically get downloader class
        $class_name = get_class($this);
        $parts = explode('_', $class_name);
        $destination = array_pop($parts);

        $downloader = new BackWpup_Download_Handler(
            new BackWPup_Download_File(
                $filename,
                static function (BackWPup_Download_File_Interface $obj) use (
                    $filename,
                    $file_path,
                    $jobid,
                    $destination
                ): void {
                    // Setup Destination service and download file.
                    $factory = new BackWPup_Destination_Downloader_Factory();
                    $downloader = $factory->create(
                        $destination,
                        $jobid,
                        $file_path,
                        $filename
                    );
                    $downloader->download_by_chunks();

                    exit();
                },
                self::CAPABILITY
            ),
            'backwpup_action_nonce',
            self::CAPABILITY,
            'download_backup_file'
        );

        // Download the file.
        $downloader->handle();
    }

    public function file_get_list(string $jobdest): array
    {
        return [];
    }

    abstract public function job_run_archive(BackWPup_Job $job_object): bool;

    public function job_run_sync(BackWPup_Job $job_object): bool
    {
        return true;
    }

    abstract public function can_run(array $job_settings): bool;

    /**
     * Is Backup Archive.
     *
     * Checks if given file is a backup archive.
     */
    public function is_backup_archive(string $file): bool
    {
        $file = trim(basename($file));
        $filename = '';

        foreach (self::EXTENSIONS as $extension) {
            if (substr($file, (strlen($extension) * -1)) === $extension) {
                $filename = substr($file, 0, (strlen($extension) * -1));
            }
        }

        return !(!$filename);
    }

    /**
     * Checks if the given archive belongs to the given job.
     */
    public function is_backup_owned_by_job(string $file, int $jobid): bool
    {
        $info = pathinfo($file);
        $file = basename($file, '.' . $info['extension']);

        // Try 10-character chunks first for base 32 and most of base 36
        $data = $this->getDecodedHashAndJobId($file, 10);

        // Try 9-character chunks for any left-over base 36
        if (!$data) {
            $data = $this->getDecodedHashAndJobId($file, 9);
        }

        return $data && $this->dataContainsCorrectValues($data, $jobid);
    }

    /**
     * @return array|bool
     */
    protected function getDecodedHashAndJobId(string $file, int $numberOfCharacters)
    {
        $data = [];

        for ($i = strlen($file) - $numberOfCharacters; $i >= 0; --$i) {
            $data = BackWPup_Option::decode_hash(substr($file, $i, $numberOfCharacters));
            if ($data) {
                break;
            }
        }

        return $data;
    }

    protected function dataContainsCorrectValues(array $data, int $jobid): bool
    {
        if ($data[0] !== BackWPup::get_plugin_data('hash')) {
            return false;
        }

        return $data[1] === $jobid;
    }
}
