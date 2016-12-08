<?php

namespace RangelReale\mdh\def;

use RangelReale\mdh\IConverter;
use RangelReale\mdh\IDataHandler;
use RangelReale\mdh\Util;
use RangelReale\mdh\InvalidDataHandlerException;
use RangelReale\mdh\DataConversionException;

/**
 * Class DefaultConverter
 */
class DefaultConverter implements IConverter
{
    private $_mdh;
    private $_datahandlers = [];
    
    public function __construct($mdh)
    {
        $this->_mdh = $mdh;
        
        $this->_datahandlers['raw'] = new DefaultConverter_DataHandler_Raw();
        $this->_datahandlers['text'] = new DefaultConverter_DataHandler_Text();
        $this->_datahandlers['boolean'] = new DefaultConverter_DataHandler_Boolean();
        $this->_datahandlers['integer'] = new DefaultConverter_DataHandler_Integer();
        $this->_datahandlers['decimal'] = new DefaultConverter_DataHandler_Decimal();
        $this->_datahandlers['currency'] = new DefaultConverter_DataHandler_Decimal();
        $this->_datahandlers['decimalfull'] = new DefaultConverter_DataHandler_Decimal(-1);
        $this->_datahandlers['bytes'] = new DefaultConverter_DataHandler_Bytes($this);
        $this->_datahandlers['timeperiod'] = new DefaultConverter_DataHandler_TimePeriod($this);
    }
    
    public function mdh()
    {
        return $this->_mdh;
    }
    
    public function canConvert($datatype)
    {
        return isset($this->_datahandlers[$datatype]);
    }
    
    public function parse($datatype, $value, $options = [])
    {
        if (isset($this->_datahandlers[$datatype]))
            return $this->_datahandlers[$datatype]->parse($value, $options);
        
        throw new InvalidDataHandlerException($datatype);
    }
    
    public function format($datatype, $value, $options = [])
    {
        if (isset($this->_datahandlers[$datatype]))
            return $this->_datahandlers[$datatype]->format($value, $options);
        
        throw new InvalidDataHandlerException($datatype);
    }
    
    public function addHandler($datatype, $handler)
    {
        $this->_datahandlers[$datatype] = $handler;
    }
}

class DefaultConverter_DataHandler_Raw implements IDataHandler
{
    public function parse($value, $options)
    {
        return $value;
    }
    
    public function format($value, $options)
    {
        return $value;
    }
}

class DefaultConverter_DataHandler_Text implements IDataHandler
{
    public function parse($value, $options)
    {
        return htmlspecialchars_decode($value, ENT_QUOTES | ENT_SUBSTITUTE);
    }
    
    public function format($value, $options)
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE);
    }
}

class DefaultConverter_DataHandler_Boolean implements IDataHandler
{
    public function parse($value, $options)
    {
        if ($value)
            return true;
        return false;
    }
    
    public function format($value, $options)
    {
        if ($value)
            return '1';
        return '0';
    }
}

class DefaultConverter_DataHandler_Integer implements IDataHandler
{
    public function parse($value, $options)
    {
        if (!Util::isInteger($value)) {
            throw new DataConversionException('integer', 'parse', $value);
        }
        return (int)$value;
    }
    
    public function format($value, $options)
    {
        return $value;
    }
}

class DefaultConverter_DataHandler_Decimal implements IDataHandler
{
    private $_decimals;
    
    public function __construct($decimals = 2)
    {
        $this->_decimals = 2;
    }
    
    public function parse($value, $options)
    {
        if (!is_numeric($value)) {
            throw new DataConversionException('decimal', 'parse', $value);
        }
        return (float)$value;
    }
    
    public function format($value, $options)
    {
        $decimals = $this->_decimals;
        if (is_array($options)) {
            if (isset($options['decimals'])) $decimals = $options['decimals'];
        }
        if ($decimals >= 0)
            return number_format($value, $decimals, '.', '');
        return $value;
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
        throw new DataConversionException('bytes', 'parse', $value);
    }
    
    public function format($value, $options)
    {
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
        $time = $this->_converter->mdh()->parse(isset($options['__converter'])?$options['__converter']:'', 'time', $value, $options);
        $dt=getdate($time->getTimestamp());
        return $dt['seconds'] + ($dt['minutes'] * 60) + ($dt['hours'] * 60 * 60);
    }
    
    public function format($value, $options)
    {
        $hours = intval(intval($value) / 3600);
        $minutes = intval(($value / 60) % 60);
        $seconds = intval($value % 60);
        $dt = new \DateTime();
        $dt->setTimestamp(mktime($hours, $minutes, $seconds, null, null, null));
        return $this->_converter->mdh()->format(isset($options['__converter'])?$options['__converter']:'', 'time', $dt, $options);
    }
}
