<?php

namespace Inpsyde\BackWPup\Xml;

use Inpsyde\BackWPup\Xml\Exception\InvalidWxrFileException;
use Inpsyde\BackWPup\Xml\Exception\InvalidXmlException;

class WxrValidator
{
    /**
     * The file to parse.
     *
     * @var string
     */
    private $file;

    /**
     * Constructs a WxrValidator.
     *
     * @param string $file the file to parse
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Validates that the file is a valid WXR file.
     *
     * @return bool true if the file is a valid wxr file
     *
     * @throws InvalidXmlException     if the given file is not valid XML
     * @throws InvalidWxrFileException the file is not a valid WXR file
     */
    public function validateWxr()
    {
        $internalErrors = libxml_use_internal_errors(true);

        try {
            $dom = new \DOMDocument();

            $oldValue = null;
            if (function_exists('libxml_disable_entity_loader') && version_compare(\PHP_VERSION, '8.0.0', '<') === true) {
                $oldValue = libxml_disable_entity_loader();
            }

            $result = $dom->loadXML(file_get_contents($this->file), \LIBXML_PARSEHUGE);

            if ($oldValue !== null) {
                libxml_disable_entity_loader($oldValue);
            }

            if (!$result) {
                throw new InvalidXmlException(__('The provided XML is invalid', 'backwpup'), libxml_get_errors());
            }

            $xpath = new \DOMXPath($dom);
            $version = $xpath->query('/rss/channel/wp:wxr_version');
            if ($version === false
                || $version->length === 0
                || preg_match('/^\d+\.\d+$/', $version[0]->nodeValue) === 0) {
                throw new InvalidWxrFileException(__('This does not appear to be a WXR file, missing/invalid WXR version number', 'backwpup'));
            }
        } finally {
            libxml_use_internal_errors($internalErrors);
        }
    }
}
