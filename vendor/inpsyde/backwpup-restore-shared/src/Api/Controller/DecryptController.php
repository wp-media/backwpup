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

namespace Inpsyde\Restore\Api\Controller;

use Inpsyde\Restore\Api\Module\Decryption\Decrypter;
use Inpsyde\Restore\Api\Module\Decryption\Exception\DecryptException;

/**
 * Class DecryptController.
 */
class DecryptController
{
    public const STATE_DECRYPTION_FAILED = 'decryption_failed';
    public const STATE_DECRYPTION_SUCCESS = 'decryption_success';
    public const STATE_NEED_DECRYPTION_KEY = 'need_decryption_key';

    /**
     * @var Decrypter
     */
    private $decrypter;

    /**
     * DecryptController constructor.
     */
    public function __construct(Decrypter $decrypter)
    {
        $this->decrypter = $decrypter;
    }

    /**
     * @throws DecryptException
     */
    public function decrypt(string $key, string $encrypted_file): void
    {
        $decrypted = false;
        $maybe_decrypted = $this->decrypter->isEncrypted($encrypted_file);

        if ($maybe_decrypted) {
            $decrypted = $this->decrypter->decrypt($key, $encrypted_file);
            if (!$decrypted) {
                throw new DecryptException(
                    __(
                        'Decryption Failed. Probably the key you provided is not correct. Try again with a different key.',
                        'backwpup'
                    )
                );
            }
        }
    }
}
