<?php

namespace Drupal\Core\StreamWrapper;

/**
 * Defines a read-only Drupal stream wrapper base class.
 *
 * This class provides a minimal-read only stream wrapper implementation.
 * Specifically, it only implements the writing classes and read classes where
 * we need to restrict 'write-capable' arguments.
 *
 * Drupal\Core\StreamWrapper\ReadOnlyStream implementations need to implement
 * all the read-related classes.
 */
abstract class ReadOnlyStream implements StreamWrapperInterface {
  /**
   * Stream context resource.
   *
   * @var resource
   */
  public $context;

  /**
   * A generic resource handle.
   *
   * @var resource
   */
  public $handle = NULL;

  /**
   * Instance URI (stream).
   *
   * A stream is referenced as "scheme://target".
   *
   * @var string
   */
  protected $uri;

  /**
   * {@inheritdoc}
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * Support for fopen(), file_get_contents(), etc.
   *
   * Any write modes will be rejected, as this is a read-only stream wrapper.
   *
   * @param string $uri
   *   A string containing the URI to the file to open.
   * @param int $mode
   *   The file mode, only strict readonly modes are supported.
   * @param int $options
   *   A bit mask of STREAM_USE_PATH and STREAM_REPORT_ERRORS.
   * @param string $opened_path
   *   A string containing the path actually opened.
   *
   * @return bool
   *   TRUE if $mode denotes a readonly mode and the file was opened
   *   successfully, FALSE otherwise.
   *
   * @see http://php.net/manual/streamwrapper.stream-open.php
   */
  public function stream_open($uri, $mode, $options, &$opened_path) {
    if (!in_array($mode, ['r', 'rb', 'rt'])) {
      if ($options & STREAM_REPORT_ERRORS) {
        trigger_error('stream_open() write modes not supported for read-only stream wrappers', E_USER_WARNING);
      }
      return FALSE;
    }

    $this->uri = $uri;
    $path = $this->getLocalPath();
    $this->handle = ($options & STREAM_REPORT_ERRORS) ? fopen($path, $mode) : @fopen($path, $mode);

    if ($this->handle !== FALSE && ($options & STREAM_USE_PATH)) {
      $opened_path = $path;
    }

    return (bool) $this->handle;
  }

  /**
   * Support for flock().
   *
   * An exclusive lock attempt will be rejected, as this is a read-only stream
   * wrapper.
   *
   * @param int $operation
   *   One of the following:
   *   - LOCK_SH to acquire a shared lock (reader).
   *   - LOCK_EX to acquire an exclusive lock (writer).
   *   - LOCK_UN to release a lock (shared or exclusive).
   *   - LOCK_NB if you don't want flock() to block while locking (not
   *     supported on Windows).
   *
   * @return bool
   *   Return FALSE for an exclusive lock (writer), as this is a read-only
   *   stream wrapper.  Return the result of flock() for other valid operations.
   *   Defaults to TRUE if an invalid operation is passed.
   *
   * @see http://php.net/manual/streamwrapper.stream-lock.php
   */
  public function stream_lock($operation) {
    if (in_array($operation, [LOCK_EX, LOCK_EX | LOCK_NB])) {
      trigger_error('stream_lock() exclusive lock operations not supported for read-only stream wrappers', E_USER_WARNING);
      return FALSE;
    }
    if (in_array($operation, [LOCK_SH, LOCK_UN, LOCK_SH | LOCK_NB])) {
      return flock($this->handle, $operation);
    }

    return TRUE;
  }

  /**
   * Support for fwrite(), file_put_contents() etc.
   *
   * Data will not be written as this is a read-only stream wrapper.
   *
   * @param string $data
   *   The string to be written.
   *
   * @return bool
   *   FALSE as data will not be written.
   *
   * @see http://php.net/manual/streamwrapper.stream-write.php
   */
  public function stream_write($data) {
    trigger_error('stream_write() not supported for read-only stream wrappers', E_USER_WARNING);
    return FALSE;
  }

  /**
   * Support for fflush().
   *
   * Nothing will be output to the file, as this is a read-only stream wrapper.
   * However as stream_flush is called during stream_close we should not trigger
   * an error.
   *
   * @return bool
   *   FALSE, as no data will be stored.
   *
   * @see http://php.net/manual/streamwrapper.stream-flush.php
   */
  public function stream_flush() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Does not change meta data as this is a read-only stream wrapper.
   */
  public function stream_metadata($uri, $option, $value) {
    trigger_error('stream_metadata() not supported for read-only stream wrappers', E_USER_WARNING);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_truncate($new_size) {
    trigger_error('stream_truncate() not supported for read-only stream wrappers', E_USER_WARNING);
    return FALSE;
  }

  /**
   * Support for unlink().
   *
   * The file will not be deleted from the stream as this is a read-only stream
   * wrapper.
   *
   * @param string $uri
   *   A string containing the uri to the resource to delete.
   *
   * @return bool
   *   TRUE so that file_delete() will remove db reference to file. File is not
   *   actually deleted.
   *
   * @see http://php.net/manual/streamwrapper.unlink.php
   */
  public function unlink($uri) {
    trigger_error('unlink() not supported for read-only stream wrappers', E_USER_WARNING);
    return TRUE;
  }

  /**
   * Support for rename().
   *
   * This file will not be renamed as this is a read-only stream wrapper.
   *
   * @param string $from_uri
   *   The uri to the file to rename.
   * @param string $to_uri
   *   The new uri for file.
   *
   * @return bool
   *   FALSE as file will never be renamed.
   *
   * @see http://php.net/manual/streamwrapper.rename.php
   */
  public function rename($from_uri, $to_uri) {
    trigger_error('rename() not supported for read-only stream wrappers', E_USER_WARNING);
    return FALSE;
  }

  /**
   * Support for mkdir().
   *
   * Directory will never be created as this is a read-only stream wrapper.
   *
   * @param string $uri
   *   A string containing the URI to the directory to create.
   * @param int $mode
   *   Permission flags - see mkdir().
   * @param int $options
   *   A bit mask of STREAM_REPORT_ERRORS and STREAM_MKDIR_RECURSIVE.
   *
   * @return bool
   *   FALSE as directory will never be created.
   *
   * @see http://php.net/manual/streamwrapper.mkdir.php
   */
  public function mkdir($uri, $mode, $options) {
    trigger_error('mkdir() not supported for read-only stream wrappers', E_USER_WARNING);
    return FALSE;
  }

  /**
   * Support for rmdir().
   *
   * Directory will never be deleted as this is a read-only stream wrapper.
   *
   * @param string $uri
   *   A string containing the URI to the directory to delete.
   * @param int $options
   *   A bit mask of STREAM_REPORT_ERRORS.
   *
   * @return bool
   *   FALSE as directory will never be deleted.
   *
   * @see http://php.net/manual/streamwrapper.rmdir.php
   */
  public function rmdir($uri, $options) {
    trigger_error('rmdir() not supported for read-only stream wrappers', E_USER_WARNING);
    return FALSE;
  }

}
