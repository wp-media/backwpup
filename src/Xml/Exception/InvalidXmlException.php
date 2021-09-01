<?php

namespace Inpsyde\BackWPup\Xml\Exception;

class InvalidXmlException extends \RuntimeException
{
    /**
     * Array of XML errors.
     *
     * @var LibXMLError[]
     */
    private $errors;

    /**
     * Constructs an InvalidXmlException.
     *
     * @param string $message the exception message
     * @param array  $errors  the array of XML errors
     */
    public function __construct($message, array $errors = [])
    {
        $this->errors = $errors;

        parent::__construct($message);
    }

    /**
     * Get the XML errors.
     *
     * @return LibXMLError[] array of XML errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
