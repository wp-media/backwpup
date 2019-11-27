<?php

namespace Inpsyde\BackWPup;

class MsAzureDestinationConfiguration {

    const MSAZURE_ACCNAME = 'msazureaccname';
    const MSAZURE_KEY = 'msazurekey';
    const MSAZURE_CONTAINER = 'msazurecontainer';

    /** @var string */
    private $msazureaccname;

    /** @var string */
    private $msazurekey;

    /** @var string */
    private $msazurecontainer;

    public function __construct($msazureaccname, $msazurekey, $msazurecontainer)
    {
        $items = array($msazureaccname, $msazurekey, $msazurecontainer);
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

    /**
     * @return string
     */
    public function msazureaccname()
    {
        return $this->msazureaccname;
    }

    /**
     * @return string
     */
    public function msazurekey()
    {
        return $this->msazurekey;
    }

    /**
     * @return string
     */
    public function msazurecontainer()
    {
        return $this->msazurecontainer;
    }
}
