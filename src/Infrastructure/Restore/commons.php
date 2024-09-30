<?php
/**
 * BackWPup Restore functions.
 */

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Restore;

use Inpsyde\BackWPup\Archiver\Extractor;
use Inpsyde\BackWPup\Archiver\Factory;
use Inpsyde\Restore\AjaxHandler;
use Inpsyde\Restore\Api\Controller\DecryptController;
use Inpsyde\Restore\Api\Controller\JobController;
use Inpsyde\Restore\Api\Controller\LanguageController;
use Inpsyde\Restore\Api\Error\ErrorHandler;
use Inpsyde\Restore\Api\Exception\ExceptionHandler;
use Inpsyde\Restore\Api\Module\Database;
use Inpsyde\Restore\Api\Module\Database\DatabaseTypeFactory;
use Inpsyde\Restore\Api\Module\Database\ImportFileFactory;
use Inpsyde\Restore\Api\Module\Database\ImportModel;
use Inpsyde\Restore\Api\Module\Database\MysqliDatabaseType;
use Inpsyde\Restore\Api\Module\Database\SqlFileImport;
use Inpsyde\Restore\Api\Module\Decompress\Decompressor;
use Inpsyde\Restore\Api\Module\Decompress\State;
use Inpsyde\Restore\Api\Module\Decompress\StateUpdater;
use Inpsyde\Restore\Api\Module\Decryption\Decrypter;
use Inpsyde\Restore\Api\Module\Manifest\ManifestFile;
use Inpsyde\Restore\Api\Module\Registry;
use Inpsyde\Restore\Api\Module\Restore;
use Inpsyde\Restore\Api\Module\Restore\RestoreFiles;
use Inpsyde\Restore\Api\Module\Session\Session;
use Inpsyde\Restore\Api\Module\Upload;
use Inpsyde\Restore\Api\Module\Upload\BackupUpload;
use Inpsyde\Restore\EventSource;
use Inpsyde\Restore\Log\LevelExtractorFactory;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Pimple\Exception\FrozenServiceException;


/**
 * Container.
 *
 * @template T of string|null
 *
 * @param string|null $name the name of the data to retrieve or null to retrieve the whole container
 * @psalm-param T $name
 *
 * @throws FrozenServiceException if the service has been marked as frozen,
 *                                indicating that it has already been retrieved
 *                                and cannot be modified
 * @throws \OutOfBoundsException  if the provided name does not exist in the container
 *
 * @return mixed|Container
 * @psalm-return (T is null ? Container : mixed)
 * @psalm-suppress MixedArgument
 */
function restore_container($name)
{
    /** @var Container|null $container */
    static $container;

    // Clean up the name.
    $name = sanitize_key($name ?? '');

    if (!$container) {
        // Upload Dir.
        $upload_dir = wp_upload_dir(null, true, false);

        // DI Container.
        $container = new Container();

        // Project Paths, it's the root WordPress installation.
        $container['project_root'] = ABSPATH;

        // Create the restore directory to use to store the temporary data for restoring.
        $container['project_temp'] = untrailingslashit(
            \BackWPup_File::get_absolute_path($upload_dir['basedir'])
        ) . '/backwpup-restore';

        // Logger
        $container['log_file'] = (string) $container['project_temp'] . '/restore.log';
        $container['logger'] = static function (Container $container): Logger {
            $logger = new Logger('restore');
            $logger->pushHandler(new StreamHandler((string) $container['log_file'], Logger::INFO));

            return $logger;
        };

        // Registry.
        $container['registry'] = static function (Container $container): Registry {
            $registry = new Registry((string) $container['project_temp'] . '/restore.dat');
            $registry->init();

            return $registry;
        };

        // Decompressor.
        $container['decompress_state'] = static function (Container $container): State {
            return new State($container['registry']);
        };

        $container['decompress_state_updater'] = static function (Container $container): StateUpdater {
            return new StateUpdater($container['registry']);
        };

        $container['decompress'] = static function (Container $container): Decompressor {
            return new Decompressor(
                $container['registry'],
                $container['logger'],
                $container['extractor_extractor'],
                $container['decompress_state'],
                $container['decompress_state_updater']
            );
        };

        // Error.
        $container['error_handler'] = static function (Container $container): ErrorHandler {
            return new ErrorHandler($container['logger'], $container['registry']);
        };

        // Exception Handler.
        $container['exception_handler'] = static function (Container $container): ExceptionHandler {
            return new ExceptionHandler(
                $container['logger'],
                $container['session'],
                $container['registry']
            );
        };

        // Controller.
        $container['job_controller'] = static function (Container $container): JobController {
            return new JobController(
                $container['registry'],
                $container['logger'],
                $container['decompress'],
                $container['manifest'],
                $container['session'],
                $container['backup_upload'],
                $container['database_factory'],
                $container['database_import'],
                $container['restore_files'],
                $container['decrypter']
            );
        };

        $container['language_controller'] = static function (Container $container): LanguageController {
            return new LanguageController($container['registry']);
        };

        $container['decrypt_controller'] = static function (Container $container): DecryptController {
            return new DecryptController(
                $container['decrypter']
            );
        };

        // Upload.
        $container['backup_upload'] = static function (Container $container): BackupUpload {
            return new BackupUpload($container['registry']);
        };

        // Database.
        $container['database_factory'] = static function (Container $container): DatabaseTypeFactory {
            $types = [
                \mysqli::class => MysqliDatabaseType::class,
            ];
            $db_factory = new DatabaseTypeFactory($types, $container['registry']);
            $db_factory->set_logger($container['logger']);

            return $db_factory;
        };

        $container['database_import_file_factory'] = static function (Container $container): ImportFileFactory {
            $types = [
                'sql' => SqlFileImport::class,
            ];

            return new ImportFileFactory($types);
        };

        $container['database_import'] = static function (Container $container): ImportModel {
            return new ImportModel(
                $container['database_factory'],
                $container['database_import_file_factory'],
                $container['registry'],
                $container['logger']
            );
        };

        // Restore.
        $container['restore_files'] = static function (Container $container): RestoreFiles {
            return new RestoreFiles($container['registry'], $container['logger']);
        };

        // Manifest File.
        $container['manifest'] = static function (Container $container): ManifestFile {
            return new ManifestFile($container['registry']);
        };

        // Notification.
        $container['session'] = static function (): Session {
            return new Session($_SESSION); // phpcs:ignore
        };

        // Decrypt
        $container['decrypter'] = static function (Container $container): Decrypter {
            return new Decrypter(
                $container['archivefileoperator_factory']
            );
        };

        // Ajax
        $container['event_source'] = static function (): EventSource {
            return new EventSource();
        };

        $container['ajax_handler'] = static function (Container $container): AjaxHandler {
            return new AjaxHandler(
                $container['job_controller'],
                $container['language_controller'],
                $container['decrypt_controller'],
                $container['registry'],
                $container['logger'],
                $container['event_source'],
                $container['log_file']
            );
        };

        // Extractor
        $container['archivefileoperator_factory'] = static function (): Factory {
            return new Factory();
        };

        $container['extractor_extractor'] = static function (Container $container): Extractor {
            return new Extractor(
                $container['logger'],
                $container['archivefileoperator_factory']
            );
        };

        // Log
        $container['level_extractor_factory'] = static function (): LevelExtractorFactory {
            return new LevelExtractorFactory();
        };
    }

    if ('' === $name) {
        return $container;
    }

    if (!isset($container[$name])) {
        throw new \OutOfBoundsException(
            sprintf(
                'Invalid data request for container. %s doesn\'t exist in the container',
                $name
            )
        );
    }

    /** @psalm-suppress MixedReturnStatement */
    return $container[$name];
}

