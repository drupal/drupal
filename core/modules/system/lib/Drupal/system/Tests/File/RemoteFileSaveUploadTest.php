<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\RemoteFileSaveUploadTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests the file_save_upload() function on remote filesystems.
 */
class RemoteFileSaveUploadTest extends SaveUploadTest {

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
