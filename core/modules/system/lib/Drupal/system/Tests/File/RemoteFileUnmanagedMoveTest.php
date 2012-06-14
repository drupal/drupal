<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\RemoteFileUnmanagedMoveTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Unmanaged move related tests on remote filesystems.
 */
class RemoteFileUnmanagedMoveTest extends UnmanagedMoveTest {
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
