<?php

namespace RangelReale\mdh;

class MDH extends BaseMDH
{
    private $_locale = 'en-US';
    
    public function getLocale()
    {
        return $this->_locale;
    }
    
    public function setLocale($value)
    {
        $this->_locale = $value;
    }
}