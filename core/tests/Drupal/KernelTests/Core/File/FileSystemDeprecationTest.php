<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecations in file.inc.
 *
 * @group File
 * @group legacy
 */
class FileSystemDeprecationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system'];

  /**
   * @expectedDeprecation drupal_move_uploaded_file() is deprecated in Drupal 8.0.x-dev and will be removed before Drupal 9.0.0. Use \Drupal\Core\File\FileSystemInterface::moveUploadedFile(). See https://www.drupal.org/node/2418133.
   */
  public function testDeprecatedFileMoveUploadedFile() {
    $this->assertNotNull(drupal_move_uploaded_file('', ''));
  }

}
