<?php

namespace Parser\Objects;

use Parser\Interfaces\IReader;
use Parser\Interfaces\IWriter;


class Execute
{
    /**
     * @var IWriter
     */
    protected $obWriter;
    /**
     * @var IReader
     */
    protected $obReader;

    /**
     * Execute constructor.
     * @param IWriter $obWriter
     * @param IReader $obReader
     */
    public function __construct(IWriter $obWriter, IReader $obReader)
    {
        $this->obWriter = $obWriter;
        $this->obReader = $obReader;
    }

    /**
     * @param array $arResult
     * @return bool
     */
    public function searchDublicate(&$arResult)
    {
        if ($arResult === null) {
            return false;
        }
        $arSearch = [
            'EMAIL',
            'CARD',
            'PHONE'
        ];
        $parent_id = (int)$arResult['PARENT_ID'];
        $id = (int)$arResult['ID'];
        foreach ($arResult as $key => $value) {
            if (in_array($key, $arSearch)) {
                $obField = new Field($value, $id);
                PoolFields::push($obField);

                if (PoolFields::isDublicate()) {

                    if ($new_parent_id = PoolFields::get($value) !== null) {
                        $parent_id = ($parent_id == 0) ? $new_parent_id : $parent_id;
                        $parent_id = ($parent_id > $new_parent_id) ? $new_parent_id : $parent_id;
                    }
                }
            }
        }
        $arResult['PARENT_ID'] = $parent_id;
    }

    public function run()
    {
        $obReader = $this->obReader;

        $offset = 0;

        $obWriter = $this->obWriter;

        while ($arRecord = $obReader->getRecord($offset) !== false) {
            $arRecord = $obReader->getRecord($offset);
            $this->searchDublicate($arRecord);
            $obWriter->insertRecord($arRecord);
            $offset++;
        }
    }
}

