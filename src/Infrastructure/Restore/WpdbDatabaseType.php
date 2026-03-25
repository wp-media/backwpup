<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Restore;

use Inpsyde\BackWPup\Infrastructure\Database\WpdbConnection;
use Inpsyde\Restore\Api\Exception\ExceptionLinkHelper;
use Inpsyde\Restore\Api\Module\Database\DatabaseInterface;
use Inpsyde\Restore\Api\Module\Database\Exception\DatabaseConnectionException;
use Inpsyde\Restore\Api\Module\Database\Exception\DatabaseQueryException;
use Inpsyde\Restore\Api\Module\Registry;
use Psr\Log\LoggerInterface;

final class WpdbDatabaseType implements DatabaseInterface
{
    /**
     * @var WpdbConnection|null
     */
    private $wpdb;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function connect(): void
    {
        $this->wpdb = new WpdbConnection(
            $this->registry->dbuser,
            $this->registry->dbpassword,
            $this->registry->dbname,
            $this->registry->dbhost
        );

        if (!$this->wpdb->check_connection(false) || !$this->wpdb->ready) {
            $error = $this->wpdb->last_error ?: __('Unknown database error', 'backwpup');
            throw new DatabaseConnectionException(
                wp_kses_post(
                    ExceptionLinkHelper::translateWithAppropiatedLink(
                        sprintf(
                            /* translators: 1: error code, 2: error message. */
                            esc_html__( 'Cannot connect to MySQL database %1$d: %2$s', 'backwpup' ),
                            0,
                            $error
                        ),
                        'DATABASE_CONNECTION_PROBLEMS'
                    )
                )
            );
        }

        if (!empty($this->registry->dbcharset)) {
			$query = $this->wpdb->prepare('SET NAMES %s', $this->registry->dbcharset);
			$this->wpdb->query($query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.

            if ($this->wpdb->last_error !== '') {
                throw new DatabaseConnectionException(
                    wp_kses_post(
                        ExceptionLinkHelper::translateWithAppropiatedLink(
                            $this->wpdb->last_error,
                            'DATABASE_CONNECTION_PROBLEMS'
                        )
                    )
                );
            }

            $this->wpdb->charset = $this->registry->dbcharset;
        }

        $this->logger->info(
            sprintf(
                "Current character set: %s\n",
                $this->wpdb->charset ?: $this->registry->dbcharset
            )
        );
    }

    public function query(string $query): int
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Raw SQL import statements are executed as-is.
        $result = $this->wpdb->query($query);

        if ($this->wpdb->last_error !== '') {
            throw new DatabaseQueryException(
                sprintf(
                    /* translators: 1: database error message, 2: SQL query. */
                    esc_html__( 'Database error %1$s for query %2$s', 'backwpup' ),
                    esc_html( $this->wpdb->last_error ),
                    esc_html( $query )
                )
            );
        }

        return (int) $result;
    }

    public function escape(string $input): string
    {
        return $this->wpdb->remove_placeholder_escape($this->wpdb->_real_escape($input));
    }

    public function disconnect(): bool
    {
        if ($this->wpdb !== null) {
            return $this->wpdb->close();
        }

        return false;
    }

    public function can_use(): bool
    {
        return true;
    }

    public function set_logger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
