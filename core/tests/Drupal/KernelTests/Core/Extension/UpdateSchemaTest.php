<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for schema and update includes.
 *
 * @group Core
 */
class UpdateSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update_test_schema'];

  /**
   * Tests the function parses schema updates as integer numbers.
   *
   * @see \Drupal\Core\Update\VersioningUpdateRegistry::getAvailableUpdates()
   */
  public function testDrupalGetSchemaVersionsInt() {
    \Drupal::state()->set('update_test_schema_version', 8001);
    $this->installSchema('update_test_schema', ['update_test_schema_table']);
    $schema = \Drupal::service('update.update_registry')->getAvailableUpdates('update_test_schema');
    foreach ($schema as $version) {
      $this->assertIsInt($version);
    }
  }

  /**
   * Deprecation testing for drupal_get_schema_versions function.
   *
   * @group legacy
   * @see drupal_get_schema_versions()
   */
  public function testDrupalGetSchemaVersionsLegacyTest() {
    $this->expectDeprecation('drupal_get_schema_versions() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Update\VersioningUpdateRegistry::getAvailableUpdates() instead. See https://www.drupal.org/node/2444417');
    $this->assertEmpty(drupal_get_schema_versions('update_test_schema'));
  }

}
