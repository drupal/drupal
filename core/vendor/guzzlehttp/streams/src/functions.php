<?php

namespace GuzzleHttp\Stream;

/**
 * Create a new stream based on the input type
 *
 * @param resource|string|StreamInterface $resource Entity body data
 * @param int                             $size     Size of the data contained in the resource
 *
 * @return StreamInterface
 * @throws \InvalidArgumentException if the $resource arg is not valid.
 */
function create($resource = '', $size = null)
{
    $type = gettype($resource);

    if ($type == 'string') {
        $stream = fopen('php://temp', 'r+');
        if ($resource !== '') {
            fwrite($stream, $resource);
            fseek($stream, 0);
        }
        return new Stream($stream);
    }

    if ($type == 'resource') {
        return new Stream($resource, $size);
    }

    if ($resource instanceof StreamInterface) {
        return $resource;
    }

    if ($type == 'object' && method_exists($resource, '__toString')) {
        return create((string) $resource, $size);
    }

    throw new \InvalidArgumentException('Invalid resource type: ' . $type);
}

/**
 * Copy the contents of a stream into a string until the given number of bytes
 * have been read.
 *
 * @param StreamInterface $stream Stream to read
 * @param int             $maxLen Maximum number of bytes to read. Pass -1 to
 *                                read the entire stream.
 * @return string
 */
function copy_to_string(StreamInterface $stream, $maxLen = -1)
{
    $buffer = '';

    if ($maxLen === -1) {
        while (!$stream->eof()) {
            $buf = $stream->read(1048576);
            if ($buf === '' || $buf === false) {
                break;
            }
            $buffer .= $buf;
        }
    } else {
        $len = 0;
        while (!$stream->eof() && $len < $maxLen) {
            $buf = $stream->read($maxLen - $len);
            if ($buf === '' || $buf === false) {
                break;
            }
            $buffer .= $buf;
            $len = strlen($buffer);
        }
    }

    return $buffer;
}

/**
 * Copy the contents of a stream into another stream until the given number of
 * bytes have been read.
 *
 * @param StreamInterface $source Stream to read from
 * @param StreamInterface $dest   Stream to write to
 * @param int             $maxLen Maximum number of bytes to read. Pass -1 to
 *                                read the entire stream.
 */
function copy_to_stream(
    StreamInterface $source,
    StreamInterface $dest,
    $maxLen = -1
) {
    if ($maxLen === -1) {
        while (!$source->eof()) {
            if (!$dest->write($source->read(1048576))) {
                break;
            }
        }
        return;
    }

    $bytes = 0;
    while (!$source->eof()) {
        $buf = $source->read($maxLen - $bytes);
        if (!($len = strlen($buf))) {
            break;
        }
        $bytes += $len;
        $dest->write($buf);
        if ($bytes == $maxLen) {
            break;
        }
    }
}

/**
 * Calculate a hash of a Stream
 *
 * @param StreamInterface $stream    Stream to calculate the hash for
 * @param string          $algo      Hash algorithm (e.g. md5, crc32, etc)
 * @param bool            $rawOutput Whether or not to use raw output
 *
 * @return bool|string Returns false on failure or a hash string on success
 */
function hash(
    StreamInterface $stream,
    $algo,
    $rawOutput = false
) {
    $pos = $stream->tell();
    if (!$stream->seek(0)) {
        return false;
    }

    $ctx = hash_init($algo);
    while ($data = $stream->read(1048576)) {
        hash_update($ctx, $data);
    }

    $out = hash_final($ctx, (bool) $rawOutput);
    $stream->seek($pos);

    return $out;
}

/**
 * Read a line from the stream up to the maximum allowed buffer length
 *
 * @param StreamInterface $stream    Stream to read from
 * @param int             $maxLength Maximum buffer length
 *
 * @return string|bool
 */
function read_line(StreamInterface $stream, $maxLength = null)
{
    $buffer = '';
    $size = 0;

    while (!$stream->eof()) {
        if (false === ($byte = $stream->read(1))) {
            return $buffer;
        }
        $buffer .= $byte;
        // Break when a new line is found or the max length - 1 is reached
        if ($byte == PHP_EOL || ++$size == $maxLength - 1) {
            break;
        }
    }

    return $buffer;
}
