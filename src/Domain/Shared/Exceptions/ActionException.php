<?php

namespace Domain\Shared\Exceptions;

use Exception;

class ActionException extends Exception
{
    // Optional: default message
    protected $message = 'Something went wrong!';
    
    // Optional: default HTTP status code
    protected $code = 400;

    public readonly array $data;

    public function __construct($message = null, $code = null, array $data = [])
    {
        $this->data = $data;
        
        parent::__construct(
            $message ?? $this->message,
            $code ?? $this->code
        );
    }

    // It is just a wrapper to convert type to ActionException.
    static function from(Exception $e)
    {
        return new self($e->getMessage(), is_int($e->getCode()) ? $e->getCode() : null);
    }

    public function getData() :array
    {
        return $this->data;
    }
    
}
