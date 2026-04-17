<?php

class ApiException extends Exception
{
    private $statusCode;
    private $errorCode;

    public function __construct($message, $statusCode = 400, $errorCode = 'api_error', Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = (int) $statusCode;
        $this->errorCode = $errorCode;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
