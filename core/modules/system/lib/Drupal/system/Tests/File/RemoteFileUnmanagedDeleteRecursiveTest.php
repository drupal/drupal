<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\RemoteFileUnmanagedDeleteRecursiveTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Deletion related tests on remote filesystems.
 */
class RemoteFileUnmanagedDeleteRecursiveTest extends UnmanagedDeleteRecursiveTest {
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
