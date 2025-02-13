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
class LogData implements Log
{
    /**
     * @var string
     */
    private $date;

    /**
     * @var string
     */
    private $level;

    /**
     * @var string
     */
    private $message;

    /**
     * @param non-empty-string $level
     * @param non-empty-string $message
     */
    public function __construct(string $date, string $level, string $message)
    {
        $this->date = $date;
        $this->level = $level;
        $this->message = $message;
    }

    /**
     * {@inheritDoc}
     */
    public function date(): string
    {
        return $this->date;
    }

    /**
     * {@inheritDoc}
     */
    public function level(): string
    {
        return $this->level;
    }

    /**
     * {@inheritDoc}
     */
    public function message(): string
    {
        return $this->message;
    }
}
