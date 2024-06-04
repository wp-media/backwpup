<?php

declare(strict_types=1);

/*
 * This file is part of the Inpsyde BackWpUp package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Restore;

class DestinationFactory
{
    /**
     * @var string The destination identifier
     */
    private $destination;

    /**
     * The class prefix. The part before the destination.
     *
     * @var string
     */
    private static $prefix = 'BackWPup_Destination_';

    /**
     * Class Prefix for Pro Classes.
     *
     * @since 3.5.0
     *
     * @var string The class prefix for pro class
     */
    private static $pro_prefix = 'BackWPup_Pro_Destination_';

    /**
     * @param string $destination The destination name
     */
    public function __construct($destination)
    {
        $this->destination = $destination;
    }

    /**
     * Creates the specified destination object.
     */
    public function create(): object
    {
        // Build the class name.
        $class = self::$prefix . $this->destination;

        // If class doesn't exist, try within the Pro directory.
        if (!class_exists($class)) {
            $class = str_replace(self::$prefix, self::$pro_prefix, $class);
        }

        if (!class_exists($class)) {
            throw new \RuntimeException(
                sprintf(
                    'No way to instantiate class %s. Class doesn\'t exist.',
                    $class
                )
            );
        }

        return new $class();
    }
}
