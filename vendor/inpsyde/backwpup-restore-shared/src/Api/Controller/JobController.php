<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Controller;

use BackWPup_Destination_Downloader_Factory;
use Inpsyde\Restore\Api\Exception\ExceptionLinkHelper;
use Inpsyde\Restore\Api\Exception\FileSystemException;
use Inpsyde\Restore\Api\Module\Database\DatabaseInterface;
use Inpsyde\Restore\Api\Module\Database\DatabaseTypeFactory;
use Inpsyde\Restore\Api\Module\Database\Exception as DatabaseException;
use Inpsyde\Restore\Api\Module\Decompress\Decompressor;
use Inpsyde\Restore\Api\Module\Decompress\Exception\DecompressException;
use Inpsyde\Restore\Api\Module\Decryption\Decrypter;
use Inpsyde\Restore\Api\Module\Decryption\Exception\DecryptException;
use Inpsyde\Restore\Api\Module\Download\Exception\DownloadException;
use Inpsyde\Restore\Api\Module\ImportInterface;
use Inpsyde\Restore\Api\Module\Manifest\Exception\ManifestFileException;
use Inpsyde\Restore\Api\Module\Manifest\ManifestFile;
use Inpsyde\Restore\Api\Module\Registry;
use Inpsyde\Restore\Api\Module\RegistryException;
use Inpsyde\Restore\Api\Module\Restore\ConfigRewriterInterface;
use Inpsyde\Restore\Api\Module\Restore\Exception\ConfigFileException;
use Inpsyde\Restore\Api\Module\Restore\Exception\ConfigFileNotFoundException;
use Inpsyde\Restore\Api\Module\Restore\Exception\RestorePathException;
use Inpsyde\Restore\Api\Module\Restore\RestoreInterface;
use Inpsyde\Restore\Api\Module\Session\NotificableStorableSessionInterface;
use Inpsyde\Restore\Api\Module\Upload\FileUploadInterface;
use Inpsyde\Restore\DestinationFactory;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

/**
 * Class JobController.
 *
 * @author  Hans-Helge Buerger
 */
class JobController
{
    /**
     * Registry.
     *
     * @var Registry Registry object
     */
    private $registry;

    /**
     * Backup Upload.
     *
     * @var FileUploadInterface file upload object
     */
    private $backup_upload;

    /**
     * Database Factory.
     *
     * @var DatabaseTypeFactory db factory object
     */
    private $database_factory;

    /**
     * Decompress.
     *
     * @var Decompressor Instance used to decompress the archive
     */
    private $decompress;

    /**
     * Manifest.
     *
     * @var ManifestFile The instance of the manifest
     */
    private $manifest;

    /**
     * Session.
     *
     * @var NotificableStorableSessionInterface session To store info about the actions
     */
    private $session;

    /**
     * Import Interface.
     *
     * @var ImportInterface
     */
    private $database_importer;

    /**
     * Restore Interface.
     *
     * @var RestoreInterface&ConfigRewriterInterface
     */
    private $restoreFiles;

    /**
     * @var Decrypter
     */
    private $decrypter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * JobController constructor.
     *
     * @param RestoreInterface&ConfigRewriterInterface $restoreFiles
     */
    public function __construct(
        Registry $registry,
        LoggerInterface $logger,
        Decompressor $decompress,
        ManifestFile $manifest,
        NotificableStorableSessionInterface $session,
        FileUploadInterface $backup_upload,
        DatabaseTypeFactory $database_factory,
        ImportInterface $database_importer,
        RestoreInterface $restoreFiles,
        Decrypter $decrypter
    ) {
        Assert::implementsInterface(get_class($restoreFiles), ConfigRewriterInterface::class);

        $this->registry = $registry;
        $this->decompress = $decompress;
        $this->manifest = $manifest;
        $this->session = $session;
        $this->backup_upload = $backup_upload;
        $this->database_factory = $database_factory;
        $this->database_importer = $database_importer;
        $this->restoreFiles = $restoreFiles;
        $this->decrypter = $decrypter;
        $this->logger = $logger;
    }

    /**
     * Hello World Method. Used as fallback in ajax.php.
     */
    public function index(): string
    {
        return 'Crafted by Inpsyde';
    }

    /**
     * Upload Action.
     *
     * Trigger the upload process and save path to registry.
     *
     * @throws \Exception in case isn't possible to upload the file
     */
    public function upload_action(): bool
    {
        $this->backup_upload->run();
        $this->registry->uploaded_file = $this->backup_upload->get_abs_file_path();

        return true;
    }

