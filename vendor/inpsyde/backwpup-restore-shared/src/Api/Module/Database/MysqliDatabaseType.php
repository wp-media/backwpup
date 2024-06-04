<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Database;

use Inpsyde\Restore\Api\Exception\ExceptionLinkHelper;
use Inpsyde\Restore\Api\Module\Database\Exception\DatabaseConnectionException;
use Inpsyde\Restore\Api\Module\Database\Exception\DatabaseQueryException;
use Inpsyde\Restore\Api\Module\Registry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Translator;

/**
 * Class MysqliDatabaseType.
 */
final class MysqliDatabaseType implements DatabaseInterface
{
    /**
     * @var \mysqli
     */
    private $mysqli;

    /**
     * @var Translator
     */
    private $translation;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Registry $registry, Translator $translation)
    {
        $this->registry = $registry;
        $this->translation = $translation;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function connect(): void
    {
        $connection = mysqli_init();

        if (!$connection) {
            throw new DatabaseConnectionException(
                $this->translation->trans('Cannot init MySQLi database connection')
            );
        }

        $this->mysqli = $connection;

        if (!$this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5)) { // phpcs:ignore
            throw new DatabaseConnectionException(
                $this->translation->trans('Setting of MySQLi connection timeout failed')
            );
        }

        $dbhost = $this->registry->dbhost;
        $dbport = 0;
        $dbsocket = '';
        $dbcharset = $this->registry->dbcharset;

        if (strstr($dbhost, ':')) {
            $hostparts = explode(':', $this->registry->dbhost, 2);
            $hostparts[0] = trim($hostparts[0]);
            $hostparts[1] = trim($hostparts[1]);

            $dbhost = empty($hostparts[0]) ? null : $hostparts[0];

            if (is_numeric($hostparts[1])) {
                $dbport = (int) $hostparts[1];
            } else {
                $dbsocket = $hostparts[1];
            }
        }

        if (!$dbhost) {
            throw new DatabaseConnectionException(
                $this->translation->trans('No valid connection data. Please check the host is reachable.')
            );
        }

        // Connect to Database.
        $connect = @$this->mysqli->real_connect(
            $dbhost,
            $this->registry->dbuser,
            $this->registry->dbpassword,
            $this->registry->dbname,
            $dbport,
            $dbsocket
        );
        if (!$connect) {
            throw new DatabaseConnectionException(
                ExceptionLinkHelper::translateWithAppropiatedLink(
                    $this->translation,
                    sprintf(
                        $this->translation->trans('Cannot connect to MySQL database %1$d: %2$s'),
                        mysqli_connect_errno(), // phpcs:ignore
                        mysqli_connect_error() ?? '' // phpcs:ignore
                    ),
                    'DATABASE_CONNECTION_PROBLEMS'
                )
            );
        }

        if (!$this->mysqli->set_charset($dbcharset)) {
            throw new DatabaseConnectionException(
                ExceptionLinkHelper::translateWithAppropiatedLink(
                    $this->translation,
                    $this->mysqli->error,
                    'DATABASE_CONNECTION_PROBLEMS'
                )
            );
        }

        $this->logger->info(
            sprintf(
                "Current character set: %s\n",
                $this->mysqli->character_set_name()
            )
        );
    }

    public function query($query): int
    {
        $res = $this->mysqli->query($query);
        if ($this->mysqli->error !== '') {
            throw new DatabaseQueryException(
                sprintf(
                    $this->translation->trans('Database error %1$s for query %2$s'),
                    $this->mysqli->error,
                    $query
                )
            );
        }

        if ($res instanceof \mysqli_result && $res->num_rows > 0) {
            return $res->num_rows;
        }
        if ($this->mysqli->affected_rows > 0) {
            return $this->mysqli->affected_rows;
        }

        return 0;
    }

    public function escape($input): string
    {
        return mysqli_real_escape_string($this->mysqli, $input);
    }

    public function disconnect(): bool
    {
        if ($this->mysqli !== null) {
            $this->mysqli->close();

            return true;
        }

        return false;
    }

    public function can_use(): bool
    {
        return class_exists('Mysqli');
    }

    public function set_logger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
