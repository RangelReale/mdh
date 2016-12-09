<?php

namespace RangelReale\mdh;

/**
 * Exception DataConversionException
 */
class DataConversionException extends MDHException
{
    public $datatype;
    public $parseOrFormat;
    public $value;
    public $options;
    public $extra;
    
    public function __construct($message, $datatype = null, $parseOrFormat = null, $value = null, $options = null, $extra = null)
    {
        parent::__construct($message);
        $this->datatype = $datatype;
        $this->parseOrFormat = $parseOrFormat;
        $this->value = $value;
        $this->options = $options;
        $this->extra = $extra;
    }
}