    /**
     * Download Archive.
     *
     * @throws DownloadException
     */
    public function download_action(?int $job_id, ?string $service_name, ?string $source_file_path, ?string $local_file_path): void
    {
        if (empty($local_file_path)) {
            throw new DownloadException(__('Local file path cannot be empty.', 'backwpup'));
        }

        // The file may be already into the server and we have an absolute file path.
        if (empty($source_file_path) && file_exists($local_file_path)) {
            return;
        }

        if (empty($service_name)) {
            throw new DownloadException(__('Service cannot be empty.', 'backwpup'));
        }
        if (empty($job_id)) {
            throw new DownloadException(__('Job ID must not be empty or 0.', 'backwpup'));
        }

        if (!class_exists('BackWPup_Destination_Downloader_Factory')) {
            throw new DownloadException(
                __(
                    'Errors occurred while downloading. Destination may not be created.',
                    'backwpup'
                )
            );
        }

        // Set service and job_id in registry for future use
        $this->registry->service_name = $service_name;
        $this->registry->job_id = $job_id;

        $factory = new BackWPup_Destination_Downloader_Factory();
        $downloader = $factory->create(
            $service_name,
            $job_id,
            $source_file_path,
            $local_file_path
        );

        $downloader->download_by_chunks();
    }

    /**
     * Decompress Upload Action.
     *
     * @param string $file_path The path of the file to decompress.
     *                          Optional, default to `uploaded_file` in registry.
     *
     * @throws \RuntimeException In case the file manifest.json doesn't exists.
     * @throws \Exception        if something goes wrong with the decompression
     */
    public function decompress_upload_action(string $file_path = ''): void
    {
        $this->ensure_uploaded_file($file_path);

        $file_ext = pathinfo($this->registry->uploaded_file, PATHINFO_EXTENSION);

        $this->throw_error_if_bz2($file_ext);

        $may_decrypted = $this->decrypter->isEncrypted($this->registry->uploaded_file);
        if ($may_decrypted) {
            throw new DecryptException(DecryptController::STATE_NEED_DECRYPTION_KEY);
        }

        $this->decompress->run();

        if (!$this->is_manifest_readable()) {
            throw new ManifestFileException(
                __('Sorry but only backups made using BackWPup plugin can be restored.', 'backwpup')
            );
        }

        $this->session->success(__('Extraction Successful', 'backwpup'));
    }

    /**
     * Db Test Action.
     *
     * @param string $db_settings The database settings in json format
     *
     *@throws DatabaseException\DatabaseConnectionException If the connection could not be made
     * @throws DatabaseException\DatabaseFileException in case the dump file was not set properly
     * @throws ManifestFileException                   if the manifest is not a valid object
     */
    public function db_test_action(string $db_settings): bool
    {
        $db = json_decode($db_settings);
        Assert::isInstanceOf($db, \stdClass::class);

        $this->manifest->set_manifest_file($this->registry->manifest_file);

        $dumpfile = $this->manifest->get_dump_file();

        if ($dumpfile === '') {
            throw new DatabaseException\DatabaseFileException(
                sprintf(__('Sql file %1$s does not exist', 'backwpup'), $dumpfile)
            );
        }

        $this->registry->dbhost = $db->dbhost ?? '';
        $this->registry->dbname = $db->dbname ?? '';
        $this->registry->dbuser = $db->dbuser ?? '';
        $this->registry->dbpassword = $db->dbpassword ?? '';

        // Add SQL dump file to $registry->extra_files to ignore it during file restore
        $this->registry->add_to_blacklist($dumpfile);

        // phpcs:disable PSR2.Methods.FunctionCallSignature.Indent
        $this->registry->dbdumpfile = rtrim(
            $this->registry->extract_folder,
            DIRECTORY_SEPARATOR
        ) . DIRECTORY_SEPARATOR . $dumpfile;
        // phpcs:enable

        $this->registry->dbcharset = empty($db->dbcharset)
            ? $this->manifest->get_charset()
            : $db->dbcharset;

        $db = $this->database_factory->database_type();
        if (!$db instanceof DatabaseInterface) {
            throw new \InvalidArgumentException(__('No database could be loaded.', 'backwpup'));
        }

        $db->connect();

        try {
            // If we're still here, rewrite config with new DB credentials
            $this->restoreFiles->rewriteConfig();
        } catch (ConfigFileNotFoundException $e) {
            $this->logger->info($e->getMessage());
        } catch (FileSystemException | ConfigFileException $e) {
            $this->logger->warning($e->getMessage());
        }

        return true;
    }

