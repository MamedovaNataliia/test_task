<?php

namespace Parser\Objects;

use SeekableIterator;
use SplFileObject;
use TypeError;
use Exception;
use function fopen;
use function stream_get_meta_data;
use function is_resource;


class Stream implements SeekableIterator
{
    /**
     * @var array
     */
    protected $filters = [];
    /**
     * stream resource
     * @var resource
     */
    protected $stream;
    /**
     * current iterator value
     * @var mixed
     */
    protected $value;
    /**
     * @var bool
     */
    protected $should_close_stream = false;
    /**
     * current iterator key
     * @var int
     */
    protected $offset;
    /**
     * Flags for the Document
     *
     * @var int
     */
    protected $flags = 0;
    /**
     * the field delimiter (one character only)
     *
     * @var string
     */
    protected $delimiter = ',';
    /**
     * the field enclosure character (one character only)
     *
     * @var string
     */
    protected $enclosure = '"';
    /**
     * the field escape character (one character only)
     *
     * @var string
     */
    protected $escape = '\\';
    /**
     * Tell whether the current stream is seekable
     *
     * @var bool
     */
    protected $is_seekable = false;

    /**
     * Stream constructor.
     * @param resource $resource stream type resource
     */
    public function __construct($path, $mode = 'r')
    {
        $resource = fopen($path, $mode);
        if (!is_resource($resource)) {
            throw new TypeError(sprintf('Argument passed must be a stream resource, %s given', gettype($resource)));
        }
        if ('stream' !== ($type = get_resource_type($resource))) {
            throw new TypeError(sprintf('Argument passed must be a stream resource, %s resource given', $type));
        }

        $this->is_seekable = stream_get_meta_data($resource)['seekable'];
        $this->stream = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        if ($this->should_close_stream && is_resource($this->stream)) {
            fclose($this->stream);
        }
        unset($this->stream);
    }

    /**
     * @param bool $close_stream
     */
    public function setCloseStream($close_stream = true)
    {
        $this->should_close_stream = $close_stream;
    }
    /**
     * @param SplFileObject $flags
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;

    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return stream_get_meta_data($this->stream) + [
                'delimiter'      => $this->delimiter,
                'enclosure'      => $this->enclosure,
                'escape'         => $this->escape,
                'stream_filters' => array_keys($this->filters),
            ];
    }

    /**
     * Return a new instance from file path
     * @param string $path
     * @param string $open_mode
     * @param null $context
     * @return Stream
     * @throws \Exception
     */
    public static function createFromPath(string $path, string $open_mode = 'r', $context = null)
    {
        $args = [$path, $open_mode];
        if (null !== $context) {
            $args[] = false;
            $args[] = $context;
        }
        if (!$resource = @fopen(...$args)) {
            throw new Exception(sprintf('`%s`: failed to open stream: No such file or directory', $path));
        }
        $instance = new static($resource);
        $instance->should_close_stream = true;
        return $instance;
    }

    /**
     * Return a new instance from string
     * @param string $content
     * @return Stream
     */
    public static function createFromArgs(string $content)
    {
        $resource = fopen('php://stdin', 'r+');
        fwrite($resource, $content);
        $instance = new static($resource);
        $instance->should_close_stream = true;
        return $instance;
    }

    /**
     * @see http://php.net/manual/en/splfileobject.setcsvcontrol.php
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     */
    public function setCsvControl(string $delimiter = ',', string $enclosure = '"', string $escape = '\\')
    {
        list($this->delimiter, $this->enclosure, $this->escape) = $this->filterControl($delimiter, $enclosure, $escape,
            __METHOD__);
    }

    /***
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param string $caller
     * @return array
     * @throws Exception
     */
    public function filterControl(string $delimiter, string $enclosure, string $escape, string $caller): array
    {
        $controls = ['delimiter' => $delimiter, 'enclosure' => $enclosure, 'escape' => $escape];
        foreach ($controls as $type => $control) {
            if (1 !== strlen($control)) {
                throw new Exception(sprintf('%s() expects %s to be a single character', $caller, $type));
            }
        }
        return array_values($controls);
    }

    /**
     * @see http://php.net/manual/en/splfileobject.getcsvcontrol.php
     * @return array
     */
    public function getCsvControl()
    {
        return [$this->delimiter, $this->enclosure, $this->escape];
    }

    /***
     * @param array $fields
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @return bool|int
     * @throws Exception
     */
    public function fputcsv(array $fields, string $delimiter = ',', string $enclosure = '"', string $escape = '\\')
    {
        $controls = $this->filterControl($delimiter, $enclosure, $escape, __METHOD__);
        return fputcsv($this->stream, $fields, ...$controls);
    }

    /**
     * Get line number
     * @return int
     */
    public function key()
    {
        return $this->offset;
    }

    /**
     *  Read next line
     */
    public function next()
    {
        $this->value = false;
        $this->offset++;
    }

    /**
     * Rewind the file to the first line
     * @throws Exception
     */
    public function rewind()
    {
        if (!$this->is_seekable) {
            throw new Exception('stream does not support seeking');
        }
        rewind($this->stream);
        $this->offset = 0;
        $this->value = false;
        if ($this->flags & SplFileObject::READ_AHEAD) {
            $this->current();
        }
    }

    /**
     * @see http://php.net/manual/en/splfileobject.valid.php
     * @return bool
     */
    public function valid()
    {
        if ($this->flags & SplFileObject::READ_AHEAD) {
            return $this->current() !== false;
        }
        return !feof($this->stream);
    }

    /**
     * Retrieves the current line of the file
     * @return array|bool
     */
    public function current()
    {
        if (false !== $this->value) {
            return $this->value;
        }
        $this->value = $this->getCurrentRecord();
        return $this->value;
    }

    /**
     * Retrievies the current line of a CSV Record
     * @return array|bool
     */
    protected function getCurrentRecord()
    {
        do {
            $ret = fgetcsv($this->stream, 0, $this->delimiter, $this->enclosure, $this->escape);
        } while ($this->flags & SplFileObject::SKIP_EMPTY && $ret !== false && $ret[0] === null);
        return $ret;
    }

    /**
     * @param int $position
     * @throws Exception
     */
    public function seek($position)
    {
        if ($position < 0) {
            throw new Exception(sprintf('%s() can\'t seek stream to negative line %d', __METHOD__, $position));
        }
        $this->rewind();
        while ($this->key() !== $position && $this->valid()) {
            $this->current();
            $this->next();
        }
        $this->offset--;
        $this->current();
    }

    /**
     * @return int
     */
    public function fpassthru()
    {
        return fpassthru($this->stream);
    }

    /**
     * @param $length
     * @return bool|string
     */
    public function fread($length)
    {
        return fread($this->stream, $length);
    }

    /**
     * @throws Exception if the stream resource is not seekable
     * @return int
     */
    public function fseek(int $offset, int $whence = SEEK_SET)
    {
        if (!$this->is_seekable) {
            throw new Exception('stream does not support seeking');
        }
        return fseek($this->stream, $offset, $whence);
    }

    /**
     * Write to stream.
     *
     * @see http://php.net/manual/en/splfileobject.fwrite.php
     *
     * @return int|bool
     */
    public function fwrite(string $str, int $length = 0)
    {
        return fwrite($this->stream, $str, $length);
    }

    /**
     * Flushes the output to a file.
     *
     * @see http://php.net/manual/en/splfileobject.fwrite.php
     *
     * @return bool
     */
    public function fflush()
    {
        return fflush($this->stream);
    }

    private function __clone()
    {

    }
}