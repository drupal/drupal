<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated update.inc functions.
 *
 * @group legacy
 * @group extension
 *
 * @todo Remove in https://www.drupal.org/node/3210931
 */
class UpdateDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Include the legacy update.inc file.
    include_once $this->root . '/core/includes/update.inc';
  }

  /**
   * Deprecation testing for drupal_get_schema_versions function.
   *
   * @see drupal_get_schema_versions()
   */
  public function testDrupalGetSchemaVersionsLegacyTest() {
    $this->expectDeprecation('drupal_get_schema_versions() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Update\UpdateHookRegistry::getAvailableUpdates() instead. See https://www.drupal.org/node/2444417');
    $this->assertEmpty(drupal_get_schema_versions('update_test_schema'));
  }

  /**
   * Deprecation testing for drupal installed schema version functions.
   *
   * @see drupal_get_installed_schema_version()
   * @see drupal_set_installed_schema_version()
   */
  public function testDrupalGetInstalledSchemaVersion() {
    $this->expectDeprecation('drupal_get_installed_schema_version() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Update\UpdateHookRegistry::getInstalledVersion() or \Drupal\Core\Update\UpdateHookRegistry::getAllInstalledVersions() instead. See https://www.drupal.org/node/2444417');
    $this->assertIsArray(drupal_get_installed_schema_version(NULL, TRUE, TRUE));
    $this->expectDeprecation('drupal_set_installed_schema_version() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Update\UpdateHookRegistry::setInstalledVersion() instead. See https://www.drupal.org/node/2444417');
    drupal_set_installed_schema_version('system', 8001);
  }

}
