<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Database;

use Inpsyde\Restore\Api\Module\Database\Exception\DatabaseFileException;

/**
 * @psalm-type DbPos=array{pos: int, line_cache: string[]}
 */
interface ImportFileInterface
{
    /**
     * File to import.
     *
     * @param string $file The file to read
     *
     * @throws DatabaseFileException If problems to read the file
     */
    public function set_import_file(string $file): bool;

    /**
     * Get Position.
     *
     * Get Position on import file for store and later going on.
     *
     * @throws DatabaseFileException If the current position within
     *                               the file cannot be read
     *
     * @return DbPos
     */
    public function get_position(): array;

    /**
     * Set position on import file.
     *
     * @param DbPos $position The position information to set the pointer for the file
     *
     * @throws DatabaseFileException If something went wrong reading
     *                               the file
     */
    public function set_position(array $position): bool;

    /**
     * Get query to import.
     *
     * @return string The query string
     */
    public function get_query(): string;

    /**
     * Get File Size.
     *
     * @return int The size of the file
     */
    public function get_file_size(): int;
}
