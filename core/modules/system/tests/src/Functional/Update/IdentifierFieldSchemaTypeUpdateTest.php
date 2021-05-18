<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for updating the stored type of identifier fields.
 *
 * @see https://www.drupal.org/node/2928906
 *
 * @group Update
 */
class IdentifierFieldSchemaTypeUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests system_update_8901().
   */
  public function testSystemUpdate8901() {
    // The installed field storage schema is wrong before running the update.
    $key_value_store = \Drupal::keyValue('entity.storage_schema.sql');
    $id_schema = $key_value_store->get('node.field_schema_data.nid', []);
    $revision_id_schema = $key_value_store->get('node.field_schema_data.vid', []);

    $this->assertEquals('int', $id_schema['node']['fields']['nid']['type']);
    $this->assertEquals('int', $id_schema['node_revision']['fields']['nid']['type']);
    $this->assertEquals('int', $revision_id_schema['node']['fields']['vid']['type']);
    $this->assertEquals('int', $revision_id_schema['node_revision']['fields']['vid']['type']);

    $this->runUpdates();

    // Now check that the schema has been corrected.
    $id_schema = $key_value_store->get('node.field_schema_data.nid', []);
    $revision_id_schema = $key_value_store->get('node.field_schema_data.vid', []);

    $this->assertEquals('serial', $id_schema['node']['fields']['nid']['type']);
    $this->assertEquals('int', $id_schema['node_revision']['fields']['nid']['type']);
    $this->assertEquals('int', $revision_id_schema['node']['fields']['vid']['type']);
    $this->assertEquals('serial', $revision_id_schema['node_revision']['fields']['vid']['type']);

    // Check that creating and loading a node still works as expected.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node = $node_storage->create([
      'title' => 'Test update',
      'type' => 'article',
    ]);
    $node->save();

    $node = $node_storage->load($node->id());
    $this->assertEquals('Test update', $node->label());
  }

}
