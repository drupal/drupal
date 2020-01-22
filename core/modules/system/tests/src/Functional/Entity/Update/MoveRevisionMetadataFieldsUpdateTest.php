<?php

namespace Drupal\Tests\system\Functional\Entity\Update;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for moving the revision metadata fields.
 *
 * This test uses the entity_test_revlog module, which intentionally omits the
 * entity_metadata_keys fields. This causes deprecation errors.
 *
 * @group Update
 * @group legacy
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
   *
   * @expectedDeprecation The revision_user revision metadata key is not set for entity type: entity_test_mul_revlog See: https://www.drupal.org/node/2831499
   * @expectedDeprecation The revision_created revision metadata key is not set for entity type: entity_test_mul_revlog See: https://www.drupal.org/node/2831499
   * @expectedDeprecation The revision_log_message revision metadata key is not set for entity type: entity_test_mul_revlog See: https://www.drupal.org/node/2831499
   * @expectedDeprecation Support for pre-8.3.0 revision table names in imported views is deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Imported views must reference the correct tables. See https://www.drupal.org/node/2831499
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

  /**
   * Tests the addition of required revision metadata keys.
   *
   * This test ensures that already cached entity instances will only return the
   * required revision metadata keys they have been cached with and only new
   * instances will return all the new required revision metadata keys.
   *
   * @expectedDeprecation The revision_user revision metadata key is not set for entity type: entity_test_mul_revlog See: https://www.drupal.org/node/2831499
   * @expectedDeprecation The revision_created revision metadata key is not set for entity type: entity_test_mul_revlog See: https://www.drupal.org/node/2831499
   * @expectedDeprecation The revision_log_message revision metadata key is not set for entity type: entity_test_mul_revlog See: https://www.drupal.org/node/2831499
   */
  public function testAddingRequiredRevisionMetadataKeys() {
    // Ensure that cached entity types without required revision metadata keys
    // will not return any of the newly added required revision metadata keys.
    // Contains no revision metadata keys and the property holding the required
    // metadata keys is empty, the entity type id is "entity_test_mul_revlog".
    $cached_with_no_metadata_keys = 'Tzo4MjoiRHJ1cGFsXFRlc3RzXHN5c3RlbVxGdW5jdGlvbmFsXEVudGl0eVxVcGRhdGVcVGVzdFJldmlzaW9uTWV0YWRhdGFCY0xheWVyRW50aXR5VHlwZSI6Mzk6e3M6MjU6IgAqAHJldmlzaW9uX21ldGFkYXRhX2tleXMiO2E6MDp7fXM6MzE6IgAqAHJlcXVpcmVkUmV2aXNpb25NZXRhZGF0YUtleXMiO2E6MDp7fXM6MTU6IgAqAHN0YXRpY19jYWNoZSI7YjoxO3M6MTU6IgAqAHJlbmRlcl9jYWNoZSI7YjoxO3M6MTk6IgAqAHBlcnNpc3RlbnRfY2FjaGUiO2I6MTtzOjE0OiIAKgBlbnRpdHlfa2V5cyI7YTo1OntzOjg6InJldmlzaW9uIjtzOjA6IiI7czo2OiJidW5kbGUiO3M6MDoiIjtzOjg6Imxhbmdjb2RlIjtzOjA6IiI7czoxNjoiZGVmYXVsdF9sYW5nY29kZSI7czoxNjoiZGVmYXVsdF9sYW5nY29kZSI7czoyOToicmV2aXNpb25fdHJhbnNsYXRpb25fYWZmZWN0ZWQiO3M6Mjk6InJldmlzaW9uX3RyYW5zbGF0aW9uX2FmZmVjdGVkIjt9czo1OiIAKgBpZCI7czoyMjoiZW50aXR5X3Rlc3RfbXVsX3JldmxvZyI7czoxNjoiACoAb3JpZ2luYWxDbGFzcyI7TjtzOjExOiIAKgBoYW5kbGVycyI7YTozOntzOjY6ImFjY2VzcyI7czo0NToiRHJ1cGFsXENvcmVcRW50aXR5XEVudGl0eUFjY2Vzc0NvbnRyb2xIYW5kbGVyIjtzOjc6InN0b3JhZ2UiO3M6NDY6IkRydXBhbFxDb3JlXEVudGl0eVxTcWxcU3FsQ29udGVudEVudGl0eVN0b3JhZ2UiO3M6MTI6InZpZXdfYnVpbGRlciI7czozNjoiRHJ1cGFsXENvcmVcRW50aXR5XEVudGl0eVZpZXdCdWlsZGVyIjt9czoxOToiACoAYWRtaW5fcGVybWlzc2lvbiI7TjtzOjI1OiIAKgBwZXJtaXNzaW9uX2dyYW51bGFyaXR5IjtzOjExOiJlbnRpdHlfdHlwZSI7czo4OiIAKgBsaW5rcyI7YTowOnt9czoxNzoiACoAbGFiZWxfY2FsbGJhY2siO047czoyMToiACoAYnVuZGxlX2VudGl0eV90eXBlIjtOO3M6MTI6IgAqAGJ1bmRsZV9vZiI7TjtzOjE1OiIAKgBidW5kbGVfbGFiZWwiO047czoxMzoiACoAYmFzZV90YWJsZSI7TjtzOjIyOiIAKgByZXZpc2lvbl9kYXRhX3RhYmxlIjtOO3M6MTc6IgAqAHJldmlzaW9uX3RhYmxlIjtOO3M6MTM6IgAqAGRhdGFfdGFibGUiO047czoxNToiACoAdHJhbnNsYXRhYmxlIjtiOjA7czoxOToiACoAc2hvd19yZXZpc2lvbl91aSI7YjowO3M6ODoiACoAbGFiZWwiO3M6MDoiIjtzOjE5OiIAKgBsYWJlbF9jb2xsZWN0aW9uIjtzOjA6IiI7czoxNzoiACoAbGFiZWxfc2luZ3VsYXIiO3M6MDoiIjtzOjE1OiIAKgBsYWJlbF9wbHVyYWwiO3M6MDoiIjtzOjE0OiIAKgBsYWJlbF9jb3VudCI7YTowOnt9czoxNToiACoAdXJpX2NhbGxiYWNrIjtOO3M6ODoiACoAZ3JvdXAiO047czoxNDoiACoAZ3JvdXBfbGFiZWwiO047czoyMjoiACoAZmllbGRfdWlfYmFzZV9yb3V0ZSI7TjtzOjI2OiIAKgBjb21tb25fcmVmZXJlbmNlX3RhcmdldCI7YjowO3M6MjI6IgAqAGxpc3RfY2FjaGVfY29udGV4dHMiO2E6MDp7fXM6MTg6IgAqAGxpc3RfY2FjaGVfdGFncyI7YToxOntpOjA7czo5OiJ0ZXN0X2xpc3QiO31zOjE0OiIAKgBjb25zdHJhaW50cyI7YTowOnt9czoxMzoiACoAYWRkaXRpb25hbCI7YTowOnt9czo4OiIAKgBjbGFzcyI7TjtzOjExOiIAKgBwcm92aWRlciI7TjtzOjIwOiIAKgBzdHJpbmdUcmFuc2xhdGlvbiI7Tjt9';
    /** @var \Drupal\Tests\system\Functional\Entity\Update\TestRevisionMetadataBcLayerEntityType $entity_type */
    $entity_type = unserialize(base64_decode($cached_with_no_metadata_keys));
    $required_revision_metadata_keys_no_bc = [];
    $this->assertEquals($required_revision_metadata_keys_no_bc, $entity_type->getRevisionMetadataKeys(FALSE));
    $required_revision_metadata_keys_with_bc = $required_revision_metadata_keys_no_bc + [
      'revision_user' => 'revision_user',
      'revision_created' => 'revision_created',
      'revision_log_message' => 'revision_log_message',
    ];
    $this->assertEquals($required_revision_metadata_keys_with_bc, $entity_type->getRevisionMetadataKeys(TRUE));

    // Ensure that cached entity types with only one required revision metadata
    // key will return only that one after a second required revision metadata
    // key has been added.
    // Contains one revision metadata key - revision_default which is also
    // contained in the property holding the required revision metadata keys,
    // the entity type id is "entity_test_mul_revlog".
    $cached_with_metadata_key_revision_default = 'Tzo4MjoiRHJ1cGFsXFRlc3RzXHN5c3RlbVxGdW5jdGlvbmFsXEVudGl0eVxVcGRhdGVcVGVzdFJldmlzaW9uTWV0YWRhdGFCY0xheWVyRW50aXR5VHlwZSI6Mzk6e3M6MjU6IgAqAHJldmlzaW9uX21ldGFkYXRhX2tleXMiO2E6MTp7czoxNjoicmV2aXNpb25fZGVmYXVsdCI7czoxNjoicmV2aXNpb25fZGVmYXVsdCI7fXM6MzE6IgAqAHJlcXVpcmVkUmV2aXNpb25NZXRhZGF0YUtleXMiO2E6MTp7czoxNjoicmV2aXNpb25fZGVmYXVsdCI7czoxNjoicmV2aXNpb25fZGVmYXVsdCI7fXM6MTU6IgAqAHN0YXRpY19jYWNoZSI7YjoxO3M6MTU6IgAqAHJlbmRlcl9jYWNoZSI7YjoxO3M6MTk6IgAqAHBlcnNpc3RlbnRfY2FjaGUiO2I6MTtzOjE0OiIAKgBlbnRpdHlfa2V5cyI7YTo1OntzOjg6InJldmlzaW9uIjtzOjA6IiI7czo2OiJidW5kbGUiO3M6MDoiIjtzOjg6Imxhbmdjb2RlIjtzOjA6IiI7czoxNjoiZGVmYXVsdF9sYW5nY29kZSI7czoxNjoiZGVmYXVsdF9sYW5nY29kZSI7czoyOToicmV2aXNpb25fdHJhbnNsYXRpb25fYWZmZWN0ZWQiO3M6Mjk6InJldmlzaW9uX3RyYW5zbGF0aW9uX2FmZmVjdGVkIjt9czo1OiIAKgBpZCI7czoyMjoiZW50aXR5X3Rlc3RfbXVsX3JldmxvZyI7czoxNjoiACoAb3JpZ2luYWxDbGFzcyI7TjtzOjExOiIAKgBoYW5kbGVycyI7YTozOntzOjY6ImFjY2VzcyI7czo0NToiRHJ1cGFsXENvcmVcRW50aXR5XEVudGl0eUFjY2Vzc0NvbnRyb2xIYW5kbGVyIjtzOjc6InN0b3JhZ2UiO3M6NDY6IkRydXBhbFxDb3JlXEVudGl0eVxTcWxcU3FsQ29udGVudEVudGl0eVN0b3JhZ2UiO3M6MTI6InZpZXdfYnVpbGRlciI7czozNjoiRHJ1cGFsXENvcmVcRW50aXR5XEVudGl0eVZpZXdCdWlsZGVyIjt9czoxOToiACoAYWRtaW5fcGVybWlzc2lvbiI7TjtzOjI1OiIAKgBwZXJtaXNzaW9uX2dyYW51bGFyaXR5IjtzOjExOiJlbnRpdHlfdHlwZSI7czo4OiIAKgBsaW5rcyI7YTowOnt9czoxNzoiACoAbGFiZWxfY2FsbGJhY2siO047czoyMToiACoAYnVuZGxlX2VudGl0eV90eXBlIjtOO3M6MTI6IgAqAGJ1bmRsZV9vZiI7TjtzOjE1OiIAKgBidW5kbGVfbGFiZWwiO047czoxMzoiACoAYmFzZV90YWJsZSI7TjtzOjIyOiIAKgByZXZpc2lvbl9kYXRhX3RhYmxlIjtOO3M6MTc6IgAqAHJldmlzaW9uX3RhYmxlIjtOO3M6MTM6IgAqAGRhdGFfdGFibGUiO047czoxNToiACoAdHJhbnNsYXRhYmxlIjtiOjA7czoxOToiACoAc2hvd19yZXZpc2lvbl91aSI7YjowO3M6ODoiACoAbGFiZWwiO3M6MDoiIjtzOjE5OiIAKgBsYWJlbF9jb2xsZWN0aW9uIjtzOjA6IiI7czoxNzoiACoAbGFiZWxfc2luZ3VsYXIiO3M6MDoiIjtzOjE1OiIAKgBsYWJlbF9wbHVyYWwiO3M6MDoiIjtzOjE0OiIAKgBsYWJlbF9jb3VudCI7YTowOnt9czoxNToiACoAdXJpX2NhbGxiYWNrIjtOO3M6ODoiACoAZ3JvdXAiO047czoxNDoiACoAZ3JvdXBfbGFiZWwiO047czoyMjoiACoAZmllbGRfdWlfYmFzZV9yb3V0ZSI7TjtzOjI2OiIAKgBjb21tb25fcmVmZXJlbmNlX3RhcmdldCI7YjowO3M6MjI6IgAqAGxpc3RfY2FjaGVfY29udGV4dHMiO2E6MDp7fXM6MTg6IgAqAGxpc3RfY2FjaGVfdGFncyI7YToxOntpOjA7czo5OiJ0ZXN0X2xpc3QiO31zOjE0OiIAKgBjb25zdHJhaW50cyI7YTowOnt9czoxMzoiACoAYWRkaXRpb25hbCI7YTowOnt9czo4OiIAKgBjbGFzcyI7TjtzOjExOiIAKgBwcm92aWRlciI7TjtzOjIwOiIAKgBzdHJpbmdUcmFuc2xhdGlvbiI7Tjt9';
    $entity_type = unserialize(base64_decode($cached_with_metadata_key_revision_default));
    $required_revision_metadata_keys_no_bc = [
      'revision_default' => 'revision_default',
    ];
    $this->assertEquals($required_revision_metadata_keys_no_bc, $entity_type->getRevisionMetadataKeys(FALSE));
    $required_revision_metadata_keys_with_bc = $required_revision_metadata_keys_no_bc + [
        'revision_user' => 'revision_user',
        'revision_created' => 'revision_created',
        'revision_log_message' => 'revision_log_message',
      ];
    $this->assertEquals($required_revision_metadata_keys_with_bc, $entity_type->getRevisionMetadataKeys(TRUE));

    // Ensure that newly instantiated entity types will return the two required
    // revision metadata keys.
    $entity_type = new TestRevisionMetadataBcLayerEntityType(['id' => 'test']);
    $required_revision_metadata_keys = [
      'revision_default' => 'revision_default',
      'second_required_key' => 'second_required_key',
    ];
    $this->assertEquals($required_revision_metadata_keys, $entity_type->getRevisionMetadataKeys(FALSE));

    // Load an entity type from the cache with no revision metadata keys in the
    // annotation.
    $entity_last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');
    $entity_type = $entity_last_installed_schema_repository->getLastInstalledDefinition('entity_test_mul_revlog');
    $revision_metadata_keys = [];
    $this->assertEquals($revision_metadata_keys, $entity_type->getRevisionMetadataKeys(FALSE));
    $revision_metadata_keys = [
      'revision_user' => 'revision_user',
      'revision_created' => 'revision_created',
      'revision_log_message' => 'revision_log_message',
    ];
    $this->assertEquals($revision_metadata_keys, $entity_type->getRevisionMetadataKeys(TRUE));

    // Load an entity type without using the cache with no revision metadata
    // keys in the annotation.
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_type_manager->useCaches(FALSE);
    $entity_type = $entity_type_manager->getDefinition('entity_test_mul_revlog');
    $revision_metadata_keys = [
      'revision_default' => 'revision_default',
    ];
    $this->assertEquals($revision_metadata_keys, $entity_type->getRevisionMetadataKeys(FALSE));
    $revision_metadata_keys = [
      'revision_user' => 'revision_user',
      'revision_created' => 'revision_created',
      'revision_log_message' => 'revision_log_message',
      'revision_default' => 'revision_default',
    ];
    $this->assertEquals($revision_metadata_keys, $entity_type->getRevisionMetadataKeys(TRUE));

    // Ensure that the BC layer will not be triggered if one of the required
    // revision metadata keys is defined in the annotation.
    $definition = [
      'id' => 'entity_test_mul_revlog',
      'revision_metadata_keys' => [
        'revision_default' => 'revision_default',
      ],
    ];
    $entity_type = new ContentEntityType($definition);
    $revision_metadata_keys = [
      'revision_default' => 'revision_default',
    ];
    $this->assertEquals($revision_metadata_keys, $entity_type->getRevisionMetadataKeys(TRUE));

    // Ensure that the BC layer will be triggered if no revision metadata keys
    // have been defined in the annotation.
    $definition = [
      'id' => 'entity_test_mul_revlog',
    ];
    $entity_type = new ContentEntityType($definition);
    $revision_metadata_keys = [
      'revision_default' => 'revision_default',
      'revision_user' => 'revision_user',
      'revision_created' => 'revision_created',
      'revision_log_message' => 'revision_log_message',
    ];
    $this->assertEquals($revision_metadata_keys, $entity_type->getRevisionMetadataKeys(TRUE));
  }

  /**
   * Tests that the revision metadata key BC layer was updated correctly.
   *
   * @expectedDeprecation The revision_user revision metadata key is not set for entity type: entity_test_mul_revlog See: https://www.drupal.org/node/2831499
   * @expectedDeprecation The revision_created revision metadata key is not set for entity type: entity_test_mul_revlog See: https://www.drupal.org/node/2831499
   * @expectedDeprecation The revision_log_message revision metadata key is not set for entity type: entity_test_mul_revlog See: https://www.drupal.org/node/2831499
   */
  public function testSystemUpdate8501() {
    $this->runUpdates();

    /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $definition_update_manager */
    $definition_update_manager = $this->container->get('entity.definition_update_manager');
    foreach (['block_content', 'node'] as $entity_type_id) {
      $installed_entity_type = $definition_update_manager->getEntityType($entity_type_id);
      $revision_metadata_keys = $installed_entity_type->get('revision_metadata_keys');
      $this->assertTrue(isset($revision_metadata_keys['revision_default']));
      $required_revision_metadata_keys = $installed_entity_type->get('requiredRevisionMetadataKeys');
      $this->assertTrue(isset($required_revision_metadata_keys['revision_default']));
    }
  }

}

/**
 * Test entity type class for adding new required revision metadata keys.
 */
class TestRevisionMetadataBcLayerEntityType extends ContentEntityType {

  /**
   * {@inheritdoc}
   */
  public function __construct($definition) {
    // Only new instances should provide the required revision metadata keys.
    // The cached instances should return only what already has been stored
    // under the property $revision_metadata_keys. The BC layer in
    // ::getRevisionMetadataKeys() has to detect if the revision metadata keys
    // have been provided by the entity type annotation, therefore we add keys
    // to the property $requiredRevisionMetadataKeys only if those keys aren't
    // set in the entity type annotation.
    if (!isset($definition['revision_metadata_keys']['second_required_key'])) {
      $this->requiredRevisionMetadataKeys['second_required_key'] = 'second_required_key';
    }
    parent::__construct($definition);
  }

}
