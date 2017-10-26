<?php

namespace RangelReale\mdh\def;

use RangelReale\mdh\BaseConverter;
use RangelReale\mdh\IDataHandler;
use RangelReale\mdh\Util;
use RangelReale\mdh\DataHandler_NOP;

/**
 * Class DefaultConverter
 */
class DefaultConverter extends BaseConverter
{
    public function __construct($mdh, $config = [])
    {
        parent::__construct($mdh, $config);

        $this->setHandler('raw', ['RangelReale\mdh\DataHandler_NOP']);
        $this->setHandler('text', ['RangelReale\mdh\def\DefaultConverter_DataHandler_Text']);
        $this->setHandler('boolean', ['RangelReale\mdh\def\DefaultConverter_DataHandler_Boolean']);
        $this->setHandler('integer', ['RangelReale\mdh\def\DefaultConverter_DataHandler_Integer', Util::CREATEOBJECT_THIS]);
        $this->setHandler('decimal', ['RangelReale\mdh\def\DefaultConverter_DataHandler_Decimal', Util::CREATEOBJECT_THIS]);
        $this->setHandler('currency', ['RangelReale\mdh\def\DefaultConverter_DataHandler_Decimal', Util::CREATEOBJECT_THIS]);
        $this->setHandler('decimalfull', ['RangelReale\mdh\def\DefaultConverter_DataHandler_Decimal', Util::CREATEOBJECT_THIS, -1]);
        $this->setHandler('date', ['RangelReale\mdh\def\DefaultConverter_DataHandler_Datetime', Util::CREATEOBJECT_THIS, 'date']);
        $this->setHandler('time', ['RangelReale\mdh\def\DefaultConverter_DataHandler_Datetime', Util::CREATEOBJECT_THIS, 'time']);
        $this->setHandler('datetime', ['RangelReale\mdh\def\DefaultConverter_DataHandler_Datetime', Util::CREATEOBJECT_THIS, 'datetime']);
        $this->setHandler('bytes', ['RangelReale\mdh\def\DefaultConverter_DataHandler_Bytes', Util::CREATEOBJECT_THIS]);
        $this->setHandler('timeperiod', ['RangelReale\mdh\def\DefaultConverter_DataHandler_TimePeriod', Util::CREATEOBJECT_THIS]);
        $this->setHandler('bitmask', ['RangelReale\mdh\def\DefaultConverter_DataHandler_Bitmask', Util::CREATEOBJECT_THIS]);
    }
}

class DefaultConverter_DataHandler_Text implements IDataHandler
{
    public function parse($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        return htmlspecialchars_decode($value, ENT_QUOTES | ENT_SUBSTITUTE);
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE);
    }
}

class DefaultConverter_DataHandler_Boolean implements IDataHandler
{
    public function parse($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        if ($value)
            return true;
        return false;
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        if ($value)
            return '1';
        return '0';
    }
}

class DefaultConverter_DataHandler_Integer implements IDataHandler
{
    private $_converter;
    
    public function __construct($converter)
    {
        $this->_converter = $converter;
    }
    
    public function parse($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        if (!Util::isInteger($value)) {
            $this->_converter->mdh()->throwDataConversionException('integer', 'parse', $value, $options);
        }
        return (int)$value;
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        if (!Util::isInteger($value)) {
            $this->_converter->mdh()->throwDataConversionException('integer', 'format', $value, $options);
        }
        return $value;
    }
}

class DefaultConverter_DataHandler_Decimal implements IDataHandler
{
    private $_converter;
    private $_decimals;
    
    public function __construct($converter, $decimals = 2)
    {
        $this->_converter = $converter;
        $this->_decimals = 2;
    }
    
    public function parse($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        if (!is_numeric($value)) {
            $this->_converter->mdh()->throwDataConversionException('decimal', 'parse', $value, $options);
        }
        return (double)$value;
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        $decimals = $this->_decimals;
        if (is_array($options)) {
            if (isset($options['decimals'])) 
                $decimals = $options['decimals'];
        }
        if ($decimals >= 0)
            return number_format($value, $decimals, '.', '');
        return $value;
    }
}

class DefaultConverter_DataHandler_Datetime implements IDataHandler
{
    private $_converter;
    private $_type;
    
    public function __construct($converter, $type)
    {
        $this->_converter = $converter;
        $this->_type = $type;
    }
    
    public function parse($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        $value = Util::formatToDateTime($value);
        if ($value === false)
            $this->_converter->mdh()->throwDataConversionException($this->_type, 'format', $value, $options);
        return $value;
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        $value = Util::formatToDateTime($value);
        if ($value === false)
            $this->_converter->mdh()->throwDataConversionException($this->_type, 'format', $value, $options);
        return $value->getTimestamp();
    }
}

class DefaultConverter_DataHandler_Bytes implements IDataHandler
{
    private $_converter;
    
    public function __construct($converter)
    {
        $this->_converter = $converter;
    }
    
    public function parse($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        $this->_converter->mdh()->throwDataConversionException('bytes', 'parse', $value, $options);
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        $decimals = 2;
        if (isset($options['decimals']))
            $decimals = $options['decimals'];
        
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
            $value = $this->_converter->mdh()->format($options['__converter'], 'decimal', $value, ['decimals'=>$decimals]);
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

class DefaultConverter_DataHandler_TimePeriod implements IDataHandler
{
    private $_converter;
    
    public function __construct($converter)
    {
        $this->_converter = $converter;
    }
    
    public function parse($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        if (Util::isInteger($value)) {
            return (int)$value;
        }
        $time = $this->createFormatter($options)->parse($value);
        if ($time === false) {
            $this->_converter->mdh()->throwDataConversionException('timeperiod', 'parse', $value, $options);
        }
        $dt=getdate($time);
        return (int)($dt['seconds'] + ($dt['minutes'] * 60) + ($dt['hours'] * 60 * 60));
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        if (!is_numeric($value)) {
            $this->_converter->mdh()->throwDataConversionException('timeperiod', 'format', $value, $options);
        }
        $hours = intval(intval($value) / 3600);
        $minutes = intval(($value / 60) % 60);
        $seconds = intval($value % 60);
        return $this->createFormatter($options)->format(mktime($hours, $minutes, $seconds, null, null, null));
    }
    
    protected function createFormatter($options)
    {
        $f = new \IntlDateFormatter($this->_converter->mdh()->getLocale(), 
            \IntlDateFormatter::NONE, 
            \IntlDateFormatter::NONE, 
            null, null, 
            'HH:mm:ss');
        $f->setLenient(false);
        return $f;
    }    
}

class DefaultConverter_DataHandler_Bitmask implements IDataHandler
{
    private $_converter;
    
    public function __construct($converter)
    {
        $this->_converter = $converter;
    }
    
	/**
	 * Parses the value as a bit mask.
	 * @param mixed the list of items as an array or comma-delimited string
	 * @return integer the bit mask
	 */
    public function parse($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        if (!is_array($value))
            $value = explode(',', $value);

        $ret = 0;
        foreach ($value as $item)
        {
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
        if ($value === null || $value == '')
            return null;
        
        if (!Util::isInteger($value)) {
            $this->_converter->mdh()->throwDataConversionException('bitmask', 'format', $value, $options);
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
