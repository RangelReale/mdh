<?php

namespace RangelReale\mdh\user;

use RangelReale\mdh\BaseConverter;
use RangelReale\mdh\IDataHandler;
use RangelReale\mdh\Util;
use RangelReale\mdh\DataConversionException;

/**
 * Class UserConverter
 */
class UserConverter extends BaseConverter
{
    private $_deflocale;
    private $_locales = [];
    
    public function __construct($mdh, $config = [])
    {
        parent::__construct($mdh, $config);
        
        $this->_deflocale = new UserConverterLocale();

        $this->setHandler('boolean', ['RangelReale\mdh\user\UserConverter_DataHandler_Boolean', Util::CREATEOBJECT_THIS]);
        $this->setHandler('decimal', ['RangelReale\mdh\user\UserConverter_DataHandler_Decimal', Util::CREATEOBJECT_THIS, 2, \NumberFormatter::DECIMAL]);
        $this->setHandler('currency', ['RangelReale\mdh\user\UserConverter_DataHandler_Decimal', Util::CREATEOBJECT_THIS, 2, \NumberFormatter::CURRENCY]);
        $this->setHandler('decimalfull', ['RangelReale\mdh\user\UserConverter_DataHandler_Decimal', Util::CREATEOBJECT_THIS, -1]);
        $this->setHandler('date', ['RangelReale\mdh\user\UserDataHandler_Datetime', Util::CREATEOBJECT_THIS, 'date']);
        $this->setHandler('time', ['RangelReale\mdh\user\UserDataHandler_Datetime', Util::CREATEOBJECT_THIS, 'time']);
        $this->setHandler('datetime', ['RangelReale\mdh\user\UserDataHandler_Datetime', Util::CREATEOBJECT_THIS, 'datetime']);
    }

    public function setLocale($locale, $userconverterlocale)
    {
        $this->_locales[$locale] = $userconverterlocale;
    }
    
    public function getLocale($locale)
    {
        if (isset($this->_locales[$locale])) {
            return $this->_locales[$locale];
        }
        return $this->_deflocale;
    }
    
    public function getLocaleDefault()
    {
        return $this->getLocale($this->mdh()->getLocale());
    }
}

class UserConverter_DataHandler_Boolean implements IDataHandler
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
        return $this->_converter->getLocaleDefault()->parseBoolean($value, $options);
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        return $this->_converter->getLocaleDefault()->formatBoolean($value, $options);
    }
}


class UserDataHandler_Datetime implements IDataHandler
{
    private $_converter;
    private $_type;
    
    private function _formatToICUType($type)
    {
        switch ($type)
        {
        case self::FORMAT_NONE:
            return \IntlDateFormatter::NONE;
        case self::FORMAT_MEDIUM:
            return \IntlDateFormatter::MEDIUM;
        case self::FORMAT_LONG:
            return \IntlDateFormatter::LONG;
        case self::FORMAT_FULL:
            return \IntlDateFormatter::FULL;
        }
        return \IntlDateFormatter::SHORT;
    }
    
    public function __construct($converter, $type)
    {
        $this->_converter = $converter;
        $this->_type = $type;
    }
    
    public function parse($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        $formatter = $this->createFormatter($options);
        $parse = false;
        foreach ($formatter as $fmt) {
            $parse = $fmt->parse($value);
            if ($parse !== false)
                break;
        }
        if ($parse === false)
            $this->_converter->mdh()->throwDataConversionException($this->_type, 'parse', $value, $options);
        $ret = new \DateTime();
        $ret->setTimestamp($parse);
        return $ret;
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        $value = Util::formatToDateTime($value);
        if ($value === false)
            $this->_converter->mdh()->throwDataConversionException($this->_type, 'format', $value, $options);
        $formatter = $this->createFormatterSingle($options);
        return $formatter->format($value);
    }


    protected function createFormatterSingle($options)
    {
        return $this->createFormatter($options)[0];
    }    
    
    protected function createFormatter($options)
    {
        $ltype = null;
        if (isset($options['format']) && $options['format'] !== null)
            $ltype = (int)$options['format'];
        
        $locale = $this->_converter->getLocaleDefault();
        $fmt = new UserConverterLocaleTimeFormat();
        
        switch ($this->_type)
        {
            case 'date': 
            {
                if ($ltype === null) 
                    $ltype=$this->_converter->mdh()->dateFormat;
                $fmt = $locale->getDateFormat($ltype);
                break;
            }
            case 'time': 
            {
                if ($ltype === null) 
                    $ltype=$this->_converter->mdh()->timeFormat;
                $fmt = $locale->getTimeFormat($ltype);
                break;
            }
            case 'datetime': 
            {
                if ($ltype === null) 
                    $ltype=$this->_converter->mdh()->dateTimeFormat;
                $fmt = $locale->getDateTimeFormat($ltype);
                break;
            }
        }
        
        $ret = [];
        if (!is_array($fmt)) $fmt=[$fmt];
        foreach ($fmt as $f) {
            /*
            echo '<span style="border: solid 1px black">';
            echo $this->_type, ' @ ', $datetype, ' @ ', $timetype, ' @ ', $pattern; 
            echo '</span><br/>';
             */
            
            $f = new \IntlDateFormatter($this->_converter->mdh()->getLocale(), 
                $this->_formatToICUType($f->dateFormat), 
                $this->_formatToICUType($f->timeFormat), 
                null, null, 
                $f->pattern);
            $f->setLenient(false);
            $ret[] = $f;
        }
        return $ret;
    }
}

class UserConverter_DataHandler_Decimal implements IDataHandler
{
    private $_converter;
    private $_decimals;
    private $_style;
    
    public function __construct($converter, $decimals = 2, $style = \NumberFormatter::DECIMAL)
    {
        $this->_converter = $converter;
        $this->_decimals = $decimals;
        $this->_style = $style;
    }
    
    public function parse($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        $offset = 0;
        $ret = $this->createFormatter($options)->parse($value, \NumberFormatter::TYPE_DOUBLE, $offset);
        if ($ret === false || $offset != strlen($value))
            $this->_converter->mdh()->throwDataConversionException('decimal', 'parse', $value, $options);
        return $ret;
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        return $this->createFormatter($options)->format($value);
    }
    
    private function createFormatter($options)
    {
        $decimals = $this->_decimals;
        if (is_array($options)) {
            if (isset($options['decimals'])) $decimals = $options['decimals'];
        }
        $formatter = new \NumberFormatter($this->_converter->mdh()->getLocale(), $this->_style);
        $formatter->setAttribute(\NumberFormatter::LENIENT_PARSE, false);
        if ($decimals >= 0) {
            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
            $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        } else {
            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 7);
        }
        return $formatter;
    }
}