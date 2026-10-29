<?php

declare(strict_types=1);

/**
 * Import Model.
 *
 * @since   1.0.0
 */

namespace Inpsyde\Restore\Api\Module\Database;

use Exception;
use Inpsyde\Restore\AjaxHandler;
use Inpsyde\Restore\Api\Module\Database\Exception\DatabaseQueryException;
use Inpsyde\Restore\Api\Module\Database\Exception\SqlException;
use Inpsyde\Restore\Api\Module\ImportInterface;
use Inpsyde\Restore\Api\Module\Registry;
use Inpsyde\Restore\Infrastructure\EventSourceTrait;
use Psr\Log\LoggerInterface;

/**
 * Imports an SQL file into the database.
 *
 * @psalm-import-type DbPos from ImportFileInterface
 */
final class ImportModel implements ImportInterface
{
    use EventSourceTrait;

    /**
     * @var DatabaseTypeFactory
     */
    private $db_connection;

    /**
     * @var ImportFileFactory
     */
    private $file_import;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Context.
     *
     * The context in which the instance operates. Default is `event_source`, which means
     * the instance is used in a EventSource request.
     *
     * @var string
     */
    private $context = AjaxHandler::EVENT_SOURCE_CONTEXT;

    /**
     * Number of replacements made in database.
     *
     * @var int
     */
    private $replacements;

    public function __construct(
        DatabaseTypeFactory $db_connection,
        ImportFileFactory $file_import,
        Registry $registry,
        LoggerInterface $logger
    ) {
        $this->db_connection = $db_connection;
        $this->file_import = $file_import;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->replacements = 0;
    }

    public function import(): void // phpcs:ignore
    {
        $errors = 0;
        $database = $this->db_connection->database_type();
        if (!$database instanceof DatabaseInterface) {
            throw new \InvalidArgumentException(__('Could not find a valid database', 'backwpup'));
        }

        $database->connect();

        // Set sql_mode to prevent strict error on zero date
        $database->query("SET sql_mode = ''");
        // Prevent issues when dropping a table that has a foreign key.
        $database->query('SET FOREIGN_KEY_CHECKS = 0');

        $file = $this->file_import->import_file('sql');
        if (!$file instanceof ImportFileInterface) {
            throw new \InvalidArgumentException(
                __('Could not find database file importer', 'backwpup')
            );
        }

        $file->set_import_file($this->registry->dbdumpfile);

        // Save file size to calculate percentage of file process for output
        $this->registry->dbdumpsize = $file->get_file_size();

        if ($this->registry->dbdumppos !== null) {
            $file->set_position($this->registry->dbdumppos);
        }

        $query = $file->get_query();

        $response = null;

        // Leave some room for the protocol overhead.
        $max_packet = method_exists($database, 'max_allowed_packet') ? $database->max_allowed_packet() : 0;
        $max_query_length = $max_packet > 0 ? $max_packet - 1024 : 0;

        while (!empty($query)) {
            try {
                $query = $this->replace($query, $database);
                $response = 0;
                foreach ($this->split_query($query, $max_query_length) as $part) {
                    $response += $database->query($part);
                }
            } catch (Exception $exc) {
                $this->logger->error($exc->getMessage());
                ++$errors;

                // Without a connection every following query fails too. Stop with a clear message.
                if (method_exists($database, 'connection_lost') && $database->connection_lost()) {
                    preg_match('/`(\w+)`/i', $query, $match);
                    throw new DatabaseQueryException(
                        sprintf(
                            __(
                                'The database server closed the connection while restoring table %1$s (query size %2$s, server limit max_allowed_packet %3$s). Increase max_allowed_packet on the database server and restore again.',
                                'backwpup'
                            ),
                            $match[1] ?? '?',
                            self::format_bytes(\strlen($query)),
                            $max_packet > 0 ? self::format_bytes($max_packet) : '?'
                        )
                    );
                }
            }

            // Only for event source calls and only when rows are stored into the db.
            if ($response && $this->context === AjaxHandler::EVENT_SOURCE_CONTEXT) {
                preg_match('/`(\w+)`/i', $query, $match);
                $this->echoEventData(
                    'message',
                    [
                        'message' => $match[1] ?? '',
                        'state' => 'progress',
                    ]
                );
            }

            // Update the restoring progress.
            $this->registry->dbdumppos = $file->get_position();
            $this->save_progress();

            $query = $file->get_query();
        }

        $database->query('SET FOREIGN_KEY_CHECKS = 1');

        // After we have finished to import, let's update one last time the progress.
        // We cannot get the 100% in the log because may be the latest query return a value
        // valuable as false,
        // and the progress is not saved the last time.
        $this->registry->dbdumppos = $file->get_position();
        $this->registry->finish_job('db_restore');
        $this->save_progress();

        // Clean.
        unset(
            $file,
            $database,
            $query
        );

        // Report replacements made
        if ($this->replacements > 0) {
            $this->logger->info(
                sprintf(
                    __('Replacements made: %1$d', 'backwpup'),
                    $this->replacements
                )
            );

            // Reset replacements in case there are more queries
            $this->replacements = 0;
        }

        if ($this->context === AjaxHandler::EVENT_SOURCE_CONTEXT) {
            $message = $errors !== 0
                ? __('Database restored with errors.', 'backwpup')
                : __('Database restored successfully.', 'backwpup');

            $this->echoEventData(
                'message',
                [
                    'message' => $message,
                    'state' => 'done',
                ]
            );
        }
    }

