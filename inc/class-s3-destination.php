<?php

/**
 * Class BackWPup_S3_Destination
 */
class BackWPup_S3_Destination
{

    /**
     * @var array
     */
    private $options;

    /**
     * BackWPup_S3_Destination constructor.
     *
     * @param array $options
     */
    private function __construct(array $options)
    {
        $defaults = array(
            'label' => __('Custom S3 destination', 'backwpup'),
            'endpoint' => '',
            'region' => '',
            'multipart' => true,
            'only_path_style_bucket' => false,
            'version' => 'latest',
            'signature' => 'v4',
        );

        $this->options = array_merge($defaults, $options);
    }

    /**
     * Get list of S3 destinations.
     *
     * This list can be extended by using the `backwpup_s3_destination` filter.
     *
     * @return array
     */
    public static function options()
    {
        return apply_filters('backwpup_s3_destination',
            array(
                'us-east-1' => array(
                    'label' => __('Amazon S3: US Ost (Nord-Virginia)', 'backwpup'),
                    'region' => 'us-east-1',
                    'multipart' => true,
                ),
                'us-east-2' => array(
                    'label' => __('Amazon S3: US Ost (Ohio)', 'backwpup'),
                    'region' => 'us-east-2',
                    'multipart' => true,
                ),
                'us-west-1' => array(
                    'label' => __('Amazon S3: US West (Northern California)', 'backwpup'),
                    'region' => 'us-west-1',
                    'multipart' => true,
                ),
                'us-west-2' => array(
                    'label' => __('Amazon S3: US West (Oregon)', 'backwpup'),
                    'region' => 'us-west-2',
                    'multipart' => true,
                ),
                'ca-central-1' => array(
                    'label' => __('Amazon S3: Canada (Zentral)', 'backwpup'),
                    'region' => 'ca-central-1',
                    'multipart' => true,
                ),
                'eu-west-1' => array(
                    'label' => __('Amazon S3: EU (Ireland)', 'backwpup'),
                    'region' => 'eu-west-1',
                    'multipart' => true,
                ),
                'eu-west-2' => array(
                    'label' => __('Amazon S3: EU (London)', 'backwpup'),
                    'region' => 'eu-west-2',
                    'multipart' => true,
                ),
                'eu-west-3' => array(
                    'label' => __('Amazon S3: EU (Paris)', 'backwpup'),
                    'region' => 'eu-west-1',
                    'multipart' => true,
                ),
                'eu-central-1' => array(
                    'label' => __('Amazon S3: EU (Germany)', 'backwpup'),
                    'region' => 'eu-central-1',
                    'multipart' => true,
                ),
                'eu-north-1' => array(
                    'label' => __('Amazon S3: EU (Stockholm)', 'backwpup'),
                    'region' => 'eu-north-1',
                    'multipart' => true,
                ),
                'ap-south-1' => array(
                    'label' => __('Amazon S3: Asia Pacific (Mumbai)', 'backwpup'),
                    'region' => 'ap-south-1',
                    'multipart' => true,
                ),
                'ap-northeast-1' => array(
                    'label' => __('Amazon S3: Asia Pacific (Tokyo)', 'backwpup'),
                    'region' => 'ap-northeast-1',
                    'multipart' => true,
                ),
                'ap-northeast-2' => array(
                    'label' => __('Amazon S3: Asia Pacific (Seoul)', 'backwpup'),
                    'region' => 'ap-northeast-2',
                    'multipart' => true,
                ),
                'ap-east-1' => array(
                    'label' => __('Amazon S3: Asia Pacific (Hongkong)', 'backwpup'),
                    'region' => 'ap-east-1',
                    'multipart' => true,
                ),
                'ap-southeast-1' => array(
                    'label' => __('Amazon S3: Asia Pacific (Singapore)', 'backwpup'),
                    'region' => 'ap-southeast-1',
                    'multipart' => true,
                ),
                'ap-southeast-2' => array(
                    'label' => __('Amazon S3: Asia Pacific (Sydney)', 'backwpup'),
                    'region' => 'ap-southeast-2',
                    'multipart' => true,
                ),
                'sa-east-1' => array(
                    'label' => __('Amazon S3: South America (Sao Paulo)', 'backwpup'),
                    'region' => 'sa-east-1',
                    'multipart' => true,
                ),
                'cn-north-1' => array(
                    'label' => __('Amazon S3: China (Beijing)', 'backwpup'),
                    'region' => 'cn-north-1',
                    'multipart' => true,
                ),
                'cn-northwest-1' => array(
                    'label' => __('Amazon S3: China (Ningxia)', 'backwpup'),
                    'region' => 'cn-northwest-1',
                    'multipart' => true,
                ),
                'google-storage' => array(
                    'label' => __('Google Storage: EU (Multi-Regional)', 'backwpup'),
                    'region' => 'EU',
                    'endpoint' => 'https://storage.googleapis.com',
                ),
                'google-storage-us' => array(
                    'label' => __('Google Storage: USA (Multi-Regional)', 'backwpup'),
                    'region' => 'US',
                    'endpoint' => 'https://storage.googleapis.com',
                ),
                'google-storage-asia' => array(
                    'label' => __('Google Storage: Asia (Multi-Regional)', 'backwpup'),
                    'region' => 'ASIA',
                    'endpoint' => 'https://storage.googleapis.com',
                ),
                'dreamhost' => array(
                    'label' => __('Dream Host Cloud Storage', 'backwpup'),
                    'endpoint' => 'https://objects-us-west-1.dream.io',
                ),
                'digital-ocean-ams3' => array(
                    'label' => __('DigitalOcean: AMS3', 'backwpup'),
                    'endpoint' => 'https://ams3.digitaloceanspaces.com',
                ),
                'scaleway-ams' => array(
                    'label' => __('Scaleway: AMS', 'backwpup'),
                    'endpoint' => 'https://s3.nl-ams.scw.cloud',
                ),
            )
        );
    }

