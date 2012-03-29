<?php

/**
 * @file
 * Definition of Drupal\Core\StreamWrapper\PhpStreamWrapperInterface.
 */

namespace Drupal\Core\StreamWrapper;

/**
 * Defines a generic PHP stream wrapper interface.
 *
 * @see http://www.php.net/manual/class.streamwrapper.php
 */
interface PhpStreamWrapperInterface {
  public function stream_open($uri, $mode, $options, &$opened_url);
  public function stream_close();
  public function stream_lock($operation);
  public function stream_read($count);
  public function stream_write($data);
  public function stream_eof();
  public function stream_seek($offset, $whence);
  public function stream_flush();
  public function stream_tell();
  public function stream_stat();
  public function unlink($uri);
  public function rename($from_uri, $to_uri);
  public function mkdir($uri, $mode, $options);
  public function rmdir($uri, $options);
  public function url_stat($uri, $flags);
  public function dir_opendir($uri, $options);
  public function dir_readdir();
  public function dir_rewinddir();
  public function dir_closedir();
}
