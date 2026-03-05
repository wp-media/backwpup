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
class BackWPup_Destination_Ftp_Downloader implements BackWPup_Destination_Downloader_Interface {

	/**
	 * Connection data.
	 *
	 * @var \BackWpUp_Destination_Downloader_Data
     */
    private $data;

	/**
	 * Local file handler.
	 *
	 * @var resource
     */
    private $local_file_handler;

	/**
	 * FTP connection.
	 *
	 * @var BackWPup_Destination_Ftp_Type
	 */
	private $ftp;

	/**
	 * Remote file size.
	 *
	 * @var int
	 */
	private $remote_file_size = 0;

    /**
     * BackWPup_Destination_Ftp_Downloader constructor.
     */
    public function __construct(BackWpUp_Destination_Downloader_Data $data)
    {
		$this->data = $data;
	}

    /**
     * Clean up things.
	 */
	public function __destruct() {
		if ( is_resource( $this->local_file_handler ) ) {
			fclose( $this->local_file_handler ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}
    }

    /**
	 * {@inheritdoc}
	 *
	 * @param int $start_byte start.
	 * @param int $end_byte end.
	 *
	 * @return void
	 * @throws RuntimeException If downloaded file size does not match expected size.
	 */
	public function download_chunk( $start_byte, $end_byte ): void {
		$this->ftp_resource();
		$this->local_file_handler();

		$this->ftp->download( $this->data->source_file_path(), $this->local_file_handler,  $start_byte, $end_byte - $start_byte + 1 );

		$local_file_size = ftell( $this->local_file_handler );
		if ( $end_byte + 1 >= $this->calculate_size() && $this->calculate_size() !== $local_file_size ) {
			throw new RuntimeException( 'Downloaded file size does not match expected size.' );
		}
	}

    /**
     * {@inheritdoc}
	 */
	public function calculate_size(): int {
		$this->ftp_resource();

		if ( ! $this->remote_file_size ) {
			$this->remote_file_size = $this->ftp->size( $this->data->source_file_path() );
		}

		return $this->remote_file_size;
	}

    /**
     * Set the local file handler.
	 *
	 * @throws \RuntimeException On file open error.
	 */
	private function local_file_handler() {
		if ( is_resource( $this->local_file_handler ) ) {
			return;
        }

		$this->local_file_handler = fopen($this->data->local_file_path(), 'wb' ); //phpcs:ignore

		if ( ! is_resource( $this->local_file_handler ) ) {
			throw new \RuntimeException( esc_html__( 'File could not be opened for writing.', 'backwpup' ) );
		}
    }

    /**
     * Set the Ftp resource.
	 */
	private function ftp_resource(): void {
		if ( $this->ftp ) {
			return;
		}

		$opts = (object) BackWPup_Option::get_job( $this->data->job_id() );

		if ( ! empty( $opts->ftpssh ) && BackWPup::is_pro() ) {
			$this->ftp = new BackWPup_Pro_Destination_Ftp_Type_Sftp();
		} else {
			$this->ftp = new BackWPup_Destination_Ftp_Type_Ftp();
		}

		$this->ftp->connect(
			$opts->ftpuser,
			BackWPup_Encryption::decrypt( $opts->ftppass ),
			$opts->ftphost,
			[
				'port'    => $opts->ftphostport,
				'timeout' => $opts->ftptimeout,
				'ssl'     => ! empty( $opts->ftpssl ),
				'pasv'    => ! empty( $opts->ftppasv ),
				'privkey' => ! empty( $opts->ftpsshprivkey ) ? BackWPup_Encryption::decrypt( $opts->ftpsshprivkey ) : '',
			]
		);
	}
}
