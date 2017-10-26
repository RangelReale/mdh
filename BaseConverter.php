<?php

namespace RangelReale\mdh;

use RangelReale\mdh\base\Object;

class BaseConverter extends Object implements IConverter
{
    private $_mdh;
    private $_datahandlersdef = [];
    private $_datahandlers = [];

    public function __construct($mdh, $config = [])
    {
        parent::__construct($config);
        
        if (!($mdh instanceof BaseMDH))
            throw new MDHException('Invalid MDH class');
        
        $this->_mdh = $mdh;
    }

    public function mdh()
    {
        return $this->_mdh;
    }
    
    public function canConvert($datatype)
    {
        return isset($this->_datahandlersdef[$datatype]);
    }
    
    public function parse($datatype, $value, $options = [])
    {
        return $this->getHandler($datatype)->parse($value, $options);
    }
    
    public function format($datatype, $value, $options = [])
    {
        return $this->getHandler($datatype)->format($value, $options);
    }
    
    public function getHandler($datatype)
    {
        if (isset($this->_datahandlers[$datatype])) {
            return $this->_datahandlers[$datatype];
        }
        if (isset($this->_datahandlersdef[$datatype])) {
            $this->_datahandlers[$datatype] = Util::createObject($this->_datahandlersdef[$datatype], $this);
            return $this->_datahandlers[$datatype];
        }
        throw new InvalidDataHandlerException($datatype);
    }
    
    public function setHandler($datatype, $handler)
    {
        if (is_array($handler)) {
            $this->_datahandlersdef[$datatype] = $handler;
        } elseif ($handler instanceof IDataHandler) {
            $this->_datahandlersdef[$datatype] = true;
            $this->_datahandlers[$datatype] = $handler;
        } else {
            throw new MDHException('Invalid handler');
        }
    }
}