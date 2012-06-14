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
  public static function getInfo() {
    $info = parent::getInfo();
    $info['group'] = 'File API (remote)';
    return $info;
  }

  function setUp() {
    parent::setUp('file_test');
    variable_set('file_default_scheme', 'dummy-remote');
  }
}
