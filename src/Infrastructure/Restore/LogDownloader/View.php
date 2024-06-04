<?php
/**
 * DownloadLog.
 *
 * @since   3.5.0
 */

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Restore\LogDownloader;

use function add_query_arg;
use function backwpup_template;
use function current_user_can;

/**
 * Class DownloadLog.
 *
 * @since   3.5.0
 */
final class View
{
    public const NONCE_NAME = 'backwpup_restore_log_download_action';
    public const NONCE_ACTION = 'download_restore_log';

    /**
     * Capability.
     *
     * @since 3.5.0
     *
     * @var string
     */
    private static $capability = 'backwpup_restore';

    /**
     * Label.
     *
     * @since 3.5.0
     *
     * @var string The label for the link
     */
    private $label;

    /**
     * Url.
     *
     * @since 3.5.0
     *
     * @var string The url where point the action
     */
    private $url;

    /**
     * Files.
     *
     * @since 3.5.0
     *
     * @var array The list of the files to download
     */
    private $files;

    /**
     * DownloadLogView constructor.
     *
     * @since 3.5.0
     *
     * @param string $label the label for the link
     * @param string $url   the url where point the action
     */
    public function __construct(string $label, string $url, array $files)
    {
        if (!$label) {
            throw new \InvalidArgumentException(
                sprintf('Invalid label for %s', self::class)
            );
        }
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid label for %s', self::class)
            );
        }

        $this->label = $label;
        $this->url = $url;
        $this->files = $files;
    }

    /**
     * View.
     *
     * @since 3.5.0
     *
     * Print the anchor link.
     */
    public function view(): void
    {
        if (!current_user_can(self::$capability)) {
            return;
        }

        backwpup_template(
            (object) [
                'link' => $this->link(),
                'label' => $this->label,
            ],
            '/restore/download-log.php'
        );
    }

    /**
     * Build the link.
     *
     * @since 3.5.0
     *
     * @return string The link url
     */
    private function link()
    {
        return add_query_arg(
            [
                self::NONCE_NAME => wp_create_nonce(self::NONCE_NAME),
                'action' => self::NONCE_ACTION,
            ],
            $this->url
        );
    }
}
