<?php
/**
 * Exception thrown when there is an error in the Dropbox request.
 */
class BackWPup_Destination_Dropbox_API_Request_Exception extends BackWPup_Destination_Dropbox_API_Exception
{
    /**
     * @var string[]|null
     */
    protected $error;

    /**
     * @param string[]|null     $error
     */
    public function __construct(string $message, int $code = 0, ?Exception $previous = null, ?array $error = null)
    {
        $this->error = $error;
        parent::__construct($message, $code, $previous);
    }

    public function getError(): ?array
    {
        return $this->error;
    }
}
