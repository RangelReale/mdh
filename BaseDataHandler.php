<?php

namespace RangelReale\mdh;

use RangelReale\mdh\base\BaseObject;

abstract class BaseDataHandler extends BaseObject implements IDataHandler
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