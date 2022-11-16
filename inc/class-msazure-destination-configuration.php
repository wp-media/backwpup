<?php

namespace Inpsyde\BackWPup;

class MsAzureDestinationConfiguration
{
    public const MSAZURE_ACCNAME = 'msazureaccname';
    public const MSAZURE_KEY = 'msazurekey';
    public const MSAZURE_CONTAINER = 'msazurecontainer';

    /**
     * @var string
     */
    private $msazureaccname;

    /**
     * @var string
     */
    private $msazurekey;

    /**
     * @var string
     */
    private $msazurecontainer;

    /**
     * @var bool
     */
    private $new = false;

    public function __construct($msazureaccname, $msazurekey, $msazurecontainer)
    {
        $items = [$msazureaccname, $msazurekey, $msazurecontainer];
        $areConfigPartsValid = array_filter($items);
        if (count($areConfigPartsValid) !== count($items)) {
            throw new \UnexpectedValueException(
                'Invalid configuration data.'
            );
        }

        $this->msazureaccname = $msazureaccname;
        $this->msazurekey = $msazurekey;
        $this->msazurecontainer = $msazurecontainer;
    }

    public static function withNewContainer(string $accountName, string $key, string $container): self
    {
        $configuration = new self($accountName, $key, $container);
        $configuration->new = true;

        return $configuration;
    }

    public function msazureaccname(): string
    {
        return $this->msazureaccname;
    }

    public function msazurekey(): string
    {
        return $this->msazurekey;
    }

    public function msazurecontainer(): string
    {
        return $this->msazurecontainer;
    }

    public function isNew(): bool
    {
        return $this->new;
    }
}
