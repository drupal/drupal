<?php

namespace Drupal\Tests\views\Functional\Update;

/**
 * Runs EntityViewsDataUpdateTest with a dump filled with content.
 *
 * @group Update
 */
class EntityViewsDataUpdateFilledTest extends EntityViewsDataUpdateTest {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    parent::setDatabaseDumpFiles();
    $this->databaseDumpFiles[0] = __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.filled.standard.php.gz';
  }

}
