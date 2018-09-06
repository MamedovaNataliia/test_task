<?php

namespace Parser\Interfaces;

interface IReader
{
    public function getRecord($offset);
}