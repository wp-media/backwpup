<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Xml\Exception;

final class InvalidXmlException extends \RuntimeException
{
    /**
     * Array of XML errors.
     *
     * @var \LibXMLError[]
     */
    private $errors = [];

    /**
     * Constructs an InvalidXmlException.
     *
     * @param string         $message The exception message
     * @param \LibXMLError[] $errors  The array of XML errors
     */
    public function __construct(string $message, array $errors = [])
    {
        $this->errors = $errors;

        parent::__construct($message);
    }

    /**
     * Get the XML errors.
     *
     * @return \LibXMLError[] Array of XML errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
