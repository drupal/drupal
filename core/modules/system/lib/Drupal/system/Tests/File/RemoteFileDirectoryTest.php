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
