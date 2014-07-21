<?php
/**
 * This file is part of vfsStream.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\vfs
 */
namespace org\bovigo\vfs;
/**
 * File container.
 *
 * @api
 */
class vfsStreamFile extends vfsStreamAbstractContent
{
    /**
     * the real content of the file
     *
     * @type  string
     */
    protected $content;
    /**
     * amount of read bytes
     *
     * @type  int
     */
    protected $bytes_read = 0;
    /**
     * Resource id which exclusively locked this file
     *
     * @type  string
     */
    protected $exclusiveLock;
    /**
     * Resources ids which currently holds shared lock to this file
     *
     * @type  bool[string]
     */
    protected $sharedLock = array();

    /**
     * constructor
     *
     * @param  string  $name
     * @param  int     $permissions  optional
     */
    public function __construct($name, $permissions = null)
    {
        $this->type = vfsStreamContent::TYPE_FILE;
        parent::__construct($name, $permissions);
    }

    /**
     * returns default permissions for concrete implementation
     *
     * @return  int
     * @since   0.8.0
     */
    protected function getDefaultPermissions()
    {
        return 0666;
    }

    /**
     * checks whether the container can be applied to given name
     *
     * @param   string  $name
     * @return  bool
     */
    public function appliesTo($name)
    {
        return ($name === $this->name);
    }

    /**
     * alias for withContent()
     *
     * @param   string  $content
     * @return  vfsStreamFile
     * @see     withContent()
     */
    public function setContent($content)
    {
        return $this->withContent($content);
    }

    /**
     * sets the contents of the file
     *
     * Setting content with this method does not change the time when the file
     * was last modified.
     *
     * @param   string  $content
     * @return  vfsStreamFile
     */
    public function withContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * returns the contents of the file
     *
     * Getting content does not change the time when the file
     * was last accessed.
     *
     * @return  string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * simply open the file
     *
     * @since  0.9
     */
    public function open()
    {
        $this->seek(0, SEEK_SET);
        $this->lastAccessed = time();
    }

    /**
     * open file and set pointer to end of file
     *
     * @since  0.9
     */
    public function openForAppend()
    {
        $this->seek(0, SEEK_END);
        $this->lastAccessed = time();
    }

    /**
     * open file and truncate content
     *
     * @since  0.9
     */
    public function openWithTruncate()
    {
        $this->open();
        $this->content      = '';
        $time               = time();
        $this->lastAccessed = $time;
        $this->lastModified = $time;
    }

    /**
     * reads the given amount of bytes from content
     *
     * Using this method changes the time when the file was last accessed.
     *
     * @param   int     $count
     * @return  string
     */
    public function read($count)
    {
        $data = substr($this->content, $this->bytes_read, $count);
        $this->bytes_read  += $count;
        $this->lastAccessed = time();
        return $data;
    }

    /**
     * returns the content until its end from current offset
     *
     * Using this method changes the time when the file was last accessed.
     *
     * @return  string
     */
    public function readUntilEnd()
    {
        $this->lastAccessed = time();
        return substr($this->content, $this->bytes_read);
    }

    /**
     * writes an amount of data
     *
     * Using this method changes the time when the file was last modified.
     *
     * @param   string  $data
     * @return  amount of written bytes
     */
    public function write($data)
    {
        $dataLen            = strlen($data);
        $this->content      = substr($this->content, 0, $this->bytes_read) . $data . substr($this->content, $this->bytes_read + $dataLen);
        $this->bytes_read  += $dataLen;
        $this->lastModified = time();
        return $dataLen;
    }

    /**
     * Truncates a file to a given length
     *
     * @param   int  $size length to truncate file to
     * @return  bool
     * @since   1.1.0
     */
    public function truncate($size) {
        if ($size > $this->size()) {
            // Pad with null-chars if we're "truncating up"
            $this->setContent($this->getContent() . str_repeat("\0", $size - $this->size()));
        } else {
            $this->setContent(substr($this->getContent(), 0, $size));
        }

        $this->lastModified = time();
        return true;
    }

    /**
     * checks whether pointer is at end of file
     *
     * @return  bool
     */
    public function eof()
    {
        return $this->bytes_read >= strlen($this->content);
    }

    /**
     * returns the current position within the file
     *
     * @return  int
     */
    public function getBytesRead()
    {
        return $this->bytes_read;
    }