    /**
     * Perform replacements in query.
     *
     * @param string            $query    The database query to search and replace
     * @param DatabaseInterface $database The database connection
     *
     * @return string The replaced query
     */
    /**
     * Splits a multi-row INSERT that is larger than the server accepts into several INSERTs.
     *
     * BackWPup and mysqldump write one row per line ("(...),\n"); line breaks inside values are
     * escaped, so a ")," followed by a line break and "(" always separates two rows.
     *
     * @return string[]
     */
    private function split_query(string $query, int $max_length): array
    {
        if ($max_length <= 0 || \strlen($query) <= $max_length) {
            return [$query];
        }

        if (preg_match('/^(INSERT\s+INTO\s+.+?\s+VALUES\s*)\(/is', $query, $matches) !== 1) {
            return [$query];
        }

        $header = rtrim($matches[1]) . "\n";
        $body = rtrim(substr($query, \strlen($matches[1])));
        if (substr($body, -1) === ';') {
            $body = substr($body, 0, -1);
        }

        $rows = preg_split('/\),\r?\n(?=\()/', $body);
        if (!\is_array($rows) || \count($rows) < 2) {
            return [$query];
        }

        $last = \count($rows) - 1;
        foreach ($rows as $index => $row) {
            if ($index !== $last) {
                $rows[$index] = $row . ')';
            }
        }

        $parts = [];
        $current = [];
        $current_length = \strlen($header);
        foreach ($rows as $row) {
            $row_length = \strlen($row) + 2;
            if ($current && $current_length + $row_length > $max_length) {
                $parts[] = $header . implode(",\n", $current) . ';';
                $current = [];
                $current_length = \strlen($header);
            }
            $current[] = $row;
            $current_length += $row_length;
        }
        if ($current) {
            $parts[] = $header . implode(",\n", $current) . ';';
        }

        if (\count($parts) > 1) {
            $this->logger->info(
                sprintf(
                    'Split a %1$s query into %2$d queries to stay below max_allowed_packet.',
                    self::format_bytes(\strlen($query)),
                    \count($parts)
                )
            );
        }

        return $parts;
    }

    private static function format_bytes(int $bytes): string
    {
        return \function_exists('size_format') ? (string) size_format($bytes, 1) : $bytes . ' B';
    }

    private function replace(string $query, DatabaseInterface $database): string
    {
        if (!$this->registry->old_url || !$this->registry->new_url) {
            return $query;
        }

        // Only operate on INSERT queries
        if (strtoupper(substr($query, 0, 6)) !== 'INSERT') {
            return $query;
        }

        // Cache the old query in case there is an error
        $old_query = $query;

        // Attempt to parse the query
        try {
            $matched = preg_match(
                '/^INSERT\s+INTO\s+.*\((?<fields>[^)]+)\)\s+VALUES\s*(?<values>.+)/is',
                $query,
                $matches,
                PREG_OFFSET_CAPTURE
            );

            if ($matched !== 1) {
                throw new SqlException();
            }

            $fields = preg_split('/,\s*/', $matches['fields'][0]);
            if ($fields === false) {
                throw new SqlException();
            }

            $fields = array_map(static function (string $field): string {
                // Allow hierarchical fields
                $parts = explode('.', $field);

                return strtolower(trim(array_pop($parts), '`'));
            }, $fields);

            $position = $matches['values'][1];

            // Tokenize and process until query is parsed
            while ($position < \strlen($query)) {
                $query = $this->processNextToken($query, $position, $fields, $database);
            }
        } catch (SqlException $e) {
            $this->logger->warning(
                sprintf(
                    __(
                        'Malformed query encountered; cannot perform replace. Query: %s',
                        'backwpup'
                    ),
                    $old_query
                )
            );

            // Always return original
            return $old_query;
        }

        return $query;
    }

    /**
     * Process next token in query.
     *
     * @param string            $query    The entire query being parsed
     * @param int               $position The current position of parsing
     * @param array<string>     $fields   The array of fields for this query
     * @param DatabaseInterface $database The database connection
     *
     * @throws SqlException If query is malformed
     *
     * @return string The query parsed at the next token
     */
    private function processNextToken(
        string $query,
        int &$position,
        array &$fields,
        DatabaseInterface $database
    ): string {
        if (key($fields) === 0 && $query[$position] === '(') {
            ++$position;
        }

        $query = $this->processField($query, $position, $fields, $database);

        // If next character is a comma, then we are moving to the next field
        if ($query[$position] === ',') {
            preg_match(
                '/,\s*(?<remaining>.+)/sA',
                $query,
                $matches,
                PREG_OFFSET_CAPTURE,
                $position
            );
            $position = $matches['remaining'][1];
            next($fields);
        } elseif ($query[$position] === ')') {
            // End of record
            ++$position;

            // If comma, we have another record
            if ($query[$position] === ',') {
                preg_match(
                    '/,\s*(?<remaining>.+)/sA',
                    $query,
                    $matches,
                    PREG_OFFSET_CAPTURE,
                    (int) $position
                );
                $position = $matches['remaining'][1];
                reset($fields);
            } elseif ($query[$position] === ';') {
                ++$position;
            }
        }

        return $query;
    }

