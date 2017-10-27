<?php

namespace RangelReale\mdh;

use RangelReale\mdh\base\Object;
use RangelReale\mdh\base\ObjectUtil;

/**
 * Class BaseMDH
 */
abstract class BaseMDH extends Object
{
    public $dateFormat = IDataHandler::FORMAT_SHORT;
    public $timeFormat = IDataHandler::FORMAT_SHORT;
    public $dateTimeFormat = IDataHandler::FORMAT_SHORT;
    
    private $_converters = [];
    private $_convertersdef = [
        'default' => 'RangelReale\mdh\def\DefaultConverter',
        'user' => 'RangelReale\mdh\user\UserConverter',
    ];
    private $_dataconversionmessagedef = 'RangelReale\mdh\DataConversionMessage';
    private $_dataconversionmessage;

    private $_datatypealiases = [
        'decimalfull' => ['decimal'],
    ];
    
    public abstract function getLocale();
    
    public abstract function setLocale($value);
    
    /**
     * Convert the value to PHP format using the converter
     * 
     * @param string $converter converter name. If null, return $value unparsed. If '', use 'default' converter
     * @param string $datatype data type
     * @param mixed $value value
     * @param array $options options
     * @return mixed parsed value in PHP format
     * @throws InvalidConverterException
     */
    public function parse($converter, $datatype, $value, $options = [])
    {
        if ($converter === null) {
            return $value;
        }
        if ($converter == '')  {
            $converter = 'default';
        }
        $options = array_merge($options, ['__converter'=>$converter]);
        
        $converters = [$converter];
        if ($converter != 'default') {
            $converters[] = 'default';
        }
        
        foreach ($converters as $conv) {
            foreach ($this->getDataTypeAliasesFor($datatype) as $curdatatype) {
                if ($this->getConverter($conv)->canConvert($curdatatype)) {
                    return $this->getConverter($conv)->parse($curdatatype, $value, $options, $this);
                }
            }
        }
        $this->throwDataConversionException($datatype, 'parse', $value, $options);
    }
    
    /**
     * Convert the value from PHP format using the converter
     * 
     * @param string $converter converter name. If null, return $value unparsed. If '', use 'default' converter
     * @param string $datatype data type
     * @param mixed $value value
     * @param array $options options
     * @return mixed formatted value
     * @throws InvalidConverterException
     */
    public function format($converter, $datatype, $value, $options = [])
    {
        if ($converter === null) {
            return $value;
        }
        if ($converter == '') {
            $converter = 'default';
        }
        $options = array_merge($options, ['__converter'=>$converter]);
        
        $converters = [$converter];
        if ($converter != 'default') {
            $converters[] = 'default';
        }
        
        foreach ($converters as $conv) {
            foreach ($this->getDataTypeAliasesFor($datatype) as $curdatatype) {
                if ($this->getConverter($conv)->canConvert($curdatatype)) {
                    return $this->getConverter($conv)->format($curdatatype, $value, $options, $this);
                }
            }
        }
        $this->throwDataConversionException($datatype, 'format', $value, $options);
    }
    
    /**
     * Convert the value from one converter to another
     * 
     * @param string|null $converterFrom converter name or null to not parse the value
     * @param string|null $converterTo converter name or null to not format the value
     * @param string $datatype data type
     * @param mixed $value value
     * @param array $optionsFrom options to parse
     * @param array $optionsTo options to format
     * @return mixed converted value
     */
    public function convert($converterFrom, $converterTo, $datatype, $value, $optionsFrom = [], $optionsTo = [])
    {
        if ($converterFrom !== null) {
            $value = $this->parse($converterFrom, $datatype, $value, $optionsFrom);
        }
        if ($converterTo !== null) {
            $value = $this->format($converterTo, $datatype, $value, $optionsTo);
        }
        return $value;
    }
    