    /**
     * seeks to the given offset
     *
     * @param   int   $offset
     * @param   int   $whence
     * @return  bool
     */
    public function seek($offset, $whence)
    {
        switch ($whence) {
            case SEEK_CUR:
                $this->bytes_read += $offset;
                return true;

            case SEEK_END:
                $this->bytes_read = strlen($this->content) + $offset;
                return true;

            case SEEK_SET:
                $this->bytes_read = $offset;
                return true;

            default:
                return false;
        }

        return false;
    }

    /**
     * returns size of content
     *
     * @return  int
     */
    public function size()
    {
        return strlen($this->content);
    }


    /**
     * locks file for
     *
     * @param   resource|vfsStreamWrapper $resource
     * @param   int  $operation
     * @return  bool
     * @since   0.10.0
     * @see     https://github.com/mikey179/vfsStream/issues/6
     * @see     https://github.com/mikey179/vfsStream/issues/40
     */
    public function lock($resource, $operation)
    {
        if ((LOCK_NB & $operation) == LOCK_NB) {
            $operation = $operation - LOCK_NB;
        }

        // call to lock file on the same file handler firstly releases the lock
        $this->unlock($resource);

        if (LOCK_EX === $operation) {
            if ($this->isLocked()) {
                return false;
            }

            $this->setExclusiveLock($resource);
        } elseif(LOCK_SH === $operation) {
            if ($this->hasExclusiveLock()) {
                return false;
            }
            
            $this->addSharedLock($resource);
        }

        return true;
    }

    /**
     * Removes lock from file acquired by given resource
     * 
     * @param   resource|vfsStreamWrapper $resource
     * @see     https://github.com/mikey179/vfsStream/issues/40
     */
    public function unlock($resource) {
        if ($this->hasExclusiveLock($resource)) {
            $this->exclusiveLock = null;
        }
        if ($this->hasSharedLock($resource)) {
            unset($this->sharedLock[$this->getResourceId($resource)]);
        }
    }

    /**
     * Set exlusive lock on file by given resource
     *
     * @param   resource|vfsStreamWrapper $resource
     * @see     https://github.com/mikey179/vfsStream/issues/40
     */
    protected function setExclusiveLock($resource) {
        $this->exclusiveLock = $this->getResourceId($resource);
    }

    /**
     * Add shared lock on file by given resource
     *
     * @param   resource|vfsStreamWrapper $resource
     * @see     https://github.com/mikey179/vfsStream/issues/40
     */
    protected function addSharedLock($resource) {
        $this->sharedLock[$this->getResourceId($resource)] = true;
    }

    /**
     * checks whether file is locked
     *
     * @param   resource|vfsStreamWrapper $resource
     * @return  bool
     * @since   0.10.0
     * @see     https://github.com/mikey179/vfsStream/issues/6
     * @see     https://github.com/mikey179/vfsStream/issues/40
     */
    public function isLocked($resource = null)
    {
        return $this->hasSharedLock($resource) || $this->hasExclusiveLock($resource);
    }

    /**
     * checks whether file is locked in shared mode
     *
     * @param   resource|vfsStreamWrapper $resource
     * @return  bool
     * @since   0.10.0
     * @see     https://github.com/mikey179/vfsStream/issues/6
     * @see     https://github.com/mikey179/vfsStream/issues/40
     */
    public function hasSharedLock($resource = null)
    {
        if (null !== $resource) {
            return isset($this->sharedLock[$this->getResourceId($resource)]);
        }

        return !empty($this->sharedLock);
    }

    /**
     * Returns unique resource id
     *
     * @param   resource|vfsStreamWrapper $resource
     * @return  string
     * @see     https://github.com/mikey179/vfsStream/issues/40
     */
    public function getResourceId($resource) {
        if (is_resource($resource)) {
            $data = stream_get_meta_data($resource);
            $resource = $data['wrapper_data'];
        }

        return spl_object_hash($resource);
    }

    /**
     * checks whether file is locked in exclusive mode
     *
     * @param   resource|vfsStreamWrapper $resource
     * @return  bool
     * @since   0.10.0
     * @see     https://github.com/mikey179/vfsStream/issues/6
     * @see     https://github.com/mikey179/vfsStream/issues/40
     */
    public function hasExclusiveLock($resource = null)
    {
        if (null !== $resource) {
            return $this->exclusiveLock === $this->getResourceId($resource);
        }

        return null !== $this->exclusiveLock;
    }
}
?>