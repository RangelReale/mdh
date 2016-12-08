<?php

namespace RangelReale\mdh;

/**
 * Class Util
 */
class Util
{
    // check if the variable is integer conversible
    public static function isInteger($value) {
        return(ctype_digit(strval($value)));
    }
    
    // returns a DateTime from the php value (can be timestamp or DateTime)
    public static function formatToDateTime($value, $type) {
        if (!($value instanceof \DateTime)) {
            if (!self::isInteger($value)) {
                throw new DataConversionException($type, 'format', $value);
            }
            $curvalue = $value;
            $value = new \DateTime();
            $value->setTimestamp($curvalue);
        }
        return $value;
    }
}