<?php

namespace Parser\Objects;

use Parser\Interfaces\IObject;
use Parser\Interfaces\IPoolObjects;

class PoolFields implements IPoolObjects
{
    /**
     * @var array
     */
    protected static $fields = [];
    /**
     * @var bool
     */
    protected static $bDublicate = false;

    /**
     * @param IObject $field
     */
    public static function push(IObject $field)
    {
        self::$bDublicate = false;

        $key = $field->getKey();
        $value = $field->getValue();

        if (isset(self::$fields[$key])) {
            if ($value < self::$fields[$key]) {
                self::$fields[$key] = $value;
            }
            self::$bDublicate = true;
        } else {
            self::$fields[$key] = $value;
        }
    }

    /**
     * @return bool
     */
    public static function isDublicate()
    {
        return self::$bDublicate;
    }

    /**
     * @param integer|string $key
     * @return Field|null
     */
    public static function get($key)
    {
        return isset(self::$fields[$key]) ? self::$fields[$key] : null;
    }

    /**
     * @param integer|string $key
     */
    public static function remove($key)
    {
        if (array_key_exists($key, self::$fields)) {
            unset(self::$fields[$key]);
        }
    }
}