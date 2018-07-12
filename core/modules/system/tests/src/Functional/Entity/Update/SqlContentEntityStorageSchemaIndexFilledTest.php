<?php

namespace Drupal\Tests\system\Functional\Entity\Update;

/**
 * Runs SqlContentEntityStorageSchemaIndexTest with a dump filled with content.
 *
 * @group Entity
 * @group legacy
 */
class SqlContentEntityStorageSchemaIndexFilledTest extends SqlContentEntityStorageSchemaIndexTest {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    parent::setDatabaseDumpFiles();
    $this->databaseDumpFiles[0] = __DIR__ . '/../../../../fixtures/update/drupal-8.filled.standard.php.gz';
  }

}
