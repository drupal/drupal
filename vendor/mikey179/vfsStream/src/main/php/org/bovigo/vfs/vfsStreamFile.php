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
use org\bovigo\vfs\content\FileContent;
use org\bovigo\vfs\content\StringBasedFileContent;
/**
 * File container.
 *
 * @api
 */
class vfsStreamFile extends vfsStreamAbstractContent
{
    /**
     * content of the file
     *
     * @type  FileContent
     */
    private $content;
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
        $this->content = new StringBasedFileContent(null);
        $this->type    = vfsStreamContent::TYPE_FILE;
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
     * @param   string]FileContent  $content
     * @return  vfsStreamFile
     * @throws  \InvalidArgumentException
     */
    public function withContent($content)
    {
        if (is_string($content)) {
            $this->content = new StringBasedFileContent($content);
        } elseif ($content instanceof FileContent) {
            $this->content = $content;
        } else {
            throw new \InvalidArgumentException('Given content must either be a string or an instance of org\bovigo\vfs\content\FileContent');
        }

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
        return $this->content->content();
    }

    /**
     * simply open the file
     *
     * @since  0.9
     */
    public function open()
    {
        $this->content->seek(0, SEEK_SET);
        $this->lastAccessed = time();
    }

    /**
     * open file and set pointer to end of file
     *
     * @since  0.9
     */
    public function openForAppend()
    {
        $this->content->seek(0, SEEK_END);
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
        $this->content->truncate(0);
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
        $this->lastAccessed = time();
        return $this->content->read($count);
    }

    /**
     * returns the content until its end from current offset
     *
     * Using this method changes the time when the file was last accessed.
     *
     * @return  string
     * @deprecated  since 1.3.0
     */
    public function readUntilEnd()
    {
        $this->lastAccessed = time();
        return $this->content->readUntilEnd();
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
        $this->lastModified = time();
        return $this->content->write($data);
    }

    /**
     * Truncates a file to a given length
     *
     * @param   int  $size length to truncate file to
     * @return  bool
     * @since   1.1.0
     */
    public function truncate($size)
    {
        $this->content->truncate($size);
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
        return $this->content->eof();
    }

    /**
     * returns the current position within the file
     *
     * @return  int
     * @deprecated  since 1.3.0
     */
    public function getBytesRead()
    {
        return $this->content->bytesRead();
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
        return $this->content->seek($offset, $whence);
    }

    /**
     * returns size of content
     *
     * @return  int
     */
    public function size()
    {
        return $this->content->size();
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
