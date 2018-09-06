<?php
namespace Parser\Objects;
use Parser\Interfaces\IWriter;
use SeekableIterator;
class Writer implements IWriter
{
    /**
     * @var string
     */
    protected $newline = "\n";
    /**
     * @var SeekableIterator
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
     * @var string
     */
    protected $enclosure = '"';
    /**
     * Buffer flush threshold.
     *
     * @var int|null
     */
    protected $flush_threshold;
    /**
     * Insert records count for flushing.
     *
     * @var int
     */
    protected $flush_counter = 0;

    /**
     * Writer constructor.
     * @param SeekableIterator $document
     * @param array $need_header
     */
    public function __construct(SeekableIterator $document)
    {
        $this->document = $document;
        list($this->delimiter, $this->enclosure, $this->escape) = $this->document->getCsvControl();
    }

    /**
     * @param array $record
     * @return int
     */
    public function insertRecord(array $record): int
    {
        $bytes = $this->document->fputcsv($record, $this->delimiter, $this->enclosure, $this->escape);
        if (false !== $bytes && 0 !== $bytes) {
            return $bytes;
        }
    }
    /**
     * Apply post insertion actions.
     */
    protected function consolidate(): int
    {
        $bytes = 0;
        if ("\n" !== $this->newline) {
            $this->document->fseek(-1, SEEK_CUR);
            $bytes = $this->document->fwrite($this->newline, strlen($this->newline)) - 1;
        }
        if (null === $this->flush_threshold) {
            return $bytes;
        }
        ++$this->flush_counter;
        if (0 === $this->flush_counter % $this->flush_threshold) {
            $this->flush_counter = 0;
            $this->document->fflush();
        }
        return $bytes;
    }

}