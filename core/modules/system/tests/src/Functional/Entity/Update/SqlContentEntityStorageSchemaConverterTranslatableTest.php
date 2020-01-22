<?php

namespace Drupal\Tests\system\Functional\Entity\Update;

/**
 * Tests converting a translatable entity type with data to revisionable.
 *
 * @group Entity
 * @group Update
 * @group legacy
 */
class SqlContentEntityStorageSchemaConverterTranslatableTest extends SqlContentEntityStorageSchemaConverterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../fixtures/update/drupal-8.0.0-rc1-filled.standard.entity_test_update_mul.php.gz',
      __DIR__ . '/../../../../fixtures/update/drupal-8.entity-test-schema-converter-enabled.php',
    ];
  }

}
