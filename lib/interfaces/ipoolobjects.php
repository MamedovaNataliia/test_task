<?php

namespace Parser\Interfaces;

interface IPoolObjects
{
    public static function push(IObject $object);

    public static function get($id);

    public static function remove($id);
}