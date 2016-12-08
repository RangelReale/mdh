<?php

namespace RangelReale\mdh;

/**
 * Exception InvalidDataHandlerException
 */
class InvalidDataHandlerException extends MDHException
{
    public function __construct($datahandler)
    {
        parent::__construct('Invalid data handler: '.$datahandler);
    }
}