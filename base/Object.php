<?php

namespace RangelReale\mdh\base;

use RangelReale\mdh\MDHException;

class Object implements Configurable
{
    public static function className()
    {
        return get_called_class();
    }

    public function __construct($config = [])
    {
        if (!empty($config)) {
            ObjectUtil::configure($this, $config);
        }
        $this->init();
    }    
    
    public function init()
    {
    }

    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, 'set' . $name)) {
            throw new MDHException('Getting write-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new MDHException('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }
    
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new MDHException('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new MDHException('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }
    
    public function __isset($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            return false;
        }
    }

    public function __unset($name)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new MDHException('Unsetting read-only property: ' . get_class($this) . '::' . $name);
        }
    }
    
    public function hasProperty($name, $checkVars = true)
    {
        return $this->canGetProperty($name, $checkVars) || $this->canSetProperty($name, false);
    }

    public function canGetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name);
    }

    public function canSetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'set' . $name) || $checkVars && property_exists($this, $name);
    }

    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }
}
