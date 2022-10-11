<?php

namespace Drupal\KernelTests\Core\Database;

/**
 * Trait to manage installation for test tables.
 */
trait DatabaseTestSchemaInstallTrait {

  /**
   * Sets up our sample table schema.
   */
  protected function installSampleSchema(): void {
    $this->installSchema(
      'database_test', [
        'test',
        'test_classtype',
        'test_people',
        'test_people_copy',
        'test_one_blob',
        'test_two_blobs',
        'test_task',
        'test_null',
        'test_serialized',
        'TEST_UPPERCASE',
        'select',
        'virtual',
      ]
    );
  }

}
