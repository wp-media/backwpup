<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Authentication\Exception;

final class CouldNotDecodeBasicAuthenticationToken extends \RuntimeException
{
    public static function withToken(string $token): self
    {
        return new self(
            sprintf(
                /* translators: %s: authentication token. */
                esc_html__( 'Could not decode basic authentication token %s', 'backwpup' ),
                esc_html( $token )
            )
        );
    }
}
