<?php

namespace Drupal\system\Tests\Update;

/**
 * Runs RouterIndexOptimizationTest with a dump filled with content.
 *
 * @group Update
 */
class RouterIndexOptimizationFilledTest extends RouterIndexOptimizationTest {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    parent::setDatabaseDumpFiles();
    $this->databaseDumpFiles[0] = __DIR__ . '/../../../tests/fixtures/update/drupal-8.filled.standard.php.gz';
  }

}
