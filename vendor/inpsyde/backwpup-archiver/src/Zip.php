<?php

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
use SplFileInfo;
use ZipArchive;

/**
 * Class Zip
 *
 * @internal
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class Zip implements ArchiveFileOperator, Closable
{
    /**
     * @var ZipArchive
     */
    private $zipArchive;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var bool
     */
    private $isOpen = false;

    /**
     * Zip constructor
     * @param ZipArchive $zipArchive
     * @param $fileName
     * @throws InvalidArgumentException If file name isn't a valid path
     */
    public function __construct(ZipArchive $zipArchive, $fileName)
    {
        // TODO Improve assert for Filename, check traversal path etc...
        Assert::path($fileName);

        $this->zipArchive = $zipArchive;
        $this->fileName = $fileName;
    }

    /**
     * Create the Archive file
     *
     * @return void
     * @throws ArchiveException If the archive cannot be opened
     */
    public function create()
    {
        !$this->isOpen and $this->openWithFlag(ZipArchive::CREATE);
    }

    /**
     * Open the Archive for Read
     *
     * @return void
     * @throws ArchiveException If the archive cannot be opened
     */
    public function open()
    {
        !$this->isOpen and $this->openWithFlag(ZipArchive::ER_READ);
    }

    /**
     * Close the Archive File
     *
     * @return void
     */
    public function close()
    {
        $this->isOpen and $this->zipArchive->close();

        $this->isOpen = false;
    }

    /**
     * @inheritDoc
     */
    public function isValid()
    {
        try {
            $this->open();
        } catch (ArchiveException $exc) {
            return false;
        }

        return ZipArchive::ER_OK === $this->zipArchive->status;
    }

    /**
     * @inheritDoc
     */
    public function extractTo($destination)
    {
        Assert::path($destination);

        $this->open();

        $extracted = $this->zipArchive->extractTo($destination);

        if (!$extracted) {
            throw ArchiveException::becauseArchiveCannotBeExtracted($this->fileName);
        }
    }

    /**
     * @inheritDoc
     */
    public function extractFileByIndex($index, $destination)
    {
        Assert::greaterThanEq($index, 0);
        Assert::path($destination);

        $this->open();

        $fileName = $this->zipArchive->getNameIndex($index);

        if (!$fileName) {
            throw ArchiveException::forInvalidFileIndex($index);
        }

        $extracted = $this->zipArchive->extractTo($destination, $fileName);

        if (!$extracted) {
            throw ArchiveException::becauseFileCannotBeExtracted($fileName);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete()
    {
        $this->close();

        // TODO Would be better an abstraction?
        file_exists($this->fileName) and unlink($this->fileName);
    }

    /**
     * @inheritDoc
     */
    public function content()
    {
        $this->open();

        $list = [];
        $numEntries = $this->zipArchive->numFiles;

        for ($count = 0; $count < $numEntries; ++$count) {
            $item = $this->zipArchive->getNameIndex($count);
            if (!$item) {
                throw ArchiveException::forContentUnretrievable();
            }

            $list[] = $item;
        }

        return $list;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        $this->open();

        return $this->zipArchive->numFiles;
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

        $this->create();

        $added = $this->zipArchive->addFile($fileName);

        if (!$added) {
            throw ArchiveException::forFileCannotBeAddBecauseInternalError($fileName);
        }
    }

    /**
     * @inheritDoc
     */
    public function fetch(SplFileInfo $file)
    {
        $this->open();

        $fileName = $file->getPathname();
        $temDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $filePath = "{$temDir}/{$fileName}";
        $extracted = $this->zipArchive->extractTo($temDir, $fileName);

        if (!$extracted) {
            throw ArchiveException::becauseFileCannotBeExtracted($fileName);
        }

        return new SplFileInfo($filePath);
    }

    /**
     * @inheritDoc
     */
    public function exists(SplFileInfo $file)
    {
        $this->open();

        $fileName = $file->getFilename();
        $index = $this->zipArchive->locateName($fileName);

        return $index !== false;
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
        $this->open();

        $fileName = $file->getFilename();
        $deleted = $this->zipArchive->deleteName($fileName);

        if (!$deleted) {
            throw ArchiveException::becauseFileCannotBeDeleted($fileName);
        }
    }

    /**
     * @inheritDoc
     */
    public function fileNameByIndex($index)
    {
        Assert::greaterThanEq($index, 0);

        $fileName = $this->zipArchive->getNameIndex($index);

        if (!$fileName) {
            throw ArchiveException::forInvalidFileIndex($index);
        }

        return $fileName;
    }

    /**
     * Open a Zip File With the Specified Flag
     *
     * @param int $flag
     * @return void
     * @throws ArchiveException
     */
    private function openWithFlag($flag)
    {
        $opened = $this->zipArchive->open($this->fileName, $flag);

        if ($opened !== true) {
            throw ArchiveException::becauseArchiveCannotBeOpened($opened);
        }

        $this->isOpen = true;
    }
}
