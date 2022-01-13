<?php

namespace Drupal\file_test\StreamWrapper;

use Drupal\Core\StreamWrapper\ReadOnlyStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Helper class for testing the stream wrapper registry.
 *
 * Dummy external stream wrapper implementation (dummy-external-readonly://).
 */
class DummyExternalReadOnlyWrapper extends ReadOnlyStream {

  /**
   * @inheritDoc
   */
  public static function getType() {
    return StreamWrapperInterface::READ_VISIBLE;
  }

  /**
   * @inheritDoc
   */
  public function getName() {
    return t('Dummy external stream wrapper (readonly)');
  }

  /**
   * @inheritDoc
   */
  public function getDescription() {
    return t('Dummy external read-only stream wrapper for testing.');
  }

  /**
   * @inheritDoc
   */
  public function getExternalUrl() {
    [, $target] = explode('://', $this->uri, 2);
    return 'https://www.dummy-external-readonly.com/' . $target;
  }

  /**
   * @inheritDoc
   */
  public function realpath() {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function dirname($uri = NULL) {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function dir_closedir() {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function dir_opendir($path, $options) {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function dir_readdir() {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function dir_rewinddir() {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function stream_cast($cast_as) {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function stream_close() {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function stream_eof() {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function stream_read($count) {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function stream_seek($offset, $whence = SEEK_SET) {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function stream_set_option($option, $arg1, $arg2) {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function stream_stat() {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function stream_tell() {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function url_stat($path, $flags) {
    return FALSE;
  }

}
