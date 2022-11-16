<?php

declare(strict_types=1);

use Aws\S3\S3Client;

/**
 * Class BackWPup_S3_Destination.
 */
final class BackWPup_S3_Destination
{
    /**
     * @var array
     */
    private $options;

    /**
     * BackWPup_S3_Destination constructor.
     */
    private function __construct(array $options)
    {
        $defaults = [
            'label' => __('Custom S3 destination', 'backwpup'),
            'endpoint' => '',
            'region' => '',
            'multipart' => true,
            'only_path_style_bucket' => false,
            'version' => 'latest',
            'signature' => 'v4',
        ];

        $this->options = array_merge($defaults, $options);
    }

    /**
     * Get list of S3 destinations.
     *
     * This list can be extended by using the `backwpup_s3_destination` filter.
     */
    public static function options(): array
    {
        return apply_filters(
            'backwpup_s3_destination',
            [
                'us-east-2' => [
                    'label' => __('Amazon S3: US East (Ohio)', 'backwpup'),
                    'region' => 'us-east-2',
                    'multipart' => true,
                ],
                'us-east-1' => [
                    'label' => __('Amazon S3: US East (N. Virginia)', 'backwpup'),
                    'region' => 'us-east-1',
                    'multipart' => true,
                ],
                'us-west-1' => [
                    'label' => __('Amazon S3: US West (N. California)', 'backwpup'),
                    'region' => 'us-west-1',
                    'multipart' => true,
                ],
                'us-west-2' => [
                    'label' => __('Amazon S3: US West (Oregon)', 'backwpup'),
                    'region' => 'us-west-2',
                    'multipart' => true,
                ],
                'af-south-1' => [
                    'label' => __('Amazon S3: Africa (Cape Town)', 'backwpup'),
                    'region' => 'af-south-1',
                    'multipart' => true,
                ],
                'ap-east-1' => [
                    'label' => __('Amazon S3: Asia Pacific (Hong Kong)', 'backwpup'),
                    'region' => 'ap-east-1',
                    'multipart' => true,
                ],
                'ap-southeast-3' => [
                    'label' => __('Amazon S3: Asia Pacific (Jakarta)', 'backwpup'),
                    'region' => 'ap-southeast-3',
                    'multipart' => true,
                ],
                'ap-south-1' => [
                    'label' => __('Amazon S3: Asia Pacific (Mumbai)', 'backwpup'),
                    'region' => 'ap-south-1',
                    'multipart' => true,
                ],
                'ap-northeast-3' => [
                    'label' => __('Amazon S3: Asia Pacific (Osaka)', 'backwpup'),
                    'region' => 'ap-northeast-3',
                    'multipart' => true,
                ],
                'ap-northeast-2' => [
                    'label' => __('Amazon S3: Asia Pacific (Seoul)', 'backwpup'),
                    'region' => 'ap-northeast-2',
                    'multipart' => true,
                ],
                'ap-southeast-1' => [
                    'label' => __('Amazon S3: Asia Pacific (Singapore)', 'backwpup'),
                    'region' => 'ap-southeast-1',
                    'multipart' => true,
                ],
                'ap-southeast-2' => [
                    'label' => __('Amazon S3: Asia Pacific (Sydney)', 'backwpup'),
                    'region' => 'ap-southeast-2',
                    'multipart' => true,
                ],
                'ap-northeast-1' => [
                    'label' => __('Amazon S3: Asia Pacific (Tokyo)', 'backwpup'),
                    'region' => 'ap-northeast-1',
                    'multipart' => true,
                ],
                'ca-central-1' => [
                    'label' => __('Amazon S3: Canada (Central)', 'backwpup'),
                    'region' => 'ca-central-1',
                    'multipart' => true,
                ],
                'eu-central-1' => [
                    'label' => __('Amazon S3: Europe (Frankfurt)', 'backwpup'),
                    'region' => 'eu-central-1',
                    'multipart' => true,
                ],
                'eu-west-1' => [
                    'label' => __('Amazon S3: Europe (Ireland)', 'backwpup'),
                    'region' => 'eu-west-1',
                    'multipart' => true,
                ],
                'eu-west-2' => [
                    'label' => __('Amazon S3: Europe (London)', 'backwpup'),
                    'region' => 'eu-west-2',
                    'multipart' => true,
                ],
                'eu-south-1' => [
                    'label' => __('Amazon S3: Europe (Milan)', 'backwpup'),
                    'region' => 'eu-south-1',
                    'multipart' => true,
                ],
                'eu-west-3' => [
                    'label' => __('Amazon S3: Europe (Paris)', 'backwpup'),
                    'region' => 'eu-west-3',
                    'multipart' => true,
                ],
                'eu-north-1' => [
                    'label' => __('Amazon S3: Europe (Stockholm)', 'backwpup'),
                    'region' => 'eu-north-1',
                    'multipart' => true,
                ],
                'me-south-1' => [
                    'label' => __('Amazon S3: Middle East (Bahrain)', 'backwpup'),
                    'region' => 'me-south-1',
                    'multipart' => true,
                ],
                'sa-east-1' => [
                    'label' => __('Amazon S3: South America (São Paulo)', 'backwpup'),
                    'region' => 'sa-east-1',
                    'multipart' => true,
                ],
                'us-gov-east-1' => [
                    'label' => __('Amazon S3: AWS GovCloud (US-East)', 'backwpup'),
                    'region' => 'us-gov-east-1',
                    'multipart' => true,
                ],
                'us-gov-west-1' => [
                    'label' => __('Amazon S3: AWS GovCloud (US-West)', 'backwpup'),
                    'region' => 'us-gov-west-1',
                    'multipart' => true,
                ],
                'google-storage' => [
                    'label' => __('Google Storage: EU (Multi-Regional)', 'backwpup'),
                    'region' => 'EU',
                    'endpoint' => 'https://storage.googleapis.com',
                ],
                'google-storage-us' => [
                    'label' => __('Google Storage: USA (Multi-Regional)', 'backwpup'),
                    'region' => 'US',
                    'endpoint' => 'https://storage.googleapis.com',
                ],
                'google-storage-asia' => [
                    'label' => __('Google Storage: Asia (Multi-Regional)', 'backwpup'),
                    'region' => 'ASIA',
                    'endpoint' => 'https://storage.googleapis.com',
                ],
                'dreamhost' => [
                    'label' => __('Dream Host Cloud Storage', 'backwpup'),
                    'endpoint' => 'https://objects-us-west-1.dream.io',
                ],
                'digital-ocean-sfo2' => [
                    'label' => __('DigitalOcean: SFO2', 'backwpup'),
                    'endpoint' => 'https://sfo2.digitaloceanspaces.com',
                ],
                'digital-ocean-nyc3' => [
                    'label' => __('DigitalOcean: NYC3', 'backwpup'),
                    'endpoint' => 'https://nyc3.digitaloceanspaces.com',
                ],
                'digital-ocean-ams3' => [
                    'label' => __('DigitalOcean: AMS3', 'backwpup'),
                    'endpoint' => 'https://ams3.digitaloceanspaces.com',
                ],
                'digital-ocean-sgp1' => [
                    'label' => __('DigitalOcean: SGP1', 'backwpup'),
                    'endpoint' => 'https://sgp1.digitaloceanspaces.com',
                ],
                'digital-ocean-fra1' => [
                    'label' => __('DigitalOcean: FRA1', 'backwpup'),
                    'endpoint' => 'https://fra1.digitaloceanspaces.com',
                ],
                'scaleway-ams' => [
                    'label' => __('Scaleway: AMS', 'backwpup'),
                    'region' => 'nl-ams',
                    'endpoint' => 'https://s3.nl-ams.scw.cloud',
                ],
                'scaleway-par' => [
                    'label' => __('Scaleway: PAR', 'backwpup'),
                    'region' => 'fr-par',
                    'endpoint' => 'https://s3.fr-par.scw.cloud',
                ],
            ]
        );
    }

    /**
     * Get the AWS destination of the passed id or base url.
     *
     * @param string $idOrUrl Destination id or endpoint
     */
    public static function fromOption(string $idOrUrl): self
    {
        $destinations = self::options();

        return new self($destinations[$idOrUrl]);
    }

    /**
     * Get the AWS destination class from options array.
     *
     * @param array $optionsArr S3 options
     */
    public static function fromOptionArray(array $optionsArr): self
    {
        return new self($optionsArr);
    }

    /**
     * Get the AWS destination class from job ID.
     *
     * @param int $jobId The job ID to get options from
     */
    public static function fromJobId(int $jobId): self
    {
        $options = [
            'label' => __('Custom S3 destination', 'backwpup'),
            'endpoint' => BackWPup_Option::get($jobId, 's3base_url'),
            'region' => BackWPup_Option::get($jobId, 's3base_region'),
            'multipart' => !empty(BackWPup_Option::get($jobId, 's3base_multipart')),
            'only_path_style_bucket' => !empty(BackWPup_Option::get($jobId, 's3base_pathstylebucket')),
            'version' => BackWPup_Option::get($jobId, 's3base_version'),
            'signature' => BackWPup_Option::get($jobId, 's3base_signature'),
        ];

        return self::fromOptionArray($options);
    }

    /**
     * Get the Amazon S3 Client.
     *
     * @param $accessKey
     * @param $secretKey
     */
    public function client($accessKey, $secretKey): S3Client
    {
        $s3Options = [
            'signature' => $this->signature(),
            'credentials' => [
                'key' => $accessKey,
                'secret' => BackWPup_Encryption::decrypt($secretKey),
            ],
            'region' => $this->region(),
            'http' => [
                'verify' => BackWPup::get_plugin_data('cacert'),
            ],
            'version' => $this->version(),
            'use_path_style_endpoint' => $this->onlyPathStyleBucket(),
        ];

        if ($this->endpoint()) {
            $s3Options['endpoint'] = $this->endpoint();
            if (!$this->region()) {
                $s3Options['bucket_endpoint'] = true;
            }
        }

        $s3Options = apply_filters('backwpup_s3_client_options', $s3Options);

        return new S3Client($s3Options);
    }

    /**
     * The label of the destination.
     */
    public function label(): string
    {
        return $this->options['label'];
    }

    /**
     * The region of the destination.
     */
    public function region(): string
    {
        return $this->options['region'];
    }

    /**
     * The base url of the option. If empty than it should be a original AWS.
     */
    public function endpoint(): string
    {
        return $this->options['endpoint'];
    }

    /**
     * The s3 version for the api like '2006-03-01'.
     */
    public function version(): string
    {
        return $this->options['version'];
    }

    /**
     * The signature for the api like 'v4'.
     */
    public function signature(): string
    {
        return $this->options['signature'];
    }

    /**
     * Destination supports multipart uploads.
     */
    public function supportsMultipart(): bool
    {
        return (bool) $this->options['multipart'];
    }

    /**
     * Destination support only path style buckets.
     */
    public function onlyPathStyleBucket(): bool
    {
        return (bool) $this->options['only_path_style_bucket'];
    }
}
