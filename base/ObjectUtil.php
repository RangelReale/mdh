<?php

namespace RangelReale\mdh\base;

use ReflectionClass;
use RangelReale\mdh\MDHException;

class ObjectUtil
{
    public static function createObject($type, array $params = [])
    {
        if (is_string($type)) {
            return static::buildObject($type, $params);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            return static::buildObject($class, $params, $type);
        } elseif (is_array($type)) {
            throw new MDHException('Object configuration must be an array containing a "class" element.');
        } else {
            throw new MDHException('Unsupported configuration type: ' . gettype($type));
        }
    }

    public static function buildObject($class, $params = [], $config = [])
    {
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new MDHException('Class is not instantiable: '. $reflection->name);
        }
        $dependencies = [];
        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    $dependencies[] = null;
                }
            }
        }
        
        foreach ($params as $index => $param) {
            $dependencies[$index] = $param;
        }
        
        if (empty($config)) {
            return $reflection->newInstanceArgs($dependencies);
        }
        
        if (!empty($dependencies) && $reflection->implementsInterface('RangelReale\mdh\base\Configurable')) {
            // set $config as the last parameter (existing one will be overwritten)
            $dependencies[count($dependencies) - 1] = $config;
            return $reflection->newInstanceArgs($dependencies);
        } else {
            $object = $reflection->newInstanceArgs($dependencies);
            foreach ($config as $name => $value) {
                $object->$name = $value;
            }
            return $object;
        }
    }
    
    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }
        return $object;
    }    
}