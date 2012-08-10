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
    parent::setUp();
    variable_set('file_default_scheme', 'dummy-remote');
  }
}
