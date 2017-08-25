<?php

namespace Drupal\Tests\system\Functional\Update;

/**
 * Runs MenuTreeSerializationTitleTest with a dump filled with content.
 *
 * @group Update
 */
class MenuTreeSerializationTitleFilledTest extends MenuTreeSerializationTitleTest {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    parent::setDatabaseDumpFiles();
    $this->databaseDumpFiles[0] = __DIR__ . '/../../../../tests/fixtures/update/drupal-8.filled.standard.php.gz';
  }

}
