<?php

namespace Palshin\PswCrack\Service;

class WriteFileService
{
    private $resource;
    private $filePath;

    public function __construct($path, $mode)
    {
        $this->filePath = $path;
        $this->open($path, $mode);
    }

    public function open($path, $mode)
    {
        $this->resource = fopen($path, $mode);
    }
    public function writeLine($message, $length = null)
    {
        if ($length !== null) {
            $length++;
        }

        $this->write($message.PHP_EOL, $length);
    }

    public function write($message, $length = null)
    {
        if (null === $length) {
            $length = strlen($message);
        }

        fwrite($this->resource, $message, $length);
    }

    public function readLine($length = null)
    {
        if (null === $length) {
            $line = fgets($this->resource);
        } else {
            $line = fgets($this->resource, $length);
        }

        if (false === $line) {
            return '';
        }

        return rtrim($line, PHP_EOL);
    }

    public function reset()
    {
        $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        fseek($this->resource, $offset, $whence);
    }

}