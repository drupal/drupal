<?php

namespace Drupal\Tests\block_content\Functional\Update;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update functions for the Block Content module.
 *
 * @group Update
 */
class BlockContentUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests the revision metadata fields and revision data table additions.
   */
  public function testSimpleUpdates() {
    $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    $entity_type = $entity_definition_update_manager->getEntityType('block_content');
    $this->assertNull($entity_type->getRevisionDataTable());

    $this->runUpdates();

    $post_revision_created = $entity_definition_update_manager->getFieldStorageDefinition('revision_created', 'block_content');
    $post_revision_user = $entity_definition_update_manager->getFieldStorageDefinition('revision_user', 'block_content');
    $this->assertTrue($post_revision_created instanceof BaseFieldDefinition, "Revision created field found");
    $this->assertTrue($post_revision_user instanceof BaseFieldDefinition, "Revision user field found");

    $this->assertEqual('created', $post_revision_created->getType(), "Field is type created");
    $this->assertEqual('entity_reference', $post_revision_user->getType(), "Field is type entity_reference");

    $entity_type = $entity_definition_update_manager->getEntityType('block_content');
    $this->assertEqual('block_content_field_revision', $entity_type->getRevisionDataTable());
  }

  /**
   * Tests adding a status field to the block content entity type.
   *
   * @see block_content_update_8400()
   */
  public function testStatusFieldAddition() {
    $schema = \Drupal::database()->schema();
    $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();

    // Run updates.
    $this->runUpdates();

    // Check that the field exists and has the correct label.
    $updated_field = $entity_definition_update_manager->getFieldStorageDefinition('status', 'block_content');
    $this->assertEqual('Publishing status', $updated_field->getLabel());

    $content_translation_status = $entity_definition_update_manager->getFieldStorageDefinition('content_translation_status', 'block_content');
    $this->assertNull($content_translation_status);

    $this->assertFalse($schema->fieldExists('block_content_field_revision', 'content_translation_status'));
    $this->assertFalse($schema->fieldExists('block_content_field_data', 'content_translation_status'));
  }

}
