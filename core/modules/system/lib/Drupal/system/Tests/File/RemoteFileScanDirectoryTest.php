<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\RemoteFileScanDirectoryTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests the file_scan_directory() function on remote filesystems.
 */
class RemoteFileScanDirectoryTest extends ScanDirectoryTest {

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
