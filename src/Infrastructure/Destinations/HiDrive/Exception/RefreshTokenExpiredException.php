<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Destinations\HiDrive\Exception;

final class RefreshTokenExpiredException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            __('The HiDrive refresh token has expired. Please reauthenticate.', 'backwpup')
        );
    }
}
