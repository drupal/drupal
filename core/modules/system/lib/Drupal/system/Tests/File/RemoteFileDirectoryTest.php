<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\RemoteFileDirectoryTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Directory related tests.
 */
class RemoteFileDirectoryTest extends DirectoryTest {

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
    \Drupal::config('system.file')->set('default_scheme', 'dummy-remote')->save();
  }
}
