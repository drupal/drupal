<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\Update\SqlContentEntityStorageSchemaIndexTest.
 */

namespace Drupal\system\Tests\Entity\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests that a newly-added index is properly created during database updates.
 *
 * @group Entity
 */
class SqlContentEntityStorageSchemaIndexTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update_order_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
    parent::setUp();
  }

  /**
   * Tests entity and field schema database updates and execution order.
   */
  public function testIndex() {
    // Enable the hook implementations in the update_order_test module.
    \Drupal::state()->set('update_order_test', TRUE);

    // The initial Drupal 8 database dump before any updates does not include
    // the entity ID in the entity field data table indices that were added in
    // https://www.drupal.org/node/2261669.
    $this->assertTrue(db_index_exists('node_field_data', 'node__default_langcode'), 'Index node__default_langcode exists prior to running updates.');
    $this->assertFalse(db_index_exists('node_field_data', 'node__id__default_langcode__langcode'), 'Index node__id__default_langcode__langcode does not exist prior to running updates.');
    $this->assertFalse(db_index_exists('users_field_data', 'user__id__default_langcode__langcode'), 'Index users__id__default_langcode__langcode does not exist prior to running updates.');

    // Running database updates should automatically update the entity schemata
    // to add the indices from https://www.drupal.org/node/2261669.
    $this->runUpdates();
    $this->assertFalse(db_index_exists('node_field_data', 'node__default_langcode'), 'Index node__default_langcode properly removed.');
    $this->assertTrue(db_index_exists('node_field_data', 'node__id__default_langcode__langcode'), 'Index node__id__default_langcode__langcode properly created on the node_field_data table.');
    $this->assertTrue(db_index_exists('users_field_data', 'user__id__default_langcode__langcode'), 'Index users__id__default_langcode__langcode properly created on the user_field_data table.');

    // Ensure that hook_update_N() implementations were in the expected order
    // relative to the entity and field updates. The expected order is:
    // 1. Initial Drupal 8.0.0-beta12 installation with no indices.
    // 2. update_order_test_update_8001() is invoked.
    // 3. update_order_test_update_8002() is invoked.
    // 4. update_order_test_update_8002() explicitly applies the updates for
    //    the update_order_test_field storage. See update_order_test.module.
    // 5. update_order_test_update_8002() explicitly applies the updates for
    //    the node entity type indices listed above.
    // 6. The remaining entity schema updates are applied automatically after
    //    all update hook implementations have run, which applies the user
    //    index update.
   $this->assertTrue(\Drupal::state()->get('update_order_test_update_8001', FALSE), 'Index node__default_langcode still existed during update_order_test_update_8001(), indicating that it ran before the entity type updates.');

    // Node updates were run during update_order_test_update_8002().
    $this->assertFalse(\Drupal::state()->get('update_order_test_update_8002_node__default_langcode', TRUE), 'The node__default_langcode index was removed during update_order_test_update_8002().');
    $this->assertTrue(\Drupal::state()->get('update_order_test_update_8002_node__id__default_langcode__langcode', FALSE), 'The node__id__default_langcode__langcode index was created during update_order_test_update_8002().');

    // Ensure that the base field created by update_order_test_update_8002() is
    // created when we expect.
    $this->assertFalse(\Drupal::state()->get('update_order_test_update_8002_update_order_test_before', TRUE), 'The update_order_test field was not been created on Node before update_order_test_update_8002().');
    $this->assertTrue(\Drupal::state()->get('update_order_test_update_8002_update_order_test_after', FALSE), 'The update_order_test field was created on Node by update_order_test_update_8002().');

    // User update were not run during update_order_test_update_8002().
    $this->assertFalse(\Drupal::state()->get('update_order_test_update_8002_user__id__default_langcode__langcode', TRUE));
  }

}
