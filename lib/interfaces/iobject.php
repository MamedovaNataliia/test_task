<?php

namespace Parser\Interfaces;

interface IObject
{
    public function getKey();

    public function getValue();

    public function setKey($value);

    public function setValue($value);

}