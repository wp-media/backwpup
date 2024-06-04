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
class NullLogData implements Log
{
    /**
     * {@inheritDoc}
     */
    public function date(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function level(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function message(): string
    {
        return '';
    }
}
