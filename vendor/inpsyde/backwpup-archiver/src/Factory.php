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

use InvalidArgumentException;
use PclZip;
use ZipArchive;
use Inpsyde\Assert\Assert;

/**
 * Class Factory
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class Factory
{
    /**
     * Create instance of Archive Operator
     *
     * Try to create Zip because of ZipArchive take precedence, if ZipArchive class
     * doesn't exists, fallback to FallBackZip the wrapper for PclZip.
     *
     * @param string $fileName
     * @return ArchiveFileOperator
     * @throws InvalidArgumentException
     */
    public function create($fileName)
    {
        Assert::stringNotEmpty($fileName);

        if (class_exists('ZipArchive')) {
            return new Zip(new ZipArchive(), $fileName);
        }

        if (!class_exists('PclZip')) {
            require_once dirname(__DIR__) . '/lib/pclzip.lib.php';
        }

        return new FallBackZip(new PclZip($fileName), $fileName);
    }
}
