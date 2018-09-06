<?php
namespace Parser\Interfaces;

interface IWriter
{
    public function insertRecord(array $record):int;
}