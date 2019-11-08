<?php

namespace Drupal\Tests\system\Functional\Update;

/**
 * Runs RouterIndexOptimizationTest with a dump filled with content.
 *
 * @group Update
 * @group legacy
 */
class RouterIndexOptimizationFilledTest extends RouterIndexOptimizationTest {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    parent::setDatabaseDumpFiles();
    $this->databaseDumpFiles[0] = __DIR__ . '/../../../../tests/fixtures/update/drupal-8.filled.standard.php.gz';
  }

}
