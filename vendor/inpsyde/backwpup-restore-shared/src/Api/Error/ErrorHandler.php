<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Error;

use Inpsyde\Restore\Api\Module\Registry;
use Psr\Log\LoggerInterface;

/**
 * Class ErrorHandler.
 *
 * @author  Hans-Helge Buerger
 */
class ErrorHandler
{
    /**
     * Holder var for Monolog logger instance.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * ErrorHandler constructor.
     */
    public function __construct(LoggerInterface $logger, Registry $registry)
    {
        $this->logger = $logger;
        $this->registry = $registry;
    }

    /**
     * Register the error handler as default.
     */
    public function register(): void
    {
        // phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
        set_error_handler(function (int $error_type, string $error_text, string $error_file, int $error_line): bool {
            return $this->handle_error($error_type, $error_text, $error_file, $error_line);
        });
        // phpcs:enable
    }

    /**
     * Restore the default error handler.
     */
    public function unregister(): void
    {
        restore_error_handler();
    }

    /**
     * Log every PHP error and don't pass it to user.
     *
     * @param int    $error_type Number of error
     * @param string $error_text Error message
     * @param string $error_file File in which error raise
     * @param int    $error_line Line in which error raised
     *
     * @return bool true; for suppressing PHP Internal error handling
     */
    public function handle_error(int $error_type, string $error_text, string $error_file, int $error_line): bool
    {
        // Only reset registry if error not suppressed
        // phpcs:disable WordPress.PHP.DiscouragedPHPFunctions, WordPress.PHP.DevelopmentFunctions
        if ((error_reporting() & $error_type) !== 0) {
            $this->registry->reset_registry();
        }
        // phpcs:enable

        $msg = '[' . $error_type . '] ' . $error_text . ' ';
        $msg .= '(' . $error_file . ' on Line ' . $error_line . ') ';
        $msg .= '| PHP ' . PHP_VERSION . ' (' . PHP_OS . ')';

        // Log message according to its error type
        switch ($error_type) {
            case E_USER_ERROR:
                $this->logger->error($msg);
                break;

            case E_USER_NOTICE:
                $this->logger->notice($msg);
                break;

            case E_USER_WARNING:
            default:
                $this->logger->warning($msg);
                break;
        }

        // Return true to suppress PHP internal error handling
        return true;
    }
}
