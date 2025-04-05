<?php

namespace Epayco\Exceptions;

use Epayco\EpaycoException;

class ErrorException extends EpaycoException
{
    public function __construct( $message, $code, Exception $previous = null)
    {
        $newMessage = $message;
        $this->alternative = true;
        if($message == 'ES' || $message == 'EN'){
            $errors = json_decode(file_get_contents(EpaycoException::ERRORS_URL), true);    
            if($errors[(string)$code]){
                $newMessage = "{$errors[(string)$code][$message]}";
                $this->alternative = false;
            }
        }
        
        parent::__construct($newMessage, $code, $previous);
    }

    public function __toString()
    {
        if($this->alternative){
            return "[{$this->code}] {$this->message}";
        }
        return $this->message;
    }
}