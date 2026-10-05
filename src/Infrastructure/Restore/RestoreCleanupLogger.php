<?php
/**
 * PSR-3 logger for restore working-directory cleanups (abandoned-restore sweep and manual delete).
 */

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Restore;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Writes RestoreCleaner's warning-and-above log records to the PHP error log
 * (WP_DEBUG-gated), matching the existing diagnostic pattern already used elsewhere in this
 * module (see TemplateLoader::get_restore_capabilities()) rather than silently discarding
 * them via NullLogger.
 *
 * The twice-daily abandoned-restore sweep runs unattended; without this, a failure to clean up
 * exposed restore residue would leave no trace anywhere. Routine debug/info progress messages
 * (emitted by RestoreCleaner on every successful run) are intentionally not logged, to avoid
 * spamming the error log on every twice-daily no-op/success sweep.
 *
 * Messages are passed through redact_restore_dir_token() before being written: the
 * restore working-directory token must never reach the (potentially web-readable) error log.
 * The PSR-3 context is intentionally never written, since it carries raw paths.
 */
final class RestoreCleanupLogger extends AbstractLogger
{
    private const LOGGED_LEVELS = [
        LogLevel::WARNING,
        LogLevel::ERROR,
        LogLevel::CRITICAL,
        LogLevel::ALERT,
        LogLevel::EMERGENCY,
    ];

    /**
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array<string, mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        if (!in_array((string) $level, self::LOGGED_LEVELS, true)) {
            return;
        }

        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        error_log(
            sprintf(
                '[BackWPup Restore] Restore cleanup %s: %s',
                (string) $level,
                redact_restore_dir_token((string) $message)
            )
        ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
}
