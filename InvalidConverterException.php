<?php

namespace RangelReale\mdh;

/**
 * Exception InvalidConverterException
 */
class InvalidConverterException extends MDHException
{
    public function __construct($converter)
    {
        parent::__construct('Invalid converter: '.$converter);
    }
}