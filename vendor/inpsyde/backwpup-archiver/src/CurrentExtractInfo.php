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

use BadMethodCallException;
use Inpsyde\Assert\Assert;
use InvalidArgumentException;
use JsonSerializable;
use OutOfBoundsException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Class CurrentExtractInfo
 * @property $remains
 * @property $index
 * @property $fileName
 * @property $destinationPath
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class CurrentExtractInfo implements JsonSerializable
{
    /**
     * @var int
     */
    private $remains;

    /**
     * @var int
     */
    private $index;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $destinationPath;

    /**
     * CurrentExtractData constructor
     *
     * @param int $remains
     * @param int $index
     * @param string $fileName
     * @param $destinationPath
     * @throws InvalidArgumentException
     */
    public function __construct($remains, $index, $fileName, $destinationPath)
    {
        Assert::greaterThanEq($remains, 0);
        Assert::greaterThanEq($index, 0);
        Assert::stringNotEmpty($fileName);
        Assert::path($destinationPath);

        $this->remains = $remains;
        $this->index = $index;
        $this->fileName = $fileName;
        $this->destinationPath = $destinationPath;
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $data = [];

        try {
            $reflection = new ReflectionClass($this);
        } catch (ReflectionException $exc) {
            return [];
        }
        $properties = $reflection->getProperties(ReflectionProperty::IS_PRIVATE);

        /** @var ReflectionProperty $property */
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $data[$property->getName()] = $property->getValue($this);
            $property->setAccessible(false);
        }

        return $data;
    }

    /**
     * Get Property
     *
     * @param $name
     * @return mixed
     * @throws OutOfBoundsException
     */
    public function __get($name)
    {
        $this->__isset($name);

        return $this->$name;
    }

    /**
     * Set Property
     *
     * @param $name
     * @param $value
     * @throws BadMethodCallException
     */
    public function __set($name, $value)
    {
        throw new BadMethodCallException(sprintf(
            'Cannot Set properties for %s object.',
            __CLASS__
        ));
    }

    /**
     * Check if the Given Property is Set
     *
     * @param $name
     * @return bool
     * @throws OutOfBoundsException
     */
    public function __isset($name)
    {
        if (!isset($this->$name)) {
            throw new OutOfBoundsException("Property {$name} does not exists.");
        }

        return true;
    }
}
