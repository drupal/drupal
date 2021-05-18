<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that 'all' index is dropped from the 'key_value_expire' table.
 *
 * @group Update
 * @see system_post_update_remove_key_value_expire_all_index()
 */
class DropIndexAllOnKeyValueExpireTableUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      dirname(__DIR__, 3) . '/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that 'all' index is dropped from the 'key_value_expire' table.
   */
  public function testUpdate() {
    $schema = \Drupal::database()->schema();

    $this->assertTrue($schema->indexExists('key_value_expire', 'all'));
    $this->runUpdates();
    $this->assertFalse($schema->indexExists('key_value_expire', 'all'));
  }

}