/**
 * Registry.
 *
 * @todo Move the creation of this object within the container, so we'll pass the values directly to the construct as
 *       an array of arguments.
 *
 * @internal
 *
 * @throws FrozenServiceException if the service has been marked as frozen,
 *                                indicating that it has already been retrieved
 *                                and cannot be modified
 * @throws \OutOfBoundsException  if the provided name does not exist in the container
 *
 * @return Registry the instance with additional properties
 */
function restore_registry(): Registry
{
    $container = restore_container( null );
    /** @var Registry $registry */
    $registry = $container['registry'];

    if (! $registry->project_root) {
        // Save Project Root in Registry.
        $registry->project_root = $container['project_root'];
        $registry->project_temp = $container['project_temp'];
        $registry->extract_folder = untrailingslashit($container['project_temp']) . '/extract';
        $registry->uploads_folder = untrailingslashit($container['project_temp']) . '/uploads';

        // Create the uploads directory if not exists.
        // In some cases me need to create the uploaded file from third party services and the directory must exists
        // prior to save the file.
        if (!file_exists($registry->uploads_folder)) {
            backwpup_wpfilesystem()->mkdir($registry->uploads_folder);
        }
        $registry->locale = get_locale();
    }

    return $registry;
}

/**
 * Error Handler Register.
 *
 * @param Container $container the container from which retrieve the error handler instance
 */
function error_handler_register(Container $container): void
{
    /** @var ErrorHandler $error_handler */
    $error_handler = $container['error_handler'];
    $error_handler->register();
}

/**
 * Exception Handler Register.
 *
 * @param Container $container the container from which retrieve the error handler instance
 */
function exception_handler_register(Container $container): void
{
    /** @var ExceptionHandler $exception_handler */
    $exception_handler = $container['exception_handler'];
    $exception_handler->register();
}

/**
 * Create Project temporary directory.
 *
 * @param Container $container the container of the services
 *
 * @throws \Exception in case the temporary project directory isn't writable
 */
function create_project_temp_dir(Container $container): void
{
    $response = \BackWPup_File::check_folder((string) $container['project_temp'], true);

    if ($response) {
        throw new \Exception($response);
    }
}

/**
 * Restore Boot.
 *
 * @throws FrozenServiceException if the service has been marked as frozen,
 *                                indicating that it has already been retrieved
 *                                and cannot be modified
 * @throws \OutOfBoundsException  if the provided name does not exist in the container
 * @throws \Exception             if the temp dir cannot be created
 */
function restore_boot(): void
{
    // Session is needed if we want to use notifications.
    session_start(); // phpcs:ignore

    $container = restore_container(null);
    restore_registry();

    create_project_temp_dir($container);
    error_handler_register($container);
    exception_handler_register($container);
}
