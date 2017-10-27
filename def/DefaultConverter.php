<?php

namespace RangelReale\mdh\def;

use RangelReale\mdh\BaseConverter;
use RangelReale\mdh\BaseDataHandler;
use RangelReale\mdh\Util;

/**
 * Class DefaultConverter
 */
class DefaultConverter extends BaseConverter
{
    public function init()
    {
        parent::init();
        $this->setHandlers([
            'raw' => ['class' => 'RangelReale\mdh\DataHandler_NOP'],
            'text' => ['class' => 'RangelReale\mdh\def\DefaultConverter_DataHandler_Text'],
            'boolean' => ['class' => 'RangelReale\mdh\def\DefaultConverter_DataHandler_Boolean'],
            'integer' => ['class' => 'RangelReale\mdh\def\DefaultConverter_DataHandler_Integer'],
            'decimal' => ['class' => 'RangelReale\mdh\def\DefaultConverter_DataHandler_Decimal'],
            'currency' => ['class' => 'RangelReale\mdh\def\DefaultConverter_DataHandler_Decimal'],
            'decimalfull' => ['class' => 'RangelReale\mdh\def\DefaultConverter_DataHandler_Decimal', 'decimals' => -1],
            'date' => ['class' => 'RangelReale\mdh\def\DefaultConverter_DataHandler_Datetime', 'type' => 'date'],
            'time' => ['class' => 'RangelReale\mdh\def\DefaultConverter_DataHandler_Datetime', 'type' => 'time'],
            'datetime' => ['class' => 'RangelReale\mdh\def\DefaultConverter_DataHandler_Datetime', 'type' => 'datetime'],
            'bytes' => ['class' => 'RangelReale\mdh\def\DefaultConverter_DataHandler_Bytes'],
            'timeperiod' => ['class' => 'RangelReale\mdh\def\DefaultConverter_DataHandler_TimePeriod'],
            'bitmask' => ['class' => 'RangelReale\mdh\def\DefaultConverter_DataHandler_Bitmask'],
        ]);
    }
}

class DefaultConverter_DataHandler_Text extends BaseDataHandler
{
    public function parse($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        return htmlspecialchars_decode($value, ENT_QUOTES | ENT_SUBSTITUTE);
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE);
    }
}

class DefaultConverter_DataHandler_Boolean extends BaseDataHandler
{
    public function parse($value, $options) 
    {
        if ($value === null || $value == '') {
            return null;
        }
        if ($value) {
            return true;
        }
        return false;
    }
    
    public function format($value, $options) 
    {
        if ($value === null || $value == '') {
            return null;
        }
        if ($value) {
            return '1';
        }
        return '0';
    }
}

class DefaultConverter_DataHandler_Integer extends BaseDataHandler
{
    public function parse($value, $options) 
    {
        if ($value === null || $value == '') {
            return null;
        }
        if (!Util::isInteger($value)) {
            $this->mdh()->throwDataConversionException('integer', 'parse', $value, $options);
        }
        return (int)$value;
    }
    
    public function format($value, $options) 
    {
        if ($value === null || $value == '') {
            return null;
        }
        if (!Util::isInteger($value)) {
            $this->mdh()->throwDataConversionException('integer', 'format', $value, $options);
        }
        return $value;
    }
}

class DefaultConverter_DataHandler_Decimal extends BaseDataHandler
{
    public $decimals = 2;
    
    public function parse($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        if (!is_numeric($value)) {
            $this->mdh()->throwDataConversionException('decimal', 'parse', $value, $options);
        }
        return (double)$value;
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        $decimals = $this->decimals;
        if (is_array($options)) {
            if (isset($options['decimals']))  {
                $decimals = $options['decimals'];
            }
        }
        if ($decimals >= 0) {
            return number_format($value, $decimals, '.', '');
        }
        return $value;
    }
}

class DefaultConverter_DataHandler_Datetime extends BaseDataHandler
{
    public $type;
    
    public function parse($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        $value = Util::formatToDateTime($value);
        if ($value === false) {
            $this->mdh()->throwDataConversionException($this->type, 'format', $value, $options);
        }
        return $value;
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        $value = Util::formatToDateTime($value);
        if ($value === false) {
            $this->mdh()->throwDataConversionException($this->type, 'format', $value, $options);
        }
        return $value->getTimestamp();
    }
}

class DefaultConverter_DataHandler_Bytes extends BaseDataHandler
{
    public function parse($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        $this->mdh()->throwDataConversionException('bytes', 'parse', $value, $options);
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        $decimals = 2;
        if (isset($options['decimals'])) {
            $decimals = $options['decimals'];
        }
        
        $position = 0;
        do {
            if (abs($value) < 1000) {
                break;
            }
            $value /= 1000;
            $position++;
        } while ($position < 5);

        // no decimals for bytes
        if ($position === 0) {
            $decimals = 0;
        }

        if (isset($options['__converter'])) {
            $value = $this->mdh()->format($options['__converter'], 'decimal', $value, ['decimals'=>$decimals]);
        } else {
            $value = round($value, $decimals);
        }
        
        switch ($position) {
            case 0:
                return $value.' B';
            case 1:
                return $value.' KB';
            case 2:
                return $value.' MB';
            case 3:
                return $value.' GB';
            case 4:
                return $value.' TB';
            default:
                return $value.' PB';
        }
    }
}

class DefaultConverter_DataHandler_TimePeriod extends BaseDataHandler
{
    public function parse($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        if (Util::isInteger($value)) {
            return (int)$value;
        }
        $time = $this->createFormatter($options)->parse($value);
        if ($time === false) {
            $this->mdh()->throwDataConversionException('timeperiod', 'parse', $value, $options);
        }
        $dt=getdate($time);
        return (int)($dt['seconds'] + ($dt['minutes'] * 60) + ($dt['hours'] * 60 * 60));
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        if (!is_numeric($value)) {
            $this->mdh()->throwDataConversionException('timeperiod', 'format', $value, $options);
        }
        $hours = intval(intval($value) / 3600);
        $minutes = intval(($value / 60) % 60);
        $seconds = intval($value % 60);
        return $this->createFormatter($options)->format(mktime($hours, $minutes, $seconds, null, null, null));
    }
    
    protected function createFormatter($options)
    {
        $f = new \IntlDateFormatter($this->mdh()->locale, 
            \IntlDateFormatter::NONE, 
            \IntlDateFormatter::NONE, 
            null, null, 
            'HH:mm:ss');
        $f->setLenient(false);
        return $f;
    }    
}

class DefaultConverter_DataHandler_Bitmask extends BaseDataHandler
{
	/**
	 * Parses the value as a bit mask.
	 * @param mixed the list of items as an array or comma-delimited string
	 * @return integer the bit mask
	 */
    public function parse($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        if (!is_array($value)) {
            $value = explode(',', $value);
        }

        $ret = 0;
        foreach ($value as $item) {
            $ret |= pow(2, $item);
        }
        return $ret;
    }
    
    /**
     * Formats the value as a bit mask.
     * @param integer the value to be formatted
     * @return array a list of the selected bits
     */
    public function format($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        
        if (!Util::isInteger($value)) {
            $this->mdh()->throwDataConversionException('bitmask', 'format', $value, $options);
        }
        $value = (int)$value;

        $ret = array();
        for ($i=0; $i<32; $i++)
        {
            $p = pow(2, $i);
            if (($value & $p)==$p)
                $ret[]=$i;
        }
        return $ret;
    }
}
