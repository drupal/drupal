<?php

namespace Drupal\Tests\file\Functional;

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
  protected static $modules = ['file_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    $this->config('system.file')->set('default_scheme', 'dummy-remote')->save();
  }

}
