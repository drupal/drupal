<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test for htaccess deprecations.
 *
 * @group File
 * @group legacy
 */
class HtaccessDeprecationTest extends KernelTestBase {

  /**
   * Tests messages for deprecated functions.
   *
   * @expectedDeprecation file_save_htaccess() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Component\FileSecurity\FileSecurity::writeHtaccess() instead. See https://www.drupal.org/node/2940126
   * @expectedDeprecation file_ensure_htaccess() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\File\HtaccessWriter::ensure() instead. See https://www.drupal.org/node/2940126
   */
  public function testDeprecatedFunctions() {
    $public = Settings::get('file_public_path') . '/test/public';
    \Drupal::service('file_system')->prepareDirectory($public, FileSystemInterface::CREATE_DIRECTORY);
    $this->assertTrue(file_save_htaccess($public));
    file_ensure_htaccess();
  }

}
