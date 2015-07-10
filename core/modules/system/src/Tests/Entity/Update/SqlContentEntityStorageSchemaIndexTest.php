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
  public function setUp() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
    parent::setUp();
  }

  /**
   * Test for the new index.
   */
  public function testIndex() {
    $this->assertTrue(db_index_exists('node_field_data', 'node__default_langcode'), 'Index node__default_langcode exists prior to running updates.');
    $this->assertFalse(db_index_exists('node_field_data', 'node__id__default_langcode__langcode'), 'Index node__id__default_langcode__langcode does not exist prior to running updates.');
    $this->assertFalse(db_index_exists('users_field_data', 'user__id__default_langcode__langcode'), 'Index users__id__default_langcode__langcode does not exist prior to running updates.');
    $this->runUpdates();
    $this->assertFalse(db_index_exists('node_field_data', 'node__default_langcode'), 'Index node__default_langcode properly removed.');
    $this->assertTrue(db_index_exists('node_field_data', 'node__id__default_langcode__langcode'), 'Index node__id__default_langcode__langcode properly created on the node_field_data table.');
    $this->assertTrue(db_index_exists('users_field_data', 'user__id__default_langcode__langcode'), 'Index users__id__default_langcode__langcode properly created on the user_field_data table.');
  }

}
