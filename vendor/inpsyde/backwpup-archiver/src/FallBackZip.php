<?php # -*- coding: utf-8 -*-

/*
 * This file is part of the BackWPup Archiver package.
 *
 * (c) Inpsyde <hello@inpsyde.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\BackWPup\Archiver;

use Inpsyde\Assert\Assert;
use InvalidArgumentException;
use PclZip;
use SplFileInfo;

/**
 * Class PclZip
 *
 * @inthernal
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class FallBackZip implements ArchiveFileOperator
{
    /**
     * @var string
     */
    private $fileName;

    /**
     * @var PclZip
     */
    private $pclZip;

    /**
     * @var bool
     */
    private $isOpen = false;

    /**
     * FallBackZip constructor
     * @param PclZip $pclZip
     * @param $fileName
     * @throws InvalidArgumentException
     */
    public function __construct(PclZip $pclZip, $fileName)
    {
        Assert::path($fileName);

        $this->pclZip = $pclZip;
        $this->fileName = $fileName;
    }

    /**
     * @inheritDoc
     */
    public function delete()
    {
        file_exists($this->fileName) and unlink($this->fileName);
        $this->isOpen = false;
    }

    /**
     * @inheritDoc
     */
    public function extractTo($destination)
    {
        Assert::path($destination);

        $destination = $this->extractDirNameByDestination($destination);

        foreach ($this->content() as $entryName) {
            $this->assertEntryWithinDestination($destination, $entryName);
        }

        // phpcs:ignore NeutronStandard.Extract.DisallowExtract.Extract
        $extracted = $this->pclZip->extract(
            PCLZIP_OPT_PATH,
            $destination,
            PCLZIP_OPT_EXTRACT_DIR_RESTRICTION,
            $destination
        );

        if ($extracted <= 0) {
            throw ArchiveException::becauseArchiveCannotBeExtracted($this->fileName);
        }
    }

    /**
     * @inheritDoc
     */
    public function isValid()
    {
        $properties = $this->pclZip->properties();

        if (!isset($properties['status'])) {
            return false;
        }

        return 'ok' === strtolower($properties['status']);
    }

    /**
     * @inheritDoc
     */
    public function extractFileByIndex($index, $destination)
    {
        Assert::greaterThanEq($index, 0);
        Assert::path($destination);

        $this->assertEntryWithinDestination($destination, $this->fileNameByIndex($index));

        $extracted = $this->pclZip->extractByIndex(
            $index,
            PCLZIP_OPT_PATH,
            $destination,
            PCLZIP_OPT_EXTRACT_DIR_RESTRICTION,
            $destination
        );

        if ($extracted <= 0) {
            throw ArchiveException::forInvalidFileIndex($index);
        }
    }

    /**
     * @inheritDoc
     */
    public function content()
    {
        $list = $this->pclZip->listContent();

        if ($list <= 0) {
            throw ArchiveException::forContentUnretrievable();
        }

        $list = array_map(static function ($item) {
            return $item['filename'];
        }, $list);

        /** @noinspection UnnecessaryCastingInspection */
        return (array)$list;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        $properties = $this->pclZip->properties();

        if (!is_array($properties)) {
            throw ArchiveException::forContentUnretrievable();
        }

        return isset($properties['nb']) ? $properties['nb'] : 0;
    }

    /**
     * @inheritDoc
     */
    public function add(SplFileInfo $file)
    {
        $fileName = $file->getPathname();

        if (!$file->isReadable()) {
            throw FileException::becauseFileIsNotReadable($fileName);
        }

        $added = $this->pclZip->add($fileName);

        if ($added <= 0) {
            throw ArchiveException::forFileCannotBeAddBecauseInternalError($fileName);
        }
    }

    /**
     * @inheritDoc
     */
    public function fetch(SplFileInfo $file)
    {
        $fileName = $file->getPathname();
        $temDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $filePath = "{$temDir}/{$fileName}";

        $this->assertEntryWithinDestination($temDir, $fileName);

        // phpcs:ignore NeutronStandard.Extract.DisallowExtract.Extract
        $extracted = $this->pclZip->extract(
            PCLZIP_OPT_PATH,
            $temDir,
            PCLZIP_OPT_EXTRACT_DIR_RESTRICTION,
            $temDir,
            PCLZIP_OPT_BY_NAME,
            $fileName
        );

        if ($extracted <= 0) {
            throw ArchiveException::becauseFileCannotBeExtracted($fileName);
        }

        return new SplFileInfo($filePath);
    }

    /**
     * @inheritDoc
     */
    public function exists(SplFileInfo $file)
    {
        $found = false;
        $searchFileName = $file->getFilename();
        $content = $this->content();

        foreach ($content as $fileName) {
            if (strpos($searchFileName, $fileName) !== false) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * @inheritDoc
     */
    public function replace(SplFileInfo $file)
    {
        $this->remove($file);
        $this->add($file);
    }

    /**
     * @inheritDoc
     */
    public function remove(SplFileInfo $file)
    {
        $fileName = $file->getPathname();
        $deleted = $this->pclZip->delete(PCLZIP_OPT_BY_NAME, $fileName);

        if ($deleted <= 0) {
            throw ArchiveException::becauseFileCannotBeDeleted($fileName);
        }
    }

    /**
     * @inheritDoc
     */
    public function fileNameByIndex($index)
    {
        Assert::greaterThanEq($index, 0);

        $content = $this->content();

        if (!array_key_exists($index, $content)) {
            throw ArchiveException::forInvalidFileIndex($index);
        }

        Assert::string($content[$index]);

        return $content[$index];
    }

    /**
     * Build the Destination for Extract Archive Content
     *
     * @param $destination
     * @return string
     */
    private function extractDirNameByDestination($destination)
    {
        $baseName = basename($this->fileName, '.zip');
        $destination = rtrim($destination, DIRECTORY_SEPARATOR) . "/{$baseName}";

        return $destination;
    }

    /**
     * Assert an Archive Entry Resolves Inside the Extraction Destination
     *
     * PclZip's own PCLZIP_OPT_EXTRACT_DIR_RESTRICTION relies on PclZipUtilPathInclusion(),
     * which, per its own docblock, "does not support '.' or '..' statements": it compares
     * path segments position by position without resolving traversal sequences, so an entry
     * name like "../../pwned.txt" passes that check undetected. This performs the actual
     * path resolution ourselves before any extraction call is made, so a crafted entry
     * cannot cause a file to be written outside $destination.
     *
     * @param string $destination
     * @param string $entryName
     * @throws ArchiveException
     */
    private function assertEntryWithinDestination($destination, $entryName)
    {
        $destinationPath = rtrim(self::normalizePath($destination), '/');
        $entryPath = self::normalizePath($destinationPath . '/' . $entryName);

        if ($entryPath !== $destinationPath && strpos($entryPath, $destinationPath . '/') !== 0) {
            throw ArchiveException::becauseEntryEscapesDestination($entryName);
        }
    }

    /**
     * Resolve '.' and '..' Segments in a Path Without Touching the Filesystem
     *
     * @param string $path
     * @return string
     */
    private static function normalizePath($path)
    {
        $path = str_replace('\\', '/', $path);
        $isAbsolute = strpos($path, '/') === 0;

        $resolved = array();
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($resolved);
                continue;
            }
            $resolved[] = $segment;
        }

        return ( $isAbsolute ? '/' : '' ) . implode('/', $resolved);
    }
}
