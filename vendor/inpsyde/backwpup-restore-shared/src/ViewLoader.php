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

class ViewLoader
{
    private const DECRYPT_KEY_INPUT = 'decrypt-key-input.php';

    /**
     * @var string
     */
    private $view_directory;

    /**
     * @var array<string, string>
     */
    private $cache = [];

    public function __construct()
    {
        $this->view_directory = \dirname(__DIR__) . '/views';
    }

    /**
     * Load the private key input view.
     */
    public function decrypt_key_input(): void
    {
        $this->load(self::DECRYPT_KEY_INPUT);
    }

    private function load(string $view): void
    {
        if (isset($this->cache[$view])) {
            /** @noinspection PhpIncludeInspection */
            include $this->cache[$view];

            return;
        }

        $file_path = $this->view_directory . "/{$view}";
        if (!file_exists($file_path)) {
            return;
        }

        $this->cache[$view] = $file_path;

        /** @noinspection PhpIncludeInspection */
        include $file_path;
    }
}
