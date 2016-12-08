<?php

namespace RangelReale\mdh\user;

use RangelReale\mdh\IConverter;
use RangelReale\mdh\IDataHandler;
use RangelReale\mdh\Util;
use RangelReale\mdh\InvalidDataHandlerException;
use RangelReale\mdh\DataConversionException;

/**
 * Class UserConverter
 */
class UserConverter implements IConverter
{
    private $_mdh;
    private $_datahandlers = [];
    private $_deflocale;
    private $_locales = [];
    
    public function __construct($mdh)
    {
        $this->_mdh = $mdh;
        
        $this->_deflocale = new UserConverterLocale();
        
        $this->_datahandlers['boolean'] = new UserConverter_DataHandler_Boolean($this);
        $this->_datahandlers['decimal'] = new UserConverter_DataHandler_Decimal($this, 2, \NumberFormatter::DECIMAL);
        $this->_datahandlers['currency'] = new UserConverter_DataHandler_Decimal($this, 2, \NumberFormatter::CURRENCY);
        $this->_datahandlers['decimalfull'] = new UserConverter_DataHandler_Decimal($this, -1);
        $this->_datahandlers['date'] = new UserDataHandler_Datetime($this, 'date');
        $this->_datahandlers['time'] = new UserDataHandler_Datetime($this, 'time');
        $this->_datahandlers['datetime'] = new UserDataHandler_Datetime($this, 'datetime');
    }
    
    public function mdh()
    {
        return $this->_mdh;
    }
    
    public function canConvert($data)
    {
        return isset($this->_datahandlers[$data]);
    }
    
    public function parse($data, $value, $options = [])
    {
        if (isset($this->_datahandlers[$data]))
            return $this->_datahandlers[$data]->parse($value, $options);
        
        throw new InvalidDataHandlerException($data);
    }
    
    public function format($data, $value, $options = [])
    {
        if (isset($this->_datahandlers[$data]))
            return $this->_datahandlers[$data]->format($value, $options);
        
        throw new InvalidDataHandlerException($data);
    }
    
    public function addHandler($data, $handler)
    {
        $this->_datahandlers[$data] = $handler;
    }
    
    public function addLocale($locale, $userconverterlocale)
    {
        $this->_locales[$locale] = $userconverterlocale;
    }
    
    public function getLocale($locale)
    {
        if (isset($this->_locales[$locale]))
            return $this->_locales[$locale];
        return $this->_deflocale;
    }
    
    public function getLocaleDefault()
    {
        return $this->getLocale($this->_mdh->getLocale());
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
        return $this->_converter->getLocaleDefault()->parseBoolean($value, $options);
    }
    
    public function format($value, $options)
    {
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
        $formatter = $this->createFormatter($options);
        $parse = false;
        foreach ($formatter as $fmt) {
            $parse = $fmt->parse($value);
            if ($parse !== false)
                break;
        }
        if ($parse === false)
            throw new DataConversionException($this->_type, 'parse', $value);
        $ret = new \DateTime();
        $ret->setTimestamp($parse);
        return $ret;
    }
    
    public function format($value, $options)
    {
        $value = Util::formatToDateTime($value, $this->_type);
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
        if (isset($options['format']))
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
        $this->_decimals = 2;
        $this->_style = $style;
    }
    
    public function parse($value, $options)
    {
        $ret = $this->createFormatter($options)->parse($value);
        if ($ret === false)
            throw new DataConversionException('decimal', 'parse', $value);
        return $ret;
    }
    
    public function format($value, $options)
    {
        return $this->createFormatter($options)->format($value);
    }
    
    private function createFormatter($options)
    {
        $decimals = $this->_decimals;
        if (is_array($options)) {
            if (isset($options['decimals'])) $decimals = $options['decimals'];
        }
        $formatter = new \NumberFormatter($this->_converter->mdh()->getLocale(), $this->_style);
        if ($decimals >= 0) {
            
            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
            $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        }
        return $formatter;
    }
}