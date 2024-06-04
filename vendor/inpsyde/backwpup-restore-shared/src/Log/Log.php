<?php

declare(strict_types=1);

/*
 * This file is part of the BackWPup Restore Shared package.
 *
 * (c) Guido Scialfa <dev@guidoscialfa.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Restore\Log;

/**
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
interface Log
{
    /**
     * Formatted Date of When the Log was Written.
     */
    public function date(): string;

    /**
     * Log Level.
     */
    public function level(): string;

    /**
     * Log Message.
     */
    public function message(): string;
}
