<?php

use Inpsyde\BackWPup\MsAzureDestinationConfiguration;
use Inpsyde\BackWPupShared\File\MimeTypeExtractor;
use MicrosoftAzure\Storage\Blob\Models\GetBlobOptions;
use MicrosoftAzure\Storage\Common\Models\Range;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

class BackWPup_Destination_MSAzure_Downloader implements BackWPup_Destination_Downloader_Interface
{
    /**
     * @var BackWpUp_Destination_Downloader_Data
     */
    private $data;

    /**
     * @var resource
     */
    private $local_file_handler;

    /**
     * @param BackWpUp_Destination_Downloader_Data $data
     */
    public function __construct(BackWpUp_Destination_Downloader_Data $data)
    {
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function download_chunk($start_byte, $end_byte)
    {
        $option = new GetBlobOptions();
        $range = new Range($start_byte, $end_byte);
        $option->setRange($range);

        $client = $this->getBlobClient();

        $blob = $client->getBlob(
            BackWPup_Option::get(
                $this->data->job_id(),
                MsAzureDestinationConfiguration::MSAZURE_CONTAINER
            ),
            $this->data->source_file_path(),
            $option
        );

        if ($blob->getProperties()->getContentLength() === 0) {
            throw new RuntimeException(
                __('Could not write data to file. Empty source file.', 'backwpup')
            );
        }

        $this->setLocalFileHandler($start_byte);

        $bytes = (int)fwrite($this->local_file_handler, stream_get_contents($blob->getContentStream()));
        if ($bytes === 0) {
            throw new RuntimeException(
                sprintf(__('Could not write data to file %s.', 'backwpup'), $this->data->source_file_path())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function calculate_size()
    {
        $client = $this->getBlobClient();

        $blob = $client->getBlob(
            BackWPup_Option::get(
                $this->data->job_id(),
                MsAzureDestinationConfiguration::MSAZURE_CONTAINER
            ),
            $this->data->source_file_path()
        );

        return $blob->getProperties()->getContentLength();
    }

    /**
     * Sets local_file_handler property by opening the current chunk of the resource.
     * @param int $start_byte
     * @throws RuntimeException
     */
    private function setLocalFileHandler($start_byte)
    {
        if (is_resource($this->local_file_handler)) {
            return;
        }

        $this->local_file_handler = fopen(
            $this->data->local_file_path(),
            $start_byte == 0 ? 'wb' : 'ab'
        );

        if (!is_resource($this->local_file_handler)) {
            throw new RuntimeException(__('File could not be opened for writing.', 'backwpup'));
        }
    }

    /**
     * Retrieves the service used to access the blob.
     * @return BlobRestProxy
     */
    private function getBlobClient()
    {
        $destination = new BackWPup_Destination_MSAzure();

        return $destination->createBlobClient(
            BackWPup_Option::get(
                $this->data->job_id(),
                MsAzureDestinationConfiguration::MSAZURE_ACCNAME
            ),
            BackWPup_Encryption::decrypt(
                BackWPup_Option::get(
                    $this->data->job_id(),
                    MsAzureDestinationConfiguration::MSAZURE_KEY
                )
            )
        );
    }
}
