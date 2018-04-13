<?php

namespace RangelReale\mdh;

use yii\base\BaseObject;

class ConverterProxy extends BaseObject implements IConverter
{
    /**
     * @var BaseMDH
     */
    private $_mdh;
    /**
     * @var string
     */
    private $_converterId;
    /**
     * @var IConverter
     */
    private $_converter;

    /**
     * ConverterProxy constructor.
     * @param $mdh BaseMDH
     * @param $converterId string
     * @param array $config
     */
    public function __construct($mdh, $converterId, $config = [])
    {
        $this->_mdh = $mdh;
        $this->_converterId = $converterId;
        $this->_converter = $mdh->getConverter($converterId);
        parent::__construct($config);
    }

    // convert the value from handler format to PHP format
    public function parse($datatype, $value, $options = [])
    {
        return $this->_mdh->parse($this->_converterId, $datatype, $value, $options);
    }

    // convert the value from php format to handler format
    public function format($datatype, $value, $options = [])
    {
        return $this->_mdh->format($this->_converterId, $datatype, $value, $options);
    }

    // check if it is possible to convert this data type
    public function canConvert($datatype)
    {
        return $this->_converter->canConvert($datatype);
    }

    // returns the handler for the data type
    public function getHandler($datatype)
    {
        return $this->_converter->getHandler($datatype);
    }

    // add a handler for a data type
    public function setHandler($datatype, $handler)
    {
        $this->_converter->setHandler($datatype, $handler);
    }
}
