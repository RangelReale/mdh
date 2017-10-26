<?php

namespace RangelReale\mdh;

use RangelReale\mdh\base\Object;

abstract class BaseDataHandler extends Object implements IDataHandler
{
    private $_mdh;
    private $_converter;

    public function __construct($mdh, $converter, $config = [])
    {
        $this->_mdh = $mdh;
        $this->_converter = $converter;
        parent::__construct($config);
    }

    public function mdh()
    {
        return $this->_mdh;
    }

    public function converter()
    {
        return $this->_converter;
    }    
}