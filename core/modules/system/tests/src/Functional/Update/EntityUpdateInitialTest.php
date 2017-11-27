<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests handling of existing initial keys during updates.
 *
 * @see https://www.drupal.org/project/drupal/issues/2925550
 *
 * @group Update
 */
class EntityUpdateInitialTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-8.0.0-rc1-filled.standard.entity_test_update.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.entity-test-initial.php',
    ];
  }

  /**
   * Tests that a pre-existing initial key in the field schema is not a change.
   */
  public function testInitialIsIgnored() {
    $this->runUpdates();
  }

}
