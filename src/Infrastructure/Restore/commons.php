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

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Option name used to persist the per-site randomized restore working-directory token.
 */
const RESTORE_DIR_TOKEN_OPTION = 'backwpup_restore_dir_token';

/**
 * Legacy, un-tokenized restore working-directory name (used prior to this fix).
 *
 * Kept as a permanent second sweep target so pre-existing abandoned-restore residue
 * at the old fixed path also gets cleaned up (see maybe_cleanup_abandoned_restore()).
 */
const LEGACY_RESTORE_DIR_NAME = 'backwpup-restore';

/**
 * Pattern matching a tokenized restore working-directory name (`backwpup-restore-<20 hex>`),
 * without delimiters or anchors so it can be reused for both discovery and log redaction.
 */
const RESTORE_DIR_TOKEN_PATTERN = 'backwpup-restore-[0-9a-f]{20}';

/**
 * Returns the per-site randomized token used to make the restore working directory
 * name unguessable (closes the unauthenticated-disclosure vector on webservers that
 * do not honour .htaccess, e.g. NGINX).
 *
 * Generated once via a cryptographically secure random source and persisted in a
 * per-site option (not a network-wide site option), matching the per-site scoping
 * `wp_upload_dir( null, true, false )` already uses for `project_temp`.
 *
 * Uses `add_option()`'s atomicity to converge concurrent requests on the same token:
 * if two requests race to generate a token, only the first insert wins, and both
 * requests then re-read the option to get the authoritative, persisted value.
 *
 * @return string The restore directory token.
 */
function restore_dir_token(): string
{
    $token = get_option(RESTORE_DIR_TOKEN_OPTION);

    if (is_string($token) && $token !== '') {
        return $token;
    }

    $token = bin2hex(random_bytes(10));

    // add_option() is a no-op (returns false) if another request already inserted
    // the option in the meantime; re-reading afterwards always returns the value
    // that actually won the race, regardless of which request generated it.
    add_option(RESTORE_DIR_TOKEN_OPTION, $token, '', 'no');

    $token = get_option(RESTORE_DIR_TOKEN_OPTION);

    return (string) $token;
}

/**
 * Returns the current, tokenized restore working-directory name
 * (e.g. `backwpup-restore-<token>`).
 *
 * @return string
 */
function restore_dir_name(): string
{
    return LEGACY_RESTORE_DIR_NAME . '-' . restore_dir_token();
}

/**
 * Ensures the restore working directory's `uploads/` subdirectory exists and is
 * protected via BackWPup_File::check_folder(), the same primitive already used for
 * the top-level `project_temp` directory.
 *
 * Mirrors the fail-loudly behaviour of create_project_temp_dir(): a protection
 * failure must not be silently swallowed, since that would leave the directory
 * unprotected.
 *
 * @param string $uploads_folder Absolute path to the restore `uploads/` subdirectory.
 *
 * @throws \Exception If the uploads folder cannot be protected.
 */
function ensure_uploads_folder_protected(string $uploads_folder): void
{
    if (!file_exists($uploads_folder)) {
        backwpup_wpfilesystem()->mkdir($uploads_folder);
    }

    $response = \BackWPup_File::check_folder($uploads_folder, true);

    if ($response) {
        throw new \Exception(esc_html($response));
    }
}

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
 * @throws \Exception             if the registry's uploads folder cannot be protected
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
        // Uses a per-site randomized token (see restore_dir_name()) instead of a fixed,
        // predictable name, so an unauthenticated attacker cannot construct the URL to
        // the working directory's contents on webservers that don't honour .htaccess.
        $container['project_temp'] = untrailingslashit(
            \BackWPup_File::get_absolute_path($upload_dir['basedir'])
        ) . '/' . restore_dir_name();

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

            // Ensure that all values (not even project_root is empty)
            if (empty($registry->project_root)) {
                $registry->project_root = (string) $container['project_root'];
            }

            if (empty($registry->project_temp)) {
                $registry->project_temp = (string) $container['project_temp'];
            }

            if (empty($registry->extract_folder)) {
                $registry->extract_folder = untrailingslashit((string) $container['project_temp']) . '/extract';
            }

            if (empty($registry->uploads_folder)) {
                $registry->uploads_folder = untrailingslashit((string) $container['project_temp']) . '/uploads';

                ensure_uploads_folder_protected($registry->uploads_folder);
            }

            if (empty($registry->locale)) {
                $registry->locale = get_locale();
            }

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
                \wpdb::class => WpdbDatabaseType::class,
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
				// translators: %s is the name of the service that doesn't exist in the container.
                esc_html__('Invalid data request for container. %s doesn\'t exist in the container', 'backwpup'),
                esc_html($name)
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
 * @throws FrozenServiceException if the service has been marked as frozen,
 *                                indicating that it has already been retrieved
 *                                and cannot be modified
 * @throws \OutOfBoundsException  if the provided name does not exist in the container
 * @throws \Exception             if the uploads folder cannot be protected
 *
 * @return Registry the instance with additional properties
 */
