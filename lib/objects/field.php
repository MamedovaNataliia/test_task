<?php

namespace Parser\Objects;

use Parser\Interfaces\IObject;

class Field implements IObject
{
    /**
     * @var string
     */
    protected $key;
    /**
     * @var string
     */
    protected $value;

    /**
     * Field constructor.
     * @param string $key
     * @param string $value
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = (int)$value;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param int|string $value
     */
    public function setKey($value)
    {
        $this->key = $value;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int|string $value
     */
    public function setValue($value)
    {
        $this->value = (int)$value;
    }
}