    /**
     * Restore Dir.
     *
     * @throws RestorePathException if the files cannot be restored because
     *                              destination and source are not set
     * @throws \Exception           if registry cannot be saved
     *
     * @return string The response for the action
     */
    public function restore_dir_action(): string
    {
        // Restore
        $errors = $this->restoreFiles->restore();

        // Store the state.
        $this->registry->finish_job('file_restore');

        return $errors !== 0
            ? __('Directories restored with errors.', 'backwpup')
            : __('Directories restored successfully.', 'backwpup');
    }

    /**
     * Restore the Database.
     *
     * @throws DatabaseException\DatabaseFileException
     */
    public function restore_db_action(): string
    {
        if (!file_exists($this->registry->dbdumpfile)) {
            $this->logger->warning('No database dump file found.');
            return __('No database dump file found.', 'backwpup');
        }

        // Restore the db.
        $this->database_importer->import();

        // Refresh file list
        if ($this->registry->service_name && $this->registry->job_id) {
            $destination_factory = new DestinationFactory($this->registry->service_name);
            $destination = $destination_factory->create();

            if (method_exists($destination, 'file_update_list')) {
                $destination->file_update_list($this->registry->job_id);
            }

            $this->registry->service_name = null;
            $this->registry->job_id = null;
        }

        // After we successfully restored the database we must log the user in again because
        // we have lost the reference to the current user.
        $this->login_user_again();

        return __('Database restored successfully.', 'backwpup');
    }

    /**
     * Save strategy.
     *
     * @phpstan-param non-empty-string $strategy
     */
    public function save_strategy_action(string $strategy): void
    {
        $this->registry->restore_strategy = $strategy;
    }

    /**
     * Get restore strategy from registry.
     */
    public function get_strategy_action(): string
    {
        return $this->registry->restore_strategy;
    }

    /**
     * Fetch the blog URL from manifest.json.
     *
     * @return string The blog URL
     */
    public function fetch_url_action(): string
    {
        $this->manifest->set_manifest_file($this->registry->manifest_file);

        return $this->manifest->get_url();
    }

    /**
     * Save migration settings.
     *
     * @phpstan-param non-empty-string $old_url
     * @phpstan-param non-empty-string $new_url
     */
    public function save_migration_action(string $old_url, string $new_url): void
    {
        $this->registry->old_url = $old_url;
        $this->registry->new_url = $new_url;
    }

    /**
     * Check if the manifest json is readable or not.
     */
    private function is_manifest_readable(): bool
    {
        $manifest_file = $this->registry->manifest_file ?? '';

        return $manifest_file && is_readable($manifest_file);
    }

    /**
     * Login User Programmatically.
     */
    private function login_user_again(): void
    {
        // Nothing to do in standalone version.
        if (!class_exists('\WP')) {
            return;
        }

        $user = wp_get_current_user();

        wp_set_auth_cookie($user->ID, true);

        /*
         * Wp Login
         *
         * @param string $user_login The user login.
         * @param \WP_User $user The user instance.
         * @since 2.0.0
         *
         */
        do_action('wp_login', $user->user_login, $user);
    }

    /**
     * Ensure the uploaded file path is set correctly.
     *
     * @throws RegistryException
     */
    private function ensure_uploaded_file(string $file_path): void
    {
        // TODO Find a way to set this once for the entire restore process if possible.
        if (!$this->registry->uploaded_file && $file_path) {
            $this->registry->uploaded_file = $file_path;
            $this->decompress->set_file_path($file_path);
        }

        // Remember about two context, upload file and restore from an existing file.
        if ($this->registry->uploaded_file === '') {
            throw new RegistryException(
                __(
                    'Seems the file you are trying to decompress doesn\'t exists. Please see the log file.',
                    'backwpup'
                )
            );
        }
    }

    /**
     * Throw an exception in case the backup file it's a bzip2
     * Bzip2 isn't supported by the restore feature.
     *
     * @throws DecompressException
     */
    private function throw_error_if_bz2(string $file_ext): void
    {
        if ($file_ext === 'bz2') {
            $this->registry->reset_registry();

            throw new DecompressException(
                ExceptionLinkHelper::translateWithAppropiatedLink(
                    __(
                        'Sorry but bzip2 backups cannot be restored. You must convert the file to a .zip one in order to able to restore your backup.',
                        'backwpup'
                    ),
                    'BZIP2_CANNOT_BE_DECOMPRESSED'
                )
            );
        }
    }
}
