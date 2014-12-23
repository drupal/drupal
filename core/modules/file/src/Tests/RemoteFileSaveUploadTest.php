<?php

/**
 * @file
 * Definition of Drupal\file\Tests\RemoteFileSaveUploadTest.
 */

namespace Drupal\file\Tests;

/**
 * Tests the file uploading functions.
 *
 * @group file
 */
class RemoteFileSaveUploadTest extends SaveUploadTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file_test');

  protected function setUp() {
    parent::setUp();
    $this->config('system.file')->set('default_scheme', 'dummy-remote')->save();
  }
}
