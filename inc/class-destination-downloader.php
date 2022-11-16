<?php

use function Inpsyde\BackWPup\Pro\Restore\Functions\restore_container;
/**
 * BackWPup_Destination_Downloader.
 *
 * @since   3.6.0
 */

use Inpsyde\Restore\Api\Controller\DecryptController;
use Inpsyde\Restore\Api\Module\Decryption\Exception\DecryptException;

/**
 * Class BackWPup_Destination_Downloader.
 *
 * @since   3.6.0
 */
class BackWPup_Destination_Downloader
{
    public const ARCHIVE_ENCRYPT_OPTION = 'archiveencryption';
    public const CAPABILITY = 'backwpup_backups_download';

    public const STATE_DOWNLOADING = 'downloading';
    public const STATE_ERROR = 'error';
    public const STATE_DONE = 'done';

    /**
     * @var \BackWpUp_Destination_Downloader_Data
     */
    private $data;

    /**
     * @var \BackWPup_Destination_Downloader_Interface
     */
    private $destination;

    /**
     * BackWPup_Downloader constructor.
     *
     * @param \BackWpUp_Destination_Downloader_Data      $data
     * @param \BackWPup_Destination_Downloader_Interface $destination
     */
    public function __construct(
        BackWpUp_Destination_Downloader_Data $data,
        BackWPup_Destination_Downloader_Interface $destination
    ) {
        $this->data = $data;
        $this->destination = $destination;
    }

    /**
     * Download file via ajax.
     */
    public static function download_by_ajax()
    {
        $dest = (string) filter_input(INPUT_GET, 'destination', FILTER_SANITIZE_STRING);
        if (!$dest) {
            return;
        }

        $job_id = (int) filter_input(INPUT_GET, 'jobid', FILTER_SANITIZE_NUMBER_INT);
        if (!$job_id) {
            return;
        }

        $file = (string) filter_input(INPUT_GET, 'file', FILTER_SANITIZE_STRING);
        $file_local = (string) filter_input(INPUT_GET, 'local_file', FILTER_SANITIZE_STRING);
        if (!$file || !$file_local) {
            return;
        }

        set_time_limit(0);
        // Set up eventsource headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        header('Content-Encoding: none');

        // 2KB padding for IE
        echo ':' . str_repeat(' ', 2048) . "\n\n"; // phpcs:ignore

        // Ensure we're not buffered.
        wp_ob_end_flush_all();
        flush();

        /** @var \BackWPup_Destinations $dest_class */
        $dest_class = BackWPup::get_destination($dest);
        $dest_class->file_download(
            $job_id,
            trim(sanitize_text_field($file)),
            trim(sanitize_text_field($file_local))
        );
    }

    /**
     * @return bool
     */
    public function download_by_chunks()
    {
        $this->ensure_user_can_download();

        $source_file_path = $this->data->source_file_path();
        $local_file_path = $this->data->local_file_path();
        $size = $this->destination->calculate_size();
        $start_byte = 0;
        $chunk_size = 2 * 1024 * 1024;
        $end_byte = $start_byte + $chunk_size - 1;

        if ($end_byte >= $size) {
            $end_byte = $size - 1;
        }

        try {
            while ($end_byte <= $size) {
                $this->destination->download_chunk($start_byte, $end_byte);
                self::send_message(
                    [
                        'state' => self::STATE_DOWNLOADING,
                        'start_byte' => $start_byte,
                        'end_byte' => $end_byte,
                        'size' => $size,
                        'download_percent' => round(($end_byte + 1) / $size * 100),
                        'filename' => basename($source_file_path),
                    ]
                );

                if ($end_byte === $size - 1) {
                    break;
                }

                $start_byte = $end_byte + 1;
                $end_byte = $start_byte + $chunk_size - 1;

                if ($start_byte < $size && $end_byte >= $size) {
                    $end_byte = $size - 1;
                }
            }

            if (BackWPup::is_pro()) {
                /** @var \Inpsyde\Restore\Api\Module\Decryption\Decrypter $decrypter */
                $decrypter = restore_container('decrypter');
                if ($decrypter->isEncrypted($local_file_path)) {
                    throw new DecryptException(DecryptController::STATE_NEED_DECRYPTION_KEY);
                }
            }
        } catch (Exception $e) {
            self::send_message(
                [
                    'state' => self::STATE_ERROR,
                    'message' => $e->getMessage(),
                ],
                'log'
            );

            return false;
        }

        self::send_message([
            'state' => self::STATE_DONE,
            'message' => esc_html__('Your download is being generated &hellip;', 'backwpup'),
        ]);

        return true;
    }

    /**
     * Ensure user capability.
     */
    private function ensure_user_can_download()
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die();
        }
    }

    /**
     * @param        $data
     * @param string $event
     */
    private static function send_message($data, $event = 'message')
    {
        echo "event: {$event}\n";
        echo 'data: ' . wp_json_encode($data) . "\n\n";
        flush();
    }
}
