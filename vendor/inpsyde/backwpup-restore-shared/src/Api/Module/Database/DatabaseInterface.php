<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Database;

use Inpsyde\Restore\Api\Module\Database\Exception\DatabaseConnectionException;
use Inpsyde\Restore\Api\Module\Database\Exception\DatabaseQueryException;
use Psr\Log\LoggerInterface;

interface DatabaseInterface
{
    /**
     * Connect to Database.
     *
     * @throws DatabaseConnectionException
     */
    public function connect(): void;

    /**
     * Do an SQL query.
     *
     * @param string $query The SQl query
     *
     * @throws DatabaseQueryException In case the query cannot be performed
     *
     * @return int Affected/queried rows
     */
    public function query(string $query): int;

    /**
     * Escape a string for the database.
     *
     * @param string $input The string to escape
     *
     * @return string The escaped string
     */
    public function escape(string $input): string;

    /**
     * Disconnect from Database.
     *
     * @return bool true on success, false otherwise
     */
    public function disconnect(): bool;

    /**
     * Can this Database type be used?
     *
     * @return bool true if mysqli class exists, false otherwise
     */
    public function can_use(): bool;

    /**
     * Setter method to add a logger.
     *
     * @param LoggerInterface $logger The logger instance, e.g. Monolog.
     */
    public function set_logger(LoggerInterface $logger): void;
}
