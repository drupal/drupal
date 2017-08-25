<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path after fixing field schema data uninstallation.
 *
 * @see https://www.drupal.org/node/2573667
 *
 * @group Update
 */
class FieldSchemaDataUninstallUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.block-content-uninstall.php',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.field-schema-data-uninstall-2573667.php',
    ];
  }

  /**
   * Tests the upgrade path after fixing field schema data uninstallation.
   */
  public function testUpdateHookN() {
    $this->assertFieldSchemaData(TRUE, 'Field schema data to be purged found before update.');
    $this->runUpdates();
    $this->assertFieldSchemaData(FALSE, 'No field schema data to be purged found after update.');
  }

  /**
   * Asserts that field schema data to be purged is found.
   *
   * @param bool $found
   *   Whether field schema data is expected to be found or not.
   * @param string $message
   *   The assert message.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertFieldSchemaData($found, $message) {
    $query = \Drupal::database()
      ->select('key_value', 'kv')
      ->fields('kv');
    $query
      ->condition('kv.collection', 'entity.storage_schema.sql')
      ->condition('kv.name', 'block_content.field_schema_data.%', 'LIKE');
    $items = $query
      ->execute()
      ->fetchAll();

    return $this->assertEqual((bool) $items, $found, $message);
  }

}
