<?php

declare(strict_types=1);

use Aws\S3\S3Client;

/**
 * S3 Downloader.
 *
 * @since   3.5.0
 */
final class BackWPup_Destination_S3_Downloader implements BackWPup_Destination_Downloader_Interface
{
    private const OPTION_BASE_URL = 's3base_url';
    private const OPTION_REGION = 's3region';
    private const OPTION_BUCKET = 's3bucket';
    private const OPTION_ACCESS_KEY = 's3accesskey';
    private const OPTION_SECRET_KEY = 's3secretkey';

    /**
     * @var BackWpUp_Destination_Downloader_Data
     */
    private $data;

    /**
     * @var S3Client
     */
    private $s3Client;

    /**
     * @var resource
	 */
	private $local_handle;

    /**
     * BackWPup_Destination_S3_Downloader constructor.
     */
    public function __construct(BackWpUp_Destination_Downloader_Data $data)
    {
        $this->data = $data;
        $this->initializeS3Client();
    }

    /**
     * Clean stuffs.
	 */
	public function __destruct() {
		fclose( $this->local_handle ); //phpcs:ignore
	}

    /**
     * {@inheritdoc}
     */
    public function download_chunk($start_byte, $end_byte): void
    {
        $file = $this->s3Client->getObject([
            'Bucket' => BackWPup_Option::get($this->data->job_id(), self::OPTION_BUCKET),
            'Key' => $this->data->source_file_path(),
            'Range' => 'bytes=' . $start_byte . '-' . $end_byte,
        ]);

		if ( empty( $file['ContentType'] ) || 0 === $file['ContentLength'] ) {
			throw new RuntimeException( esc_html__( 'Could not write data to file. Empty source file.', 'backwpup' ) );
		}

        $this->openLocalHandle($start_byte);

		$bytes = (int) fwrite( $this->local_handle, (string) $file['Body'] ); //phpcs:ignore
		if ( 0 === $bytes ) {
			throw new RuntimeException( esc_html__( 'Could not write data to file.', 'backwpup' ) );
		}
    }

    /**
     * {@inheritdoc}
     */
    public function calculate_size(): int
    {
        $file = $this->s3Client->getObject([
            'Bucket' => BackWPup_Option::get($this->data->job_id(), self::OPTION_BUCKET),
            'Key' => $this->data->source_file_path(),
        ]);

        return (int) (!empty($file['ContentType']) ? $file['ContentLength'] : 0);
    }

	/**
	 * Open local file handle.
	 *
	 * @param int $start_byte
	 *
	 * @return void
	 * @throws RuntimeException If the file could not be opened.
	 */
	private function openLocalHandle( int $start_byte ): void {
		if ( is_resource( $this->local_handle ) ) {
			return;
        }

		$this->local_handle = fopen( $this->data->local_file_path(), $start_byte === 0 ? 'wb' : 'ab' ); //phpcs:ignore

		if ( ! is_resource( $this->local_handle ) ) {
			throw new RuntimeException( esc_html__( 'File could not be opened for writing.', 'backwpup' ) );
		}
    }

    /**
     * Build S3 Client.
     */
    private function initializeS3Client(): void
    {
        if ($this->s3Client) {
            return;
        }

        if (empty(BackWPup_Option::get($this->data->job_id(), self::OPTION_BASE_URL))) {
            $aws_destination = BackWPup_S3_Destination::fromOption(
                BackWPup_Option::get($this->data->job_id(), self::OPTION_REGION)
            );
        } else {
            $aws_destination = BackWPup_S3_Destination::fromJobId($this->data->job_id());
        }

        $this->s3Client = $aws_destination->client(
            BackWPup_Option::get($this->data->job_id(), self::OPTION_ACCESS_KEY),
            BackWPup_Option::get($this->data->job_id(), self::OPTION_SECRET_KEY)
        );
    }
}
