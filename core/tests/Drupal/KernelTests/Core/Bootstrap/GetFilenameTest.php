<?php

namespace Drupal\KernelTests\Core\Bootstrap;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that drupal_get_filename() works correctly.
 *
 * @group Bootstrap
 * @group legacy
 */
class GetFilenameTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests drupal_get_filename() deprecation.
   */
  public function testDrupalGetFilename(): void {
    $this->expectDeprecation('drupal_get_filename() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Extension\ExtensionPathResolver::getPathname() instead. See https://www.drupal.org/node/2940438');
    $this->assertEquals('core/modules/system/system.info.yml', drupal_get_filename('module', 'system'));
  }

  /**
   * Tests drupal_get_path() deprecation.
   */
  public function testDrupalGetPath(): void {
    $this->expectDeprecation('drupal_get_path() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Extension\ExtensionPathResolver::getPath() instead. See https://www.drupal.org/node/2940438');
    $this->expectDeprecation('drupal_get_filename() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Extension\ExtensionPathResolver::getPathname() instead. See https://www.drupal.org/node/2940438');
    $this->assertEquals('core/modules/system', drupal_get_path('module', 'system'));
  }

}