    /**
     * Process the next field.
     *
     * @param string            $query    The entire query being parsed
     * @param int               $position The current position of parsing
     * @param array<string>     $fields   The array of fields for this query
     * @param DatabaseInterface $database The database connection
     *
     * @throws SqlException If query is malformed
     *
     * @return string The query parsed at the next field
     */
    private function processField(
        string $query,
        int &$position,
        array $fields,
        DatabaseInterface $database
    ): string {
        $matchNonString = preg_match(
            '/[^\'",)]+(?=,\s*|\))(?<remaining>.+)/sA',
            $query,
            $matches,
            PREG_OFFSET_CAPTURE,
            $position
        );
        $matchShortString = !$matchNonString && preg_match(
            '/(?<value>([\'"]).?\2)(?=,\s*|\))(?<remaining>.+)/sA',
            $query,
            $matches,
            PREG_OFFSET_CAPTURE,
            $position
        );
        /** @var array<string, string> $matchesLong */
        $matchesLong = [];
        $matchLongString = !$matchNonString && !$matchShortString && preg_match(
            '/(?<value>([\'"]).+?[^\\\]\2)(?=,\s*|\))(?<remaining>.+)/sA',
            $query,
            $matchesLong,
            0,
            $position
        );

        if ($matchNonString) {
            // Non-string parameter, which we don't care about
            $position = $matches['remaining'][1];
        } elseif ($matchShortString) {
            // One or zero characters in string value
            $position = $matches['remaining'][1];
        } elseif ($matchLongString) {
            $value = $matchesLong['value'];

            if (current($fields) === 'guid') {
                // Skip any guid field
                $position += \strlen($value);
            } else {
                $new_value = $this->replaceValue(
                    $value,
                    $database
                );

                $query = substr_replace(
                    $query,
                    $new_value,
                    $position,
                    \strlen($value)
                );

                $position += \strlen($new_value);
            }
        } else {
            // Does not match, so error
            throw new SqlException();
        }

        return $query;
    }

    /**
     * Perform replacement for migration on a specific value.
     *
     * @param string            $value    The value to search and replace
     * @param DatabaseInterface $database The database connection
     *
     * @return string The replaced value
     */
    private function replaceValue(string $value, DatabaseInterface $database): string
    {
        static $esc_old_url, $esc_new_url;
        if (!isset($esc_old_url)) {
            $esc_old_url = $database->escape($this->registry->old_url);
        }
        if (!isset($esc_new_url)) {
            $esc_new_url = $database->escape($this->registry->new_url);
        }

        // Fix serialized data first
        // It should be preceded by either ' or " (beginning of value in DB),
        // ; (from end of previous serialized value),
        // or { (from beginning of serialized array).
        // It should consist of: s:<length>:\"<value>\";
        // It should be followed by ' or " (end of value in DB),
        // } (end of serialized array),
        // or character denoting beginning of another serialized value.
        preg_match_all(
            '/(?<=[\'";{])(?<serialized>s:(?<length>\d+):\\\"(?<value>.+?)\\\";)'
            . '(?=\'|"|\}|[sidbaO]:)/',
            $value,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            // Only replace if the old URL is contained within the value
            if (strpos($match['value'], $esc_old_url) === false) {
                continue;
            }

            $replaced = str_replace($esc_old_url, $esc_new_url, $match['value'], $count);
            $urlLengthDiff = \strlen($this->registry->new_url) - \strlen($this->registry->old_url);
            $length = (int) $match['length'] + $urlLengthDiff * $count;
            $value = str_replace(
                $match['serialized'],
                "s:{$length}:\\\"{$replaced}\\\";",
                $value
            );
            $this->replacements += $count;
        }

        $value = str_replace(
            $esc_old_url,
            $esc_new_url,
            $value,
            $count
        );
        $this->replacements += $count;

        return $value;
    }

    /**
     * Save Restore Progress.
     *
     * Helper method for calculating the progress of DB file restore and saving it to registry.
     */
    private function save_progress(): void
    {
        $progress = (int) floor(
            ($this->registry->dbdumppos['pos'] / $this->registry->dbdumpsize) * 100
        );

        // Log valid progress and not log the same value multiple times.
        if ($progress > 0 && $progress > $this->registry->migration_progress) {
            $this->registry->update_progress($progress);
            $this->logger->info(
                sprintf(
                    'SQL File restore: dbdumpos [%d], dbdumpsize [%d], progress [%d]',
                    $this->registry->dbdumppos['pos'],
                    $this->registry->dbdumpsize,
                    $progress
                )
            );
        }
    }
}
