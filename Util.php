<?php

namespace RangelReale\mdh;

/**
 * Class Util
 */
class Util
{
    const CREATEOBJECT_THIS = '___THIS___';
    
    /**
     * Check if the variable is integer conversible
     */
    public static function isInteger($value) {
        return(ctype_digit(strval($value)));
    }
    
    /**
     * Returns a DateTime from the php value (can be timestamp or DateTime)
     */
    public static function formatToDateTime($value) {
        if (!($value instanceof \DateTime)) {
            if (!self::isInteger($value)) {
                return false;
            }
            $curvalue = $value;
            $value = new \DateTime();
            $value->setTimestamp($curvalue);
        }
        return $value;
    }
    
    /**
     * @param array $object class definition, [0]=class name, rest is constructor params
     */
    public static function createObject($object, $objthis)
    {
        $class = array_shift($object);
        $reflection = new \ReflectionClass($class);
        foreach ($object as $okey => $ovalue) {
            if ($ovalue === self::CREATEOBJECT_THIS) {
                $object[$okey] = $objthis;
            }
        }
        return $reflection->newInstanceArgs($object);
    }
}