    /**
     * Get the AWS destination of the passed id or base url.
     *
     * @param string $idOrUrl Destination id or endpoint
     *
     * @return self
     */
    public static function fromOption($idOrUrl)
    {
        $destinations = self::options();
        return new self($destinations[$idOrUrl]);
    }

    /**
     * Get the AWS destination class from options array
     *
     * @param array $optionsArr S3 options
     *
     * @return self
     */
    public static function fromOptionArray($optionsArr)
    {
        return new self($optionsArr);
    }

    /**
     * Get the Amazon S3 Client
     *
     * @param $accessKey
     * @param $secretKey
     *
     * @return \Aws\S3\S3Client
     */
    public function client($accessKey, $secretKey)
    {

        $s3Options = array(
            'signature' => $this->signature(),
            'credentials' => array(
                'key' => $accessKey,
                'secret' => BackWPup_Encryption::decrypt($secretKey),
            ),
            'region' => $this->region(),
            'http' => array(
                'verify' => BackWPup::get_plugin_data('cacert'),
            ),
            'version' => $this->version(),
        );

        if ($this->endpoint()) {
            $s3Options['endpoint'] = $this->endpoint();
            if ( ! $this->region()) {
                $s3Options['bucket_endpoint'] = true;
            }
        }

        $s3Options = apply_filters('backwpup_s3_client_options', $s3Options);

        return new \Aws\S3\S3Client($s3Options);
    }

    /**
     * The label of the destination
     * @return string
     */
    public function label()
    {
        return $this->options['label'];
    }

    /**
     * The region of the destination
     * @return string
     */
    public function region()
    {
        return $this->options['region'];
    }

    /**
     * The base url of the option. If empty than it should be a original AWS
     * @return string
     */
    public function endpoint()
    {
        return $this->options['endpoint'];
    }

    /**
     * The s3 version for the api like '2006-03-01'
     * @return string
     */
    public function version()
    {
        return $this->options['version'];
    }

    /**
     * The signature for the api like 'v4'
     * @return string
     */
    public function signature()
    {
        return $this->options['signature'];
    }

    /**
     * Destination supports multipart uploads
     * @return bool
     */
    public function supportsMultipart()
    {
        return (bool)$this->options['multipart'];
    }

    /**
     * Destination support only path style buckets
     * @return bool
     */
    public function onlyPathStyleBucket()
    {
        return (bool)$this->options['only_path_style_bucket'];
    }
}
