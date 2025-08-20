<?php
/**
 * BackWPup_Destination_Ftp_Downloader.
 *
 * @since   3.5.0
 */

/**
 * Class BackWPup_Destination_Ftp_Downloader.
 *
 * @since   3.5.0
 */
final class BackWPup_Destination_Ftp_Downloader implements BackWPup_Destination_Downloader_Interface
{
    /**
     * @var \BackWpUp_Destination_Downloader_Data
     */
    private $data;

    /**
     * @var resource
     */
    private $source_file_handler;

    /**
     * @var resource
     */
    private $local_file_handler;

    /**
     * @var BackWPup_Destination_Ftp_Connect
     */
    private $ftp_resource;

    /**
     * BackWPup_Destination_Ftp_Downloader constructor.
     */
    public function __construct(BackWpUp_Destination_Downloader_Data $data)
    {
        $this->data = $data;

        $this->ftp_resource();
    }

    /**
     * Clean up things.
	 */
	public function __destruct() {
		if ( is_resource( $this->source_file_handler ) ) {
			fclose( $this->source_file_handler ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}
		if ( is_resource( $this->local_file_handler ) ) {
			fclose( $this->local_file_handler ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}
    }

    /**
     * {@inheritdoc}
     */
    public function download_chunk($start_byte, $end_byte)
    {
        $this->source_file_handler($start_byte);
        $this->local_file_handler($start_byte);

        $bytes = (int) stream_copy_to_stream(
            $this->source_file_handler,
            $this->local_file_handler,
            $end_byte - $start_byte + 1,
            0
        );

        if ($bytes === 0) {
            throw new \RuntimeException(__('Could not write data to file.', 'backwpup'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function calculate_size()
    {
        $resource = $this->ftp_resource
            ->connect()
            ->resource()
        ;

        $size = ftp_size($resource, $this->data->source_file_path());
        ftp_close($resource);

        return $size;
    }

	/**
	 * Disable ftp server with filter.
	 *
	 * @param int $start_byte The start byte of the download.
	 */
	public function disable_ftp_server_ssl( int $start_byte ) {
		$backwpup_ftp_ssl = wpm_apply_filters_typed( 'boolean', 'backwpup_disable_ftp_server_ssl', true );

		return stream_context_create(
			[
				'ftp' => [
					'resume_pos' => $start_byte,
				],
				'ssl' => [
					'verify_peer'      => $backwpup_ftp_ssl,
					'verify_peer_name' => $backwpup_ftp_ssl,
				],
			]
		);
	}

    /**
     * Set the source file handler.
     *
     * @param int $start_byte
     */
    private function source_file_handler($start_byte)
    {
        if (is_resource($this->source_file_handler)) {
            return;
        }

		$url = $this->ftp_resource->getURL( $this->data->source_file_path() );

		$this->source_file_handler = fopen( $url, 'r', false, $this->disable_ftp_server_ssl( $start_byte ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( ! is_resource( $this->source_file_handler ) ) {
			// Fall back to FTP.
			$url                       = str_replace( 'ftps://', 'ftp://', $url );
			$this->source_file_handler = @fopen( $url, 'r' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		}

        if (!is_resource($this->source_file_handler)) {
            throw new \RuntimeException(__('Cannot open FTP file for download.', 'backwpup'));
        }
    }

    /**
     * Set the local file handler.
     *
     * @param int $start_byte
     */
    private function local_file_handler($start_byte)
    {
        if (is_resource($this->local_file_handler)) {
            return;
        }

        $this->local_file_handler = fopen($this->data->local_file_path(), $start_byte === 0 ? 'wb' : 'ab');

        if (!is_resource($this->local_file_handler)) {
            throw new \RuntimeException(__('File could not be opened for writing.', 'backwpup'));
        }
    }

    /**
     * Set the Ftp resource.
     */
    private function ftp_resource()
    {
        $opts = (object) BackWPup_Option::get_job($this->data->job_id());

        $this->ftp_resource = new BackWPup_Destination_Ftp_Connect(
            $opts->ftphost,
            $opts->ftpuser,
            BackWPup_Encryption::decrypt($opts->ftppass),
            $opts->ftphostport,
            $opts->ftptimeout,
            $opts->ftpssl,
            $opts->ftppasv
        );
    }
}
