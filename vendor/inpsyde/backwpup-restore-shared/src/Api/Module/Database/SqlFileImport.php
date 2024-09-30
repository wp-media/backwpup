<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Database;

use Inpsyde\Restore\Api\Module\Database\Exception\DatabaseFileException;
use InvalidArgumentException;

class SqlFileImport implements ImportFileInterface
{
    /**
     * @var resource|closed-resource|null
     */
    private $fileHandle;

    /**
     * @var string[] Read line cache
     */
    private $lineCache = [0 => ''];

    /**
     * @var string
     */
    private $delimiter = ';';

    public function __construct()
    {
    }

    public function __destruct()
    {
        $this->close_file();
    }

    public function set_import_file($file): bool
    {
        if (!is_file($file)) {
            throw new DatabaseFileException(
                sprintf(__('SQL file %1$s does not exist', 'backwpup'), $file)
            );
        }

        if (!is_readable($file)) {
            throw new DatabaseFileException(
                sprintf(__('SQL file %1$s is not readable', 'backwpup'), $file)
            );
        }

        // Close existing handle if open
        $this->close_file();

        $this->open_file($this->get_file_path($file));

        return $this->is_file_open();
    }

    public function get_file_size(): int
    {
        if (!$this->is_file_open()) {
            throw new DatabaseFileException(
                __('Could not get size of SQL file', 'backwpup')
            );
        }

        $filePath = stream_get_meta_data($this->fileHandle)['uri'];

        if (preg_match('/\.gz|\.bz2$/', $filePath)) {
            // File is compressed, so have to calculate size manually
            return $this->calculateUncompressedFileSize($filePath);
        }

        $size = filesize($filePath);
        if ($size === false) {
            throw new DatabaseFileException(
                __('Could not get size of SQL file', 'backwpup')
            );
        }

        return $size;
    }

    public function get_position(): array
    {
        if (!$this->is_file_open()) {
            throw new DatabaseFileException(__('Cannot get SQL file position', 'backwpup'));
        }

        $pos = ftell($this->fileHandle);

        if ($pos === false) {
            throw new DatabaseFileException(__('Cannot get SQL file position', 'backwpup'));
        }

        return ['pos' => $pos, 'line_cache' => $this->lineCache];
    }

    public function set_position(array $position): bool
    {
        // @phpstan-ignore-next-line
        if (!isset($position['pos'])) {
            throw new DatabaseFileException(__('SQL file position not set', 'backwpup'));
        }

        // @phpstan-ignore-next-line
        if (isset($position['line_cache'])) {
            $this->lineCache = $position['line_cache'];
        } else {
            throw new DatabaseFileException(__('SQL file line cache not set', 'backwpup'));
        }

        if (!$this->is_file_open()) {
            throw new DatabaseFileException(__('SQL file is not open', 'backwpup'));
        }

        $result = fseek($this->fileHandle, $position['pos']);
        if ($result === -1) {
            throw new DatabaseFileException(__('Cannot set SQL file position', 'backwpup'));
        }

        return true;
    }

    public function get_query(): string
    {
        $query = '';
        $line = '';

        while ($line !== false) {
            $line = $this->get_line_from_file();
            if (!$line) {
                continue;
            }
            if (substr($line, 0, 2) === '--') {
                continue;
            }
            if (substr($line, 0, 1) === '#') {
                continue;
            }
            if (preg_match('/^DELIMITER\s+([^ ]+)/i', $line, $matches) === 1) {
                $this->delimiter = trim($matches[1]);

                continue;
            }

            $query .= $line;
            $delimiterLength = \strlen($this->get_delimiter());
            if (
                preg_match(
                    '/' . preg_quote($this->get_delimiter(), '/') . '\s*$/',
                    $query
                ) === 1
            ) {
                $query = substr(rtrim($query), 0, -$delimiterLength) . ';';
                break;
            }
            $query .= "\n";
        }

        return $query;
    }

    public function is_sql_file(string $file): bool
    {
        if (!is_file($file)) {
            return false;
        }

        try {
            $content = file_get_contents($this->get_file_path($file), false, null, 0, 1024 * 1024) ?: '';
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return stristr($content, 'INSERT') !== false && stristr($content, 'CREATE TABLE') !== false;
    }

    /**
     * Get the current query delimiter.
     *
     * Defaults to ; but can be set with the DELIMITER keyword.
     *
     * @return string The current delimiter
     */
    protected function get_delimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * Read sql query from file query by query.
     *
     * @return string|false
     */
    protected function get_line_from_file()
    {
        if (!$this->is_file_open()) {
            throw new DatabaseFileException(__('SQL file is not open', 'backwpup'));
        }

        if (!$this->lineCache) {
            return false;
        }

        if (\count($this->lineCache) === 1) {
            $file_content = '';

            do {
                $file_content .= fread($this->fileHandle, 8192);
            } while ($file_content !== '' && strpos($file_content, "\n") === false);

            $file_lines = explode("\n", $file_content);

            if (!empty($this->lineCache)) {
                $file_lines[0] = array_shift($this->lineCache) . $file_lines[0];
            }

            $this->lineCache = $file_lines;
        }

        return array_shift($this->lineCache);
    }

    /*
     * Gets the file name with appropriate stream wrapper.
     *
     * If the file has a .sql.gz extension, it is wrapped with the zlib stream wrapper.
     *
     * If the file has a .sql.bz2 extension, it is wrapped with the compress.bzip2 stream wrapper.
     *
     * @throws InvalidArgumentException Thrown if the extension is not .sql, .sql.gz, or .sql.bz2
     */
    protected function get_file_path(string $file): string
    {
        if (preg_match('/\.sql\.gz$/i', $file) === 1) {
            return 'compress.zlib://' . $file;
        }
        if (preg_match('/\.sql\.bz2$/i', $file) === 1) {
            return 'compress.bzip2://' . $file;
        }
        if (preg_match('/\.sql$/i', $file) === 1) {
            return $file;
        }
        if (preg_match('/^[^.]+$/', $file) === 1) {
            throw new InvalidArgumentException(
                __('Missing SQL file extension', 'backwpup')
            );
        }

        throw new InvalidArgumentException(
            sprintf(
                __('Invalid SQL file extension .%1$s', 'backwpup'),
                pathinfo($file, PATHINFO_EXTENSION)
            )
        );
    }

    /**
     * Open the file for reading.
     *
     * @throws DatabaseFileException
     */
    protected function open_file(string $filename): void
    {
        $handle = fopen($filename, 'r');

        if ($handle === false) {
            throw new DatabaseFileException(
                sprintf(__('SQL file %1$s could not be opened', 'backwpup'), $filename)
            );
        }

        $this->fileHandle = $handle;
    }

    /**
     * @psalm-assert-if-true resource $this->fileHandle
     */
    protected function is_file_open(): bool
    {
        return \is_resource($this->fileHandle);
    }

    protected function close_file(): void
    {
        if ($this->is_file_open()) {
            fclose($this->fileHandle);
        }
    }

    /**
     * Calculate size of uncompressed files.
     *
     * The size of compressed files cannot be calculated with `filesize()`, so we have to calculate it manually.
     */
    private function calculateUncompressedFileSize(string $filePath): int
    {
        // Open a new handle since bzip2 files aren't seekable
        $handle = fopen($filePath, 'r');

        $size = 0;

        while (!feof($handle)) {
            $data = fread($handle, 4096);
            $size += strlen($data);
        }

        fclose($handle);

        return $size;
    }
}
