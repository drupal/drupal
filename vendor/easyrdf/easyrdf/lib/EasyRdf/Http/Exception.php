<?php


class EasyRdf_Http_Exception extends EasyRdf_Exception
{
    private $body;

    public function __construct($message = "", $code = 0, Exception $previous = null, $body = '')
    {
        parent::__construct($message, $code, $previous);
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }
}
