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
   * Tests deprecated FileCreateUrl.
   */
  public function testDeprecatedFileCreateUrl() {
    $this->expectDeprecation('file_create_url() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use the appropriate method on \Drupal\Core\File\FileUrlGeneratorInterface instead. See https://www.drupal.org/node/2940031');
    $this->expectDeprecation('file_url_transform_relative() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\File\FileUrlGenerator::transformRelative() instead. See https://www.drupal.org/node/2940031');
    $filepath = 'core/assets/vendor/jquery/jquery.min.js';
    $url = file_url_transform_relative(file_create_url($filepath));
    $this->assertNotEmpty($url);
  }

}
