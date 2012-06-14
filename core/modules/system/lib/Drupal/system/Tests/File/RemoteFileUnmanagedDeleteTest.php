<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\RemoteFileUnmanagedDeleteTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Deletion related tests on remote filesystems.
 */
class RemoteFileUnmanagedDeleteTest extends UnmanagedDeleteTest {
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
