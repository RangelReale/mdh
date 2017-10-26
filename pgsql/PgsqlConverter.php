<?php

namespace RangelReale\mdh\pgsql;

use RangelReale\mdh\BaseConverter;
use RangelReale\mdh\BaseDataHandler;
use RangelReale\mdh\Util;

/**
 * Class PgsqlConverter
 */
class PgsqlConverter extends BaseConverter
{
    public function init()
    {
        parent::init();
        $this->setHandlers([
            'date' => ['class' => 'RangelReale\mdh\pgsql\PgsqlDataHandler_Datetime', 'type' => 'date'],
            'time' => ['class' => 'RangelReale\mdh\pgsql\PgsqlDataHandler_Datetime', 'type' => 'time'],
            'datetime' => ['class' => 'RangelReale\mdh\pgsql\PgsqlDataHandler_Datetime', 'type' => 'datetime'],
        ]);
    }
}

class PgsqlDataHandler_Datetime extends BaseDataHandler
{
    public $type;
    
    private $_type_format = [
        'date' => 'Y-m-d',
        'time' => 'H:i:s',
        'datetime' => 'Y-m-d H:i:s.uP',
    ];
    
    public function parse($value, $options)
    {
        if ($value === null || $value == '') {
            return null;
        }
        $ret = \DateTime::createFromFormat($this->_type_format[$this->type], $value);
        if ($ret === false) {
            $this->mdh()->throwDataConversionException($this->type, 'parse', $value, $options);
        }
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
        return $value->format($this->_type_format[$this->type]);
    }
}