    /**
     * Convert multiple fields at the same time
     * @param string $converterFrom From converter
     * @param string $converterTo To converter
     * @param \RangelReale\mdh\MultiConversion $multiConversion 
     * @param array $values
     * @return \RangelReale\mdh\MultiConversionResult
     * @throws \RangelReale\mdh\DataConversionException
     */
    public function convertMulti($converterFrom, $converterTo, $multiConversion, $values)
    {
        $ret = new MultiConversionResult();
        foreach ($values as $vname => $vvalue) {
            if (isset($multiConversion->attributes[$vname])) {
                if (is_array($multiConversion->attributes[$vname])) {
                    $dataType = isset($multiConversion->attributes[$vname]['dataType'])?$multiConversion->attributes[$vname]['dataType']:'raw';
                    $options = isset($multiConversion->attributes[$vname]['options'])?$multiConversion->attributes[$vname]['options']:[];
                    $optionsFrom = isset($multiConversion->attributes[$vname]['optionsFrom'])?$multiConversion->attributes[$vname]['optionsFrom']:$options;
                    $optionsTo = isset($multiConversion->attributes[$vname]['optionsTo'])?$multiConversion->attributes[$vname]['optionsTo']:$options;
                } else {
                    $dataType = $multiConversion->attributes[$vname];
                    $options = [];
                    $optionsFrom = [];
                    $optionsTo = [];
                }

                try {
                    $vvalue = $this->convert($converterFrom, $converterTo, $dataType, $vvalue, $optionsFrom, $optionsTo);
                } catch (DataConversionException $e) {
                    if ($multiConversion->throwErrors) {
                        throw $e;
                    }
                    $ret->hasErrors = true;
                    $ret->errors[$vname] = $e;
                    $vvalue = $e;
                }
            }
            $ret->result[$vname]=$vvalue;
        }
        return $ret;
    }

    /**
     * Convert an array of multiple fields at the same time
     * @param string $converterFrom From converter
     * @param string $converterTo To converter
     * @param \RangelReale\mdh\MultiConversion $multiConversion 
     * @param array $list
     * @return \RangelReale\mdh\MultiConversionResult
     * @throws \RangelReale\mdh\DataConversionException
     */
    public function convertMultiList($converterFrom, $converterTo, $multiConversion, $list)
    {
        $ret = new MultiConversionResult();
        foreach ($list as $listindex => $listitem) {
            $r = $this->convertMulti($converterFrom, $converterTo, $multiConversion, $listitem);
            if ($r->hasErrors) {
                $ret->hasErrors = true;
                $ret->errors[$listindex] = $r->errors;
            }
            $ret->result[] = $r->result;
        }
        return $ret;
    }
    
    /**
     * Gets a converter
     * 
     * @param string $name converter name
     * @return IConverter converter
     */
    public function getConverter($id, $throwException = true)
    {
        if (isset($this->_converters[$id])) {
            return $this->_converters[$id];
        }

        if (isset($this->_convertersdef[$id])) {
            $definition = $this->_convertersdef[$id];
            if (is_object($definition) && !$definition instanceof Closure) {
                return $this->_converters[$id] = $definition;
            } else {
                return $this->_converters[$id] = ObjectUtil::createObject($definition, [$this]);
            }
        } elseif ($throwException) {
            throw new InvalidConverterException($id);
        } else {
            return null;
        }            
    }
    
    /**
     * Sets a converter
     * 
     * @param string $name converter name
     * @param array|IConverter $converter converter
     */
    public function setConverter($id, $definition)
    {
        if ($definition === null) {
            unset($this->_converters[$id], $this->_convertersdef[$id]);
            return;
        }

        unset($this->_converters[$id]);

        if (is_object($definition) || is_callable($definition, true)) {
            // an object, a class name, or a PHP callable
            $this->_convertersdef[$id] = $definition;
        } elseif (is_array($definition)) {
            if (isset($this->_convertersdef[$id])) {
                if (is_array($this->_convertersdef[$id])) {
                    $definition = array_merge($this->_convertersdef[$id], $definition);
                } elseif (is_string($this->_convertersdef[$id])) {
                    $definition = array_merge(['class'=>$this->_convertersdef[$id]], $definition);
                }
            }
            
            // a configuration array
            if (isset($definition['class'])) {
                $this->_convertersdef[$id] = $definition;
            } else {
                throw new MDHException("The configuration for the \"$id\" converter must contain a \"class\" element.");
            }
        } else {
            throw new MDHException("Unexpected configuration type for the \"$id\" converter: " . gettype($definition));
        }
        
    }

    public function getConverters($returnDefinitions = true)
    {
        return $returnDefinitions ? $this->_convertersdef : $this->_converters;
    }
    
    public function setConverters($converters)
    {
        foreach ($converters as $id => $converter) {
            $this->setConverter($id, $converter);
        }
    }
    
    /**
     * Adds a data type alias
     * 
     * @param string $datatype From datatype
     * @param string $alias To datatype
     */
    public function setDataTypeAlias($datatype, $alias)
    {
        if (!isset($this->_datatypealiases[$datatype])) {
            $this->_datatypealiases[$datatype] = [];
        }
        $this->_datatypealiases[$datatype][]=$alias;
    }
    
    public function setDataTypeAliases($values)
    {
        foreach ($values as $dt => $alias) {
            $this->setDataTypeAlias($dt, $alias);
        }
    }
       
    public function getDataConversionMessage()
    {
        if (isset($this->_dataconversionmessage)) {
            return $this->_dataconversionmessage;
        }

        if (isset($this->_dataconversionmessagedef)) {
            $definition = $this->_dataconversionmessagedef;
            if (is_object($definition) && !$definition instanceof Closure) {
                return $this->_dataconversionmessage = $definition;
            } else {
                return $this->_dataconversionmessage = ObjectUtil::createObject($definition);
            }
        } else {
            throw new MDHException('Data conversion message not set');
        }            
    }
    
    /**
     * @param DataConversionMessage $dataconversionmessage
     */
    public function setDataConversionMessage($dataconversionmessage)
    {
        if ($dataconversionmessage === null) {
            unset($this->_dataconversionmessage, $this->_dataconversionmessagedef);
            return;
        }

        unset($this->_dataconversionmessage);

        if (is_object($dataconversionmessage) || is_callable($dataconversionmessage, true)) {
            // an object, a class name, or a PHP callable
            $this->_dataconversionmessagedef = $dataconversionmessage;
        } elseif (is_array($dataconversionmessage)) {
            // a configuration array
            if (isset($dataconversionmessage['class'])) {
                $this->_dataconversionmessagedef = $dataconversionmessage;
            } else {
                throw new MDHException("The configuration for the \"$id\" data conversion message must contain a \"class\" element.");
            }
        } else {
            throw new MDHException("Unexpected configuration type for the \"$id\" datqa conversion message: " . gettype($dataconversionmessage));
        }
    }
    
    /**
     * Throws a data conversion exception
     */
    public function throwDataConversionException($datatype, $parseOrFormat, $value, $options, $extra = '')
    {
        throw new DataConversionException($this->getDataConversionMessage()->getMessage($datatype, $parseOrFormat, $value, $options, $extra),
            $datatype, $parseOrFormat, $value, $options, $extra);
    }
    
    /**
     * Get converter handler as property
     * 
     * @param string $name converter name
     * @return BaseMDHConverter converter handler
     */
    public function __get($name)
    {
        if (isset($this->_convertersdef[$name])) {
            return $this->getConverter($name);
        }
        return parent::__get($name);
    }
    
    private function getDataTypeAliasesFor($datatype)
    {
        $ret = [$datatype];
        if (isset($this->_datatypealiases[$datatype])) {
            $ret = array_merge($ret, $this->_datatypealiases[$datatype]);
        }
        return $ret;
    }
}
