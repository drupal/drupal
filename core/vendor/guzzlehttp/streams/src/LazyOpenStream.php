<?php
namespace GuzzleHttp\Stream;

/**
 * Lazily reads or writes to a file that is opened only after an IO operation
 * take place on the stream.
 */
class LazyOpenStream implements StreamInterface, MetadataStreamInterface
{
    /** @var string File to open */
    private $filename;

    /** @var string $mode */
    private $mode;

    /** @var MetadataStreamInterface */
    private $stream;

    /**
     * @param string $filename File to lazily open
     * @param string $mode     fopen mode to use when opening the stream
     */
    public function __construct($filename, $mode)
    {
        $this->filename = $filename;
        $this->mode = $mode;
    }

    public function __toString()
    {
        try {
            return (string) $this->getStream();
        } catch (\Exception $e) {
            return '';
        }
    }

    private function getStream()
    {
        if (!$this->stream) {
            $this->stream = create(safe_open($this->filename, $this->mode));
        }

        return $this->stream;
    }

    public function getContents($maxLength = -1)
    {
        return copy_to_string($this->getStream(), $maxLength);
    }

    public function close()
    {
        if ($this->stream) {
            $this->stream->close();
        }
    }

    public function detach()
    {
        $stream = $this->getStream();
        $result = $stream->detach();
        $this->close();

        return $result;
    }

    public function tell()
    {
        return $this->stream ? $this->stream->tell() : 0;
    }

    public function getSize()
    {
        return $this->getStream()->getSize();
    }

    public function eof()
    {
        return $this->getStream()->eof();
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        return $this->getStream()->seek($offset, $whence);
    }

    public function read($length)
    {
        return $this->getStream()->read($length);
    }

    public function isReadable()
    {
        return $this->getStream()->isReadable();
    }

    public function isWritable()
    {
        return $this->getStream()->isWritable();
    }

    public function isSeekable()
    {
        return $this->getStream()->isSeekable();
    }

    public function write($string)
    {
        return $this->getStream()->write($string);
    }

    public function getMetadata($key = null)
    {
        return $this->getStream()->getMetadata($key);
    }
}
