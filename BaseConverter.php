<?php

namespace RangelReale\mdh;

use RangelReale\mdh\base\Object;
use RangelReale\mdh\base\ObjectUtil;

class BaseConverter extends Object implements IConverter
{
    private $_mdh;
    private $_datahandlersdef = [];
    private $_datahandlers = [];

    public function __construct($mdh, $config = [])
    {
        $this->_mdh = $mdh;
        parent::__construct($config);
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
    
    public function getHandler($id, $throwException = true)
    {
        if (isset($this->_datahandlers[$id])) {
            return $this->_datahandlers[$id];
        }

        if (isset($this->_datahandlersdef[$id])) {
            $definition = $this->_datahandlersdef[$id];
            if (is_object($definition) && !$definition instanceof Closure) {
                return $this->_datahandlers[$id] = $definition;
            } else {
                return $this->_datahandlers[$id] = ObjectUtil::createObject($definition, [$this->_mdh, $this]);
            }
        } elseif ($throwException) {
            throw new InvalidDataHandlerException($id);
        } else {
            return null;
        }            
    }
    
    public function setHandler($id, $definition)
    {
        if ($definition === null) {
            unset($this->_datahandlers[$id], $this->_datahandlersdef[$id]);
            return;
        }

        unset($this->_datahandlers[$id]);

        if (is_object($definition) || is_callable($definition, true)) {
            // an object, a class name, or a PHP callable
            $this->_datahandlersdef[$id] = $definition;
        } elseif (is_array($definition)) {
            // a configuration array
            if (isset($definition['class'])) {
                $this->_datahandlersdef[$id] = $definition;
            } else {
                throw new MDHException("The configuration for the \"$id\" handler must contain a \"class\" element.");
            }
        } else {
            throw new MDHException("Unexpected configuration type for the \"$id\" handler: " . gettype($definition));
        }
    }
    
    public function getHandlers($returnDefinitions = true)
    {
        return $returnDefinitions ? $this->_datahandlersdef : $this->_datahandlers;
    }
    
    public function setHandlers($handlers)
    {
        foreach ($handlers as $id => $handler) {
            $this->setHandler($id, $handler);
        }
    }
    
}