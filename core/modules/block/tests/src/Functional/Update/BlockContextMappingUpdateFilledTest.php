<?php

namespace Drupal\Tests\block\Functional\Update;

/**
 * Runs BlockContextMappingUpdateTest with a dump filled with content.
 *
 * @group Update
 * @group legacy
 */
class BlockContextMappingUpdateFilledTest extends BlockContextMappingUpdateTest {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    parent::setDatabaseDumpFiles();
    $this->databaseDumpFiles[0] = __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.filled.standard.php.gz';
  }

}
