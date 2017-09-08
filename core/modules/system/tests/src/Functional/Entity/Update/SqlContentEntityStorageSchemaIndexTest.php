<?php

namespace Drupal\Tests\system\Functional\Entity\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that a newly-added index is properly created during database updates.
 *
 * @group Entity
 */
class SqlContentEntityStorageSchemaIndexTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests entity and field schema database updates and execution order.
   */
  public function testIndex() {
    // The initial Drupal 8 database dump before any updates does not include
    // the entity ID in the entity field data table indices that were added in
    // https://www.drupal.org/node/2261669.
    $this->assertTrue(db_index_exists('node_field_data', 'node__default_langcode'), 'Index node__default_langcode exists prior to running updates.');
    $this->assertFalse(db_index_exists('node_field_data', 'node__id__default_langcode__langcode'), 'Index node__id__default_langcode__langcode does not exist prior to running updates.');
    $this->assertFalse(db_index_exists('users_field_data', 'user__id__default_langcode__langcode'), 'Index users__id__default_langcode__langcode does not exist prior to running updates.');

    // Running database updates should update the entity schemata to add the
    // indices from https://www.drupal.org/node/2261669.
    $this->runUpdates();
    $this->assertFalse(db_index_exists('node_field_data', 'node__default_langcode'), 'Index node__default_langcode properly removed.');
    $this->assertTrue(db_index_exists('node_field_data', 'node__id__default_langcode__langcode'), 'Index node__id__default_langcode__langcode properly created on the node_field_data table.');
    $this->assertTrue(db_index_exists('users_field_data', 'user__id__default_langcode__langcode'), 'Index users__id__default_langcode__langcode properly created on the user_field_data table.');
  }

}
