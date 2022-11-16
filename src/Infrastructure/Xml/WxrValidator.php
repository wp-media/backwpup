<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Xml;

use Inpsyde\BackWPup\Infrastructure\Xml\Exception\InvalidWxrFileException;
use Inpsyde\BackWPup\Infrastructure\Xml\Exception\InvalidXmlException;

final class WxrValidator
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
     * @throws InvalidXmlException     If the given file is not valid XML
     * @throws InvalidWxrFileException The file is not a valid WXR file
     */
    public function validateWxr(): void
    {
        $internalErrors = libxml_use_internal_errors(true);

        try {
            $dom = new \DOMDocument();

            $oldValue = null;
            if (\function_exists('libxml_disable_entity_loader') && \PHP_VERSION_ID < 80000) {
                $oldValue = libxml_disable_entity_loader();
            }

            $xml = file_get_contents($this->file);
            if ($xml === false) {
                throw new InvalidXmlException(__('The XML file could not be read', 'backwpup'));
            }

            $result = $dom->loadXML($xml, LIBXML_PARSEHUGE);

            if ($oldValue !== null) {
                libxml_disable_entity_loader($oldValue);
            }

            if (!$result) {
                throw new InvalidXmlException(__('The provided XML is invalid', 'backwpup'), libxml_get_errors());
            }

            $xpath = new \DOMXPath($dom);
            $version = $xpath->query('/rss/channel/wp:wxr_version');
            if (
                $version === false
                || $version->length === 0
                || preg_match('/^\d+\.\d+$/', $version[0]->nodeValue) === 0
            ) {
                throw new InvalidWxrFileException(__('This does not appear to be a WXR file, missing/invalid WXR version number', 'backwpup'));
            }
        } finally {
            libxml_use_internal_errors($internalErrors);
        }
    }
}
