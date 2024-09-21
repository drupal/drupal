<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional;

/**
 * Tests the file uploading functions.
 *
 * @group file
 */
class RemoteFileSaveUploadTest extends SaveUploadTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('system.file')->set('default_scheme', 'dummy-remote')->save();
  }

}
