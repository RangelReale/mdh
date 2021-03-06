<?php

namespace RangelReale\mdh\user;

use RangelReale\mdh\BaseConverter;
use RangelReale\mdh\BaseDataHandler;
use RangelReale\mdh\Util;
use RangelReale\mdh\base\ObjectUtil;

/**
 * Class UserConverter
 */
class UserConverter extends BaseConverter
{
    private $_deflocale;
    private $_locales = [];
    private $_localesdef = [];
    
    public function init()
    {
        $this->_deflocale = new UserConverterLocale($this->mdh());
        
        $this->setHandlers([
            'boolean' => ['class' => 'RangelReale\mdh\user\UserConverter_DataHandler_Boolean'],
            'decimal' => ['class' => 'RangelReale\mdh\user\UserConverter_DataHandler_Decimal', 'decimals'=> 2, 'style'=> \NumberFormatter::DECIMAL],
            'currency' => ['class' => 'RangelReale\mdh\user\UserConverter_DataHandler_Decimal', 'decimals' => 2, 'style' => \NumberFormatter::CURRENCY],
            'decimalfull' => ['class' => 'RangelReale\mdh\user\UserConverter_DataHandler_Decimal', 'decimals' => -1],
            'date' => ['class' => 'RangelReale\mdh\user\UserDataHandler_Datetime', 'type' => 'date'],
            'time' => ['class' => 'RangelReale\mdh\user\UserDataHandler_Datetime', 'type' => 'time'],
            'datetime' => ['class' => 'RangelReale\mdh\user\UserDataHandler_Datetime', 'type' => 'datetime'],
        ]);
    }

    
    public function getLocale($id)
    {
        if (isset($this->_locales[$id])) {
            return $this->_locales[$id];
        }

        if (isset($this->_localesdef[$id])) {
            $definition = $this->_localesdef[$id];
            if (is_object($definition) && !$definition instanceof Closure) {
                return $this->_locales[$id] = $definition;
            } else {
                return $this->_locales[$id] = ObjectUtil::createObject($definition, [$this->mdh()]);
            }
        } else {
            return $this->_deflocale;
        }                    
    }

    public function setLocale($id, $definition)
    {
        if ($definition === null) {
            unset($this->_locales[$id], $this->_localesdef[$id]);
            return;
        }

        unset($this->_locales[$id]);

        if (is_object($definition) || is_callable($definition, true)) {
            // an object, a class name, or a PHP callable
            $this->_localesdef[$id] = $definition;
        } elseif (is_array($definition)) {
            // a configuration array
            if (isset($definition['class'])) {
                $this->_localesdef[$id] = $definition;
            } else {
                throw new MDHException("The configuration for the \"$id\" locale must contain a \"class\" element.");
            }
        } else {
            throw new MDHException("Unexpected configuration type for the \"$id\" locale: " . gettype($definition));
        }
    }
    
    public function getLocales($returnDefinitions = true)
    {
        return $returnDefinitions ? $this->_localesdef : $this->_locales;
    }
    
    public function setLocales($locales)
    {
        foreach ($locales as $id => $locale) {
            $this->setLocale($id, $locale);
        }
    }
    
    public function getLocaleDefault()
    {
        return $this->getLocale($this->mdh()->locale);
    }
}

class UserConverter_DataHandler_Boolean extends BaseDataHandler
{
    public function parse($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        return $this->converter()->getLocaleDefault()->parseBoolean($value, $options);
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        return $this->converter()->getLocaleDefault()->formatBoolean($value, $options);
    }
}


class UserDataHandler_Datetime extends BaseDataHandler
{
    public $type;
    
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
    
    public function parse($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        $formatter = $this->createFormatter($options);
        $parse = false;
        foreach ($formatter as $fmt) {
            $parse = $fmt->parse($value);
            if ($parse !== false)
                break;
        }
        if ($parse === false) {
            $this->mdh()->throwDataConversionException($this->type, 'parse', $value, $options);
        }
        $ret = new \DateTime();
        $ret->setTimestamp($parse);
        return $ret;
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
        if (isset($options['format']) && $options['format'] !== null) {
            $ltype = (int)$options['format'];
        }
        
        $locale = $this->converter()->getLocaleDefault();
        $fmt = new UserConverterLocaleTimeFormat();
        
        switch ($this->type)
        {
            case 'date': 
            {
                if ($ltype === null) 
                    $ltype=$this->mdh()->dateFormat;
                $fmt = $locale->getDateFormat($ltype);
                break;
            }
            case 'time': 
            {
                if ($ltype === null) 
                    $ltype=$this->mdh()->timeFormat;
                $fmt = $locale->getTimeFormat($ltype);
                break;
            }
            case 'datetime': 
            {
                if ($ltype === null) 
                    $ltype=$this->mdh()->dateTimeFormat;
                $fmt = $locale->getDateTimeFormat($ltype);
                break;
            }
        }
        
        $ret = [];
        if (!is_array($fmt)) $fmt=[$fmt];
        foreach ($fmt as $f) {
            /*
            echo '<span style="border: solid 1px black">';
            echo $this->type, ' @ ', $datetype, ' @ ', $timetype, ' @ ', $pattern; 
            echo '</span><br/>';
             */
            
            $f = new \IntlDateFormatter($this->mdh()->locale, 
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

class UserConverter_DataHandler_Decimal extends BaseDataHandler
{
    public $decimals = 2;
    public $style = \NumberFormatter::DECIMAL;
    
    public function parse($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        $offset = 0;
        $ret = $this->createFormatter($options)->parse($value, \NumberFormatter::TYPE_DOUBLE, $offset);
        if ($ret === false || $offset != strlen($value)) {
            $this->mdh()->throwDataConversionException('decimal', 'parse', $value, $options);
        }
        return $ret;
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        return $this->createFormatter($options)->format($value);
    }
    
    private function createFormatter($options)
    {
        $decimals = $this->decimals;
        if (is_array($options)) {
            if (isset($options['decimals'])) $decimals = $options['decimals'];
        }
        $formatter = new \NumberFormatter($this->mdh()->locale, $this->style);
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