<?php

namespace RangelReale\mdh;

class MDH extends BaseMDH
{
    public $locale = 'en-US';

    public function getLocale()
    {
        return $this->locale;
    }
}