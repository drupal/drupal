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
