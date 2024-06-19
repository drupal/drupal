<?php

declare(strict_types=1);

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
   * @see \Drupal\Core\Update\UpdateHookRegistry::getAvailableUpdates()
   */
  public function testDrupalGetSchemaVersionsInt(): void {
    \Drupal::state()->set('update_test_schema_version', 8001);
    $this->installSchema('update_test_schema', ['update_test_schema_table']);
    $schema = \Drupal::service('update.update_hook_registry')->getAvailableUpdates('update_test_schema');
    foreach ($schema as $version) {
      $this->assertIsInt($version);
    }
  }

}
