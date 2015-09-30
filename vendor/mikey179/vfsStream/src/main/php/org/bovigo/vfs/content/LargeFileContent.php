<?php
/**
 * This file is part of vfsStream.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\vfs
 */
namespace org\bovigo\vfs\content;
/**
 * File content implementation to mock large files.
 *
 * When content is written via write() the data will be written into the
 * positions according to the current offset.
 * When content is read via read() it will use the already written data. If no
 * data is written at the offsets to read those offsets will be filled with
 * spaces.
 * Please note that accessing the whole content via content() will deliver a
 * string with the length of the originally defined size. It is not advisable to
 * do so with large sizes, except you have enough memory and time. :-)
 *
 * @since  1.3.0
 */
class LargeFileContent extends SeekableFileContent implements FileContent
{
    /**
     * byte array of written content
     *
     * @type  char[]
     */
    private $content = array();
    /**
     * file size in bytes
     *
     * @type  int
     */
    private $size;

    /**
     * constructor
     *
     * @param  int  $size  file size in bytes
     */
    public function __construct($size)
    {
        $this->size = $size;
    }

    /**
     * create large file with given size in kilobyte
     *
     * @param   int  $kilobyte
     * @return  LargeFileContent
     */
    public static function withKilobytes($kilobyte)
    {
        return new self($kilobyte * 1024);
    }

    /**
     * create large file with given size in megabyte
     *
     * @param   int  $megabyte
     * @return  LargeFileContent
     */
    public static function withMegabytes($megabyte)
    {
        return self::withKilobytes($megabyte * 1024);
    }

    /**
     * create large file with given size in gigabyte
     *
     * @param   int  $gigabyte
     * @return  LargeFileContent
     */
    public static function withGigabytes($gigabyte)
    {
        return self::withMegabytes($gigabyte * 1024);
    }

    /**
     * returns actual content
     *
     * @return  string
     */
    public function content()
    {
        return $this->doRead(0, $this->size);
    }

    /**
     * returns size of content
     *
     * @return  int
     */
    public function size()
    {
        return $this->size;
    }

    /**
     * actual reading of given byte count starting at given offset
     *
     * @param  int  $offset
     * @param  int  $count
     */
    protected function doRead($offset, $count)
    {
        if (($offset + $count) > $this->size) {
            $count = $this->size - $offset;
        }

        $result = '';
        for ($i = 0; $i < $count; $i++) {
            if (isset($this->content[$i + $offset])) {
                $result .= $this->content[$i + $offset];
            } else {
                $result .= ' ';
            }
        }

        return $result;
    }

    /**
     * actual writing of data with specified length at given offset
     *
     * @param   string  $data
     * @param   int     $offset
     * @param   int     $length
     */
    protected function doWrite($data, $offset, $length)
    {
        for ($i = 0; $i < $length; $i++) {
            $this->content[$i + $offset] = $data{$i};
        }

        if ($offset >= $this->size) {
            $this->size += $length;
        } elseif (($offset + $length) > $this->size) {
            $this->size = $offset + $length;
        }
    }

    /**
     * Truncates a file to a given length
     *
     * @param   int  $size length to truncate file to
     * @return  bool
     */
    public function truncate($size)
    {
        $this->size = $size;
        foreach (array_filter(array_keys($this->content),
                              function($pos) use ($size)
                              {
                                  return $pos >= $size;
                              }
                ) as $removePos) {
            unset($this->content[$removePos]);
        }

        return true;
    }
}