function restore_registry(): Registry
{
    $container = restore_container( null );
    /** @var Registry $registry */
    $registry = $container['registry'];

    // Ensure that all values, even if restore.dat already exists.
    if (empty($registry->project_root)) {
        $registry->project_root = (string) $container['project_root'];
    }

    if (empty($registry->project_temp)) {
        $registry->project_temp = (string) $container['project_temp'];
    }

    if (empty($registry->extract_folder)) {
        $registry->extract_folder = untrailingslashit((string) $container['project_temp']) . '/extract';
    }

    if (empty($registry->uploads_folder)) {
        $registry->uploads_folder = untrailingslashit((string) $container['project_temp']) . '/uploads';

        ensure_uploads_folder_protected($registry->uploads_folder);
    }

    if (empty($registry->locale)) {
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
        throw new \Exception(esc_html($response));
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

/**
 * Returns the modification time of one entry found while scanning a restore working
 * directory, failing towards "recent activity" so an active restore is never swept:
 *   - a broken symlink yields the link's own mtime (it is not activity by itself);
 *   - an entry that vanished between listing and stat (e.g. moved by the running
 *     restore) counts as activity happening now.
 *
 * @param \SplFileInfo $file Entry to inspect.
 *
 * @return int
 */
function entry_mtime(\SplFileInfo $file): int
{
    try {
        return (int) $file->getMTime();
    } catch (\RuntimeException $e) {
        $stat = @lstat($file->getPathname()); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

        return false !== $stat ? (int) $stat['mtime'] : time();
    }
}

/**
 * Returns the newest modification time among the restore working directory's
 * `restore.dat`, `restore.log`, and the contents of its `uploads/`/`extract/`
 * subdirectories (recursively), or null when none of those exist.
 *
 * Deliberately based on the most recently touched file/subdirectory content —
 * not the restore's start time — so an actively-progressing restore keeps
 * refreshing its own mtime and is never swept while genuinely still running.
 *
 * @param string $project_temp Absolute path to the restore working directory.
 *
 * @return int|null
 */
function newest_restore_activity_mtime(string $project_temp): ?int
{
    $mtime = null;

    foreach (['restore.dat', 'restore.log'] as $file) {
        $path = $project_temp . '/' . $file;
        $file_mtime = @filemtime($path); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

        if (false !== $file_mtime) {
            $mtime = null === $mtime ? $file_mtime : max($mtime, $file_mtime);
        }
    }

    foreach (['uploads', 'extract'] as $subdir) {
        $dir = $project_temp . '/' . $subdir;

        if (!is_dir($dir)) {
            continue;
        }

        $dir_mtime = @filemtime($dir); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        if (false !== $dir_mtime) {
            $mtime = null === $mtime ? $dir_mtime : max($mtime, $dir_mtime);
        }

        try {
            // CATCH_GET_CHILD: an unreadable subdirectory is skipped instead of ending the scan.
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            foreach ($iterator as $file) {
                $mtime = max((int) $mtime, entry_mtime($file));
            }
        } catch (\UnexpectedValueException $e) {
            // Directory not readable — ignore, nothing more we can check for it.
            continue;
        }
    }

    return $mtime;
}

/**
 * Determines whether the restore working directory at `$project_temp` is
 * "abandoned": it exists, and nothing in it has been touched more recently
 * than the (filterable) abandoned-restore threshold.
 *
 * @param string $project_temp Absolute path to the restore working directory.
 *
 * @return bool
 */
function is_restore_abandoned(string $project_temp): bool
{
    if (!is_dir($project_temp)) {
        return false;
    }

    $mtime = newest_restore_activity_mtime($project_temp);

    if (null === $mtime) {
        // Nothing to check activity on inside the directory — fall back to the
        // directory's own mtime so an empty-but-present directory still ages out.
        $dir_mtime = @filemtime($project_temp); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        $mtime = false !== $dir_mtime ? $dir_mtime : null;
    }

    if (null === $mtime) {
        return false;
    }

    /**
     * Filters the age (in seconds) after which an abandoned restore working
     * directory is considered stale and eligible for automatic cleanup.
     *
     * @param int $threshold Threshold in seconds. Default 6 hours.
     */
    $threshold = wpm_apply_filters_typed(
        'integer',
        'backwpup_restore_abandoned_threshold',
        6 * HOUR_IN_SECONDS
    );

    return (time() - $mtime) > $threshold;
}

/**
 * Replaces any restore working-directory token in `$message` with a placeholder.
 *
 * The token is the only secret protecting the working directory on webservers that
 * don't honour .htaccess, so it must never reach a (potentially web-readable) log.
 *
 * @param string $message Message that may contain a restore working-directory path.
 *
 * @return string
 */
function redact_restore_dir_token(string $message): string
{
    return (string) preg_replace(
        '/' . RESTORE_DIR_TOKEN_PATTERN . '/',
        LEGACY_RESTORE_DIR_NAME . '-<redacted>',
        $message
    );
}

/**
 * Returns the absolute paths of every restore working directory present in the
 * current site's uploads directory:
 *   - the legacy, un-tokenized `backwpup-restore` directory (residue from before issue #1772),
 *   - every `backwpup-restore-<20 hex>` directory: the current one, and orphaned ones left
 *     behind when the token option was reset (e.g. by a plugin reinstall or a database
 *     restore) while the directory stayed on disk.
 *
 * Read-only: discovery never generates the token (see restore_dir_token()). Only real
 * directories whose name matches the restore naming scheme qualify: symbolic links and
 * anything else are ignored, so nothing unrelated can ever be cleaned up. The uploads
 * directory is listed rather than globbed, so glob metacharacters in its path are harmless.
 *
 * @return string[]
 */
function restore_dir_candidates(): array
{
    $upload_dir = wp_upload_dir(null, true, false);
    $basedir = untrailingslashit(
        \BackWPup_File::get_absolute_path($upload_dir['basedir'])
    );

    $entries = @scandir($basedir); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
    if (false === $entries) {
        return [];
    }

    $pattern = '/^(?:' . preg_quote(LEGACY_RESTORE_DIR_NAME, '/') . '|' . RESTORE_DIR_TOKEN_PATTERN . ')$/';

    $candidates = [];
    foreach ($entries as $entry) {
        $path = $basedir . '/' . $entry;

        if (!preg_match($pattern, $entry) || is_link($path) || !is_dir($path)) {
            continue;
        }

        $candidates[] = $path;
    }

    return $candidates;
}

/**
 * Cleans up a single restore working directory.
 *
 * Native PHP warnings/notices raised during the operation (e.g. `unlink(<path>):
 * Permission denied`) embed the absolute, tokenized path. They are intercepted and
 * re-reported through RestoreCleanupLogger with the token redacted, instead of letting
 * PHP write them verbatim to the (potentially web-readable) error log. @-suppressed ones
 * are dropped, as PHP would: RestoreCleaner already logs those failures itself. Fatal
 * errors cannot be intercepted this way; they carry code paths, not the working-directory path.
 *
 * Never throws — failures are logged (token redacted) so a single bad directory cannot
 * break the caller.
 *
 * @param string $project_temp      Absolute path to the restore working directory.
 * @param bool   $only_if_abandoned Whether to skip directories that are not abandoned.
 */
function cleanup_restore_dir(string $project_temp, bool $only_if_abandoned): void
{
    $logger = new RestoreCleanupLogger();

    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
    set_error_handler(
        static function (int $errno, string $errstr) use ($logger): bool {
            if (error_reporting() & $errno) {
                $logger->warning(redact_restore_dir_token($errstr));
            }

            return true;
        },
        E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE
    );

    try {
        if ($only_if_abandoned && !is_restore_abandoned($project_temp)) {
            return;
        }

        $cleaner = new \Inpsyde\Restore\Infrastructure\Restore\RestoreCleaner(
            $project_temp,
            $logger
        );
        $cleaner->cleanup();
    } catch (\Throwable $e) {
        $logger->error(
            redact_restore_dir_token(
                sprintf('Failed to clean up a restore working directory (%s): %s', get_class($e), $e->getMessage())
            )
        );
    } finally {
        restore_error_handler();
    }
}

/**
 * Sweeps every restore working directory (see restore_dir_candidates()) and deletes
 * the ones that crossed the abandoned-restore age threshold.
 *
 * Intended to be called from BackWPup_Cron::check_cleanup() (the existing generic,
 * twice-daily maintenance dispatcher); contains no cron-scheduling logic of its own.
 */
function maybe_cleanup_abandoned_restore(): void
{
    foreach (restore_dir_candidates() as $project_temp) {
        cleanup_restore_dir($project_temp, true);
    }
}
