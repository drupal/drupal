<?php

namespace Drupal\system\Tests\Entity\Update;

/**
 * Runs SqlContentEntityStorageSchemaIndexTest with a dump filled with content.
 *
 * @group Entity
 */
class SqlContentEntityStorageSchemaIndexFilledTest extends SqlContentEntityStorageSchemaIndexTest {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    parent::setDatabaseDumpFiles();
    $this->databaseDumpFiles[0] = __DIR__ . '/../../../../tests/fixtures/update/drupal-8.filled.standard.php.gz';
  }

}
