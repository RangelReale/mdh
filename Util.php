<?php

namespace RangelReale\mdh;

/**
 * Class Util
 */
class Util
{
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
            $value->setTimezone(new \DateTimeZone('UTC'));
            $value->setTimestamp($curvalue);
        }
        return $value;
    }
}