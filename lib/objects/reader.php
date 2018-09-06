<?php

namespace Parser\Objects;

use Exception;
use SplFileObject;
use Parser\Interfaces\IReader;
use SeekableIterator;

class Reader implements IReader
{
    /**
     * @var int
     */
    protected $header_offset = 0;
    /**
     * @var array
     */
    protected $header = [];
    /**
     * @var int
     */
    protected $nb_records = -1;
    /**
     * @var string
     */
    protected $enclosure = '"';
    /**
     * @var Stream object
     */
    protected $document;
    /**
     * @var string
     */
    protected $delimiter = ',';
    /**
     * @var string
     */
    protected $escape = "\\";
    /**
     * @var array
     */
    protected $need_header = [];

    public function __construct(SeekableIterator $document, array $need_header = [])
    {
        $this->document = $document;
        list($this->delimiter, $this->enclosure, $this->escape) = $this->document->getCsvControl();
        $this->need_header = $need_header;
        $this->setHeader(0);
    }

    /**
     * @return int|null
     */
    public function getHeaderOffset()
    {
        return $this->header_offset;
    }

    /**
     * @return array
     */
    public function getHeader(): array
    {
        if (null !== $this->header_offset) {
            return $this->header;
        }
        if ([] !== $this->header) {
            return $this->header;
        }
        $this->setHeader($this->header_offset);
        return $this->header;
    }

    /**
     * @param int $offset
     * @return array
     * @throws Exception
     */
    public function setHeader(int $offset): array
    {
        $header = $this->seekRow($offset);
        if (false !== $header || [] !== $header) {
            if ($this->need_header !== []) {
                foreach ($header as $key => $item) {
                    if (in_array($item, $this->need_header)) {
                        $this->header[$key] = $item;
                    }
                }
            }
            return $this->header;
        }
    }

    /**
     * @param int $offset
     * @return array|bool
     * @throws Exception
     */
    public function seekRow(int $offset)
    {
        $this->document->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);
        $this->document->setCsvControl($this->delimiter, $this->enclosure, $this->escape);
        $this->document->rewind();

        $this->document->seek($offset);
        return $this->document->current();
    }

    /**
     * @param $offset
     * @return array|false
     * @throws Exception
     */
    public function getRecord($offset)
    {
        $arRecord = false;
        if ($offset <= 0) {
            $offset = 1;
        }
        if ($this->header === [] && $this->header === null) {
            $this->setHeader(0);
        } else {
            $this->document->seek($offset);
            if ($this->document->valid()) {
                $arRecord = (array)$this->document->current();
                if ($arRecord) {
                    $arRecord = $this->combaneRecord($arRecord);
                }
                $this->document->next();
            }
        }
        return $arRecord;
    }

    /**
     * @param array $arItem
     * @return array
     */
    private function combaneRecord($arItem)
    {
        $arTemp = $arItem;
        $arRecord = [];
        foreach ($arTemp as $key => $value) {
            if (isset($this->header[$key])) {
                $arRecord[$this->header[$key]] = $value;
            }
        }
        return $arRecord;
    }
}