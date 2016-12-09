<?php

namespace RangelReale\mdh;

/**
 * Class MultiConversion
 */
class MultiConversion
{
    /**
     * [
     *   'attribute' => [
     *       'dataType' => '',
     *       'options' => [],
     *       'optionsFrom' => [],
     *       'optionsTo' => [],
     *   ],
     * ]
     */
    public $attributes;
    
    public $throwErrors = true;
    
    public function __construct($attributes, $throwErrors = true)
    {
        $this->attributes = $attributes;
    }
}
