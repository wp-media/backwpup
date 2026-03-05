<?php
/**
 * Dropbox Files Downloader.
 */

/**
 * Class BackWPup_Destination_Dropbox_Downloader.
 *
 * @since   3.5.0
 */
final class BackWPup_Destination_Dropbox_Downloader implements BackWPup_Destination_Downloader_Interface
{
    public const OPTION_ROOT = 'dropboxroot';
    public const OPTION_TOKEN = 'dropboxtoken';

    /**
     * @var \BackWpUp_Destination_Downloader_Data
     */
    private $data;

    /**
     * @var resource
     */
    private $local_file_handler;

    /**
     * @var BackWPup_Destination_Dropbox_API
     */
    private $dropbox_api;

    /**
     * BackWPup_Destination_Dropbox_Downloader constructor.
     *
     * @param \BackWpUp_Destination_Downloader_Data $data
     *
     * @throws \BackWPup_Destination_Dropbox_API_Exception
     */
    public function __construct(BackWpUp_Destination_Downloader_Data $data)
    {
        $this->data = $data;

        $this->dropbox_api();
    }

    /**
     * Clean up things.
	 */
	public function __destruct() {
		fclose( $this->local_file_handler ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

    /**
     * {@inheritdoc}
     */
    public function download_chunk($start_byte, $end_byte)
    {
        $this->local_file_handler($start_byte);

        try {
            $data = $this->dropbox_api->download(
                ['path' => $this->data->source_file_path()],
                $start_byte,
                $end_byte
            );

			$bytes = (int) fwrite( $this->local_file_handler, (string) $data ); //phpcs:ignore
			if ( 0 === $bytes ) {
				throw new \RuntimeException( esc_html__( 'Could not write data to file.', 'backwpup' ) );
			}
        } catch (\Exception $e) {
            BackWPup_Admin::message('Dropbox: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function calculate_size()
    {
        $metadata = $this->dropbox_api->filesGetMetadata(['path' => $this->data->source_file_path()]);

        return $metadata['size'];
    }

    /**
     * Set local file hanlder.
     *
     * @param int $start_byte
     */
    private function local_file_handler($start_byte)
    {
        if (is_resource($this->local_file_handler)) {
            return;
        }

		// Open file; write mode if $start_byte is 0, else append.
		$this->local_file_handler = fopen( $this->data->local_file_path(), 0 === $start_byte ? 'wb' : 'ab' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( ! is_resource( $this->local_file_handler ) ) {
			throw new \RuntimeException( esc_html__( 'File could not be opened for writing.', 'backwpup' ) );
		}
    }

    /**
     * Set the dropbox api instance.
     *
     * @throws \BackWPup_Destination_Dropbox_API_Exception
     */
    private function dropbox_api(): void
    {
        $this->dropbox_api = new \BackWPup_Destination_Dropbox_API(
            \BackWPup_Option::get($this->data->job_id(), self::OPTION_ROOT)
        );

        $this->dropbox_api->setOAuthTokens(\BackWPup_Option::get($this->data->job_id(), self::OPTION_TOKEN));
    }
}
