<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\RemoteFileUnmanagedSaveDataTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests the file_unmanaged_save_data() function on remote filesystems.
 */
class RemoteFileUnmanagedSaveDataTest extends UnmanagedSaveDataTest {
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
