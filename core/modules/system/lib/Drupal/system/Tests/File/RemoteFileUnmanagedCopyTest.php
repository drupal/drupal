<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\RemoteFileUnmanagedCopyTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Unmanaged copy related tests on remote filesystems.
 */
class RemoteFileUnmanagedCopyTest extends UnmanagedCopyTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file_test');

  public static function getInfo() {
    $info = parent::getInfo();
    $info['group'] = 'File API (remote)';
    return $info;
  }

  function setUp() {
    parent::setUp('file_test');
    config('system.file')->set('default_scheme', 'dummy-remote')->save();
  }
}
