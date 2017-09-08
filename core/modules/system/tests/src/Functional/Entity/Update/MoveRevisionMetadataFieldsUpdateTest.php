<?php

namespace Drupal\Tests\system\Functional\Entity\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for moving the revision metadata fields.
 *
 * @group Update
 */
class MoveRevisionMetadataFieldsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../tests/fixtures/update/drupal-8.2.0.bare.standard_with_entity_test_revlog_enabled.php.gz',
      __DIR__ . '/../../../../../tests/fixtures/update/drupal-8.entity-data-revision-metadata-fields-2248983.php',
      __DIR__ . '/../../../../../tests/fixtures/update/drupal-8.views-revision-metadata-fields-2248983.php',
    ];
  }

  /**
   * Tests that the revision metadata fields are moved correctly.
   */
  public function testSystemUpdate8400() {
    $this->runUpdates();

    foreach (['entity_test_revlog', 'entity_test_mul_revlog'] as $entity_type_id) {
      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
      /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
      $entity_type = $storage->getEntityType();
      $revision_metadata_field_names = $entity_type->getRevisionMetadataKeys();

      $database_schema = \Drupal::database()->schema();

      // Test that the revision metadata fields are present only in the
      // revision table.
      foreach ($revision_metadata_field_names as $revision_metadata_field_name) {
        if ($entity_type->isTranslatable()) {
          $this->assertFalse($database_schema->fieldExists($entity_type->getDataTable(), $revision_metadata_field_name));
          $this->assertFalse($database_schema->fieldExists($entity_type->getRevisionDataTable(), $revision_metadata_field_name));
        }
        else {
          $this->assertFalse($database_schema->fieldExists($entity_type->getBaseTable(), $revision_metadata_field_name));
        }
        $this->assertTrue($database_schema->fieldExists($entity_type->getRevisionTable(), $revision_metadata_field_name));
      }

      // Test that the revision metadata values have been transferred correctly
      // and that the moved fields are accessible.
      /** @var \Drupal\Core\Entity\RevisionLogInterface $entity_rev_first */
      $entity_rev_first = $storage->loadRevision(1);
      $this->assertEqual($entity_rev_first->getRevisionUserId(), '1');
      $this->assertEqual($entity_rev_first->getRevisionLogMessage(), 'first revision');
      $this->assertEqual($entity_rev_first->getRevisionCreationTime(), '1476268517');

      /** @var \Drupal\Core\Entity\RevisionLogInterface $entity_rev_second */
      $entity_rev_second = $storage->loadRevision(2);
      $this->assertEqual($entity_rev_second->getRevisionUserId(), '1');
      $this->assertEqual($entity_rev_second->getRevisionLogMessage(), 'second revision');
      $this->assertEqual($entity_rev_second->getRevisionCreationTime(), '1476268518');


      // Test that the views using revision metadata fields are updated
      // properly.
      $view = View::load($entity_type_id . '_for_2248983');
      $displays = $view->get('display');
      foreach ($displays as $display => $display_data) {
        foreach ($display_data['display_options']['fields'] as $property_data) {
          if (in_array($property_data['field'], $revision_metadata_field_names)) {
            $this->assertEqual($property_data['table'], $entity_type->getRevisionTable());
          }
        }
      }
    }
  }

}
