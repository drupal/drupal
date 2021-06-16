<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that updates clean-up non-integer schema version.
 *
 * @group Update
 * @see system_post_update_schema_version_int()
 */
class SchemaVersionUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update_test_schema'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-8.8.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.update-schema-version-int.php',
    ];
  }

  /**
   * Tests that upgrade converted string value to integer.
   */
  public function testSchemaVersionsIsInt() {
    $this->assertSame('8901', \Drupal::keyValue('system.schema')->get('update_test_schema'));
    $this->runUpdates();
    $this->assertSame(8901, \Drupal::keyValue('system.schema')->get('update_test_schema'));
  }

}
