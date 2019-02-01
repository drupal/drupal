<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Site\Settings;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;

/**
 * Tests EntityDefinitionUpdateManager's fieldable entity update functionality.
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityDefinitionUpdateManager
 *
 * @group Entity
 */
class FieldableEntityDefinitionUpdateTest extends EntityKernelTestBase {

  use EntityDefinitionTestTrait;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The ID of the entity type used in this test.
   *
   * @var string
   */
  protected $entityTypeId = 'entity_test_update';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['content_translation', 'entity_test_update', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->database = $this->container->get('database');

    // The 'changed' field type has a special behavior because it updates itself
    // automatically if any of the other field values of an entity have been
    // updated, so add it to the entity type that is being tested in order to
    // provide test coverage for this special case.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the custom block was last edited.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);
    $this->state->set('entity_test_update.additional_base_field_definitions', $fields);

    $this->installEntitySchema($this->entityTypeId);
    $this->installEntitySchema('configurable_language');

    // Enable an additional language.
    ConfigurableLanguage::createFromLangcode('ro')->save();

    // Force the update function to convert one entity at a time.
    $settings = Settings::getAll();
    $settings['entity_update_batch_size'] = 1;
    new Settings($settings);
  }

  /**
   * @covers ::updateFieldableEntityType
   * @dataProvider providerTestFieldableEntityTypeUpdates
   */
  public function testFieldableEntityTypeUpdates($initial_rev, $initial_mul, $new_rev, $new_mul, $data_migration_supported) {
    // The 'entity_test_update' entity type is neither revisionable nor
    // translatable by default, so we need to get it into the initial testing
    // state. This also covers the "no existing data" scenario for fieldable
    // entity type updates.
    if ($initial_rev || $initial_mul) {
      $entity_type = $this->getUpdatedEntityTypeDefinition($initial_rev, $initial_mul);
      $field_storage_definitions = $this->getUpdatedFieldStorageDefinitions($initial_rev, $initial_mul);

      $this->entityDefinitionUpdateManager->updateFieldableEntityType($entity_type, $field_storage_definitions);
      $this->assertEntityTypeSchema($initial_rev, $initial_mul);
    }

    // Add a few entities so we can test the data copying step.
    $this->insertData($initial_rev, $initial_mul);

    $updated_entity_type = $this->getUpdatedEntityTypeDefinition($new_rev, $new_mul);
    $updated_field_storage_definitions = $this->getUpdatedFieldStorageDefinitions($new_rev, $new_mul);

    if (!$data_migration_supported) {
      $this->setExpectedException(EntityStorageException::class, 'Converting an entity type from revisionable to non-revisionable or from translatable to non-translatable is not supported.');
    }

    // Simulate a batch run since we are converting the entities one by one.
    $sandbox = [];
    do {
      $this->entityDefinitionUpdateManager->updateFieldableEntityType($updated_entity_type, $updated_field_storage_definitions, $sandbox);
    } while ($sandbox['#finished'] != 1);

    $this->assertEntityTypeSchema($new_rev, $new_mul);
    $this->assertEntityData($initial_rev, $initial_mul);

    // Check that we can still save new entities after the schema has been
    // updated.
    $this->insertData($new_rev, $new_mul);
  }

  /**
   * Data provider for testFieldableEntityTypeUpdates().
   */
  public function providerTestFieldableEntityTypeUpdates() {
    return [
      'no change' => [
        'initial_rev' => FALSE,
        'initial_mul' => FALSE,
        'new_rev' => FALSE,
        'new_mul' => FALSE,
        'data_migration_supported' => TRUE,
      ],
      'non_rev non_mul to rev non_mul' => [
        'initial_rev' => FALSE,
        'initial_mul' => FALSE,
        'new_rev' => TRUE,
        'new_mul' => FALSE,
        'data_migration_supported' => TRUE,
      ],
      'non_rev non_mul to rev mul' => [
        'initial_rev' => FALSE,
        'initial_mul' => FALSE,
        'new_rev' => TRUE,
        'new_mul' => TRUE,
        'data_migration_supported' => TRUE,
      ],
      'non_rev non_mul to non_rev mul' => [
        'initial_rev' => FALSE,
        'initial_mul' => FALSE,
        'new_rev' => FALSE,
        'new_mul' => TRUE,
        'data_migration_supported' => TRUE,
      ],
      'rev non_mul to non_rev non_mul' => [
        'initial_rev' => TRUE,
        'initial_mul' => FALSE,
        'new_rev' => FALSE,
        'new_mul' => FALSE,
        'data_migration_supported' => FALSE,
      ],
      'rev non_mul to non_rev mul' => [
        'initial_rev' => TRUE,
        'initial_mul' => FALSE,
        'new_rev' => FALSE,
        'new_mul' => TRUE,
        'data_migration_supported' => FALSE,
      ],
      'rev non_mul to rev mul' => [
        'initial_rev' => TRUE,
        'initial_mul' => FALSE,
        'new_rev' => TRUE,
        'new_mul' => TRUE,
        'data_migration_supported' => TRUE,
      ],
      'non_rev mul to non_rev non_mul' => [
        'initial_rev' => FALSE,
        'initial_mul' => TRUE,
        'new_rev' => FALSE,
        'new_mul' => FALSE,
        'data_migration_supported' => FALSE,
      ],
      'non_rev mul to rev non_mul' => [
        'initial_rev' => FALSE,
        'initial_mul' => TRUE,
        'new_rev' => TRUE,
        'new_mul' => FALSE,
        'data_migration_supported' => FALSE,
      ],
      'non_rev mul to rev mul' => [
        'initial_rev' => FALSE,
        'initial_mul' => TRUE,
        'new_rev' => TRUE,
        'new_mul' => TRUE,
        'data_migration_supported' => TRUE,
      ],
      'rev mul to non_rev non_mul' => [
        'initial_rev' => TRUE,
        'initial_mul' => TRUE,
        'new_rev' => FALSE,
        'new_mul' => FALSE,
        'data_migration_supported' => FALSE,
      ],
      'rev mul to rev non_mul' => [
        'initial_rev' => TRUE,
        'initial_mul' => TRUE,
        'new_rev' => TRUE,
        'new_mul' => FALSE,
        'data_migration_supported' => FALSE,
      ],
      'rev mul to non_rev mul' => [
        'initial_rev' => TRUE,
        'initial_mul' => TRUE,
        'new_rev' => FALSE,
        'new_mul' => TRUE,
        'data_migration_supported' => FALSE,
      ],
    ];
  }

  /**
   * Generates test entities for the 'entity_test_update' entity type.
   *
   * @param bool $revisionable
   *   Whether the entity type is revisionable or not.
   * @param bool $translatable
   *   Whether the entity type is translatable or not.
   */
  protected function insertData($revisionable, $translatable) {
    // Add three test entities in order to make the "data copy" step run at
    // least three times.
    /** @var \Drupal\Core\Entity\TranslatableRevisionableStorageInterface|\Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($this->entityTypeId);
    $next_id = $storage->getQuery()->count()->execute() + 1;

    // Create test entities with two translations and two revisions.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    for ($i = $next_id; $i <= $next_id + 2; $i++) {
      $entity = $storage->create([
        'id' => $i,
        'name' => 'test entity - ' . $i . ' - en',
        'test_multiple_properties_multiple_values' => [
          'value1' => 'dedicated table - ' . $i . ' - value 1 - en',
          'value2' => 'dedicated table - ' . $i . ' - value 2 - en',
        ],
      ]);
      $entity->save();

      if ($translatable) {
        $translation = $entity->addTranslation('ro', [
          'name' => 'test entity - ' . $i . ' - ro',
          'test_multiple_properties_multiple_values' => [
            'value1' => 'dedicated table - ' . $i . ' - value 1 - ro',
            'value2' => 'dedicated table - ' . $i . ' - value 2 - ro',
          ],
        ]);
        $translation->save();
      }

      if ($revisionable) {
        // Create a new pending revision.
        $revision_2 = $storage->createRevision($entity, FALSE);
        $revision_2->name = 'test entity - ' . $i . ' - en - rev2';
        $revision_2->test_multiple_properties_multiple_values->value1 = 'dedicated table - ' . $i . ' - value 1 - en - rev2';
        $revision_2->test_multiple_properties_multiple_values->value2 = 'dedicated table - ' . $i . ' - value 2 - en - rev2';
        $revision_2->save();

        if ($translatable) {
          $revision_2_translation = $storage->createRevision($entity->getTranslation('ro'), FALSE);
          $revision_2_translation->name = 'test entity - ' . $i . ' - ro - rev2';
          $revision_2_translation->test_multiple_properties_multiple_values->value1 = 'dedicated table - ' . $i . ' - value 1 - ro - rev2';
          $revision_2_translation->test_multiple_properties_multiple_values->value2 = 'dedicated table - ' . $i . ' - value 2 - ro - rev2';
          $revision_2_translation->save();
        }
      }
    }
  }

  /**
   * Asserts test entity data after a fieldable entity type update.
   *
   * @param bool $revisionable
   *   Whether the entity type was revisionable prior to the update.
   * @param bool $translatable
   *   Whether the entity type was translatable prior to the update.
   */
  protected function assertEntityData($revisionable, $translatable) {
    $entities = $this->entityTypeManager->getStorage($this->entityTypeId)->loadMultiple();
    $this->assertCount(3, $entities);
    foreach ($entities as $entity_id => $entity) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $this->assertEquals("test entity - {$entity->id()} - en", $entity->label());
      $this->assertEquals("dedicated table - {$entity->id()} - value 1 - en", $entity->test_multiple_properties_multiple_values->value1);
      $this->assertEquals("dedicated table - {$entity->id()} - value 2 - en", $entity->test_multiple_properties_multiple_values->value2);

      if ($translatable) {
        $translation = $entity->getTranslation('ro');
        $this->assertEquals("test entity - {$entity->id()} - ro", $translation->label());
        $this->assertEquals("dedicated table - {$translation->id()} - value 1 - ro", $translation->test_multiple_properties_multiple_values->value1);
        $this->assertEquals("dedicated table - {$translation->id()} - value 2 - ro", $translation->test_multiple_properties_multiple_values->value2);
      }
    }

    if ($revisionable) {
      $revisions_result = $this->entityTypeManager->getStorage($this->entityTypeId)->getQuery()->allRevisions()->execute();
      $revisions = $this->entityTypeManager->getStorage($this->entityTypeId)->loadMultipleRevisions(array_keys($revisions_result));
      $this->assertCount(6, $revisions);

      foreach ($revisions as $revision) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
        $revision_label = $revision->isDefaultRevision() ? NULL : ' - rev2';
        $this->assertEquals("test entity - {$revision->id()} - en{$revision_label}", $revision->label());
        $this->assertEquals("dedicated table - {$revision->id()} - value 1 - en{$revision_label}", $revision->test_multiple_properties_multiple_values->value1);
        $this->assertEquals("dedicated table - {$revision->id()} - value 2 - en{$revision_label}", $revision->test_multiple_properties_multiple_values->value2);

        if ($translatable) {
          $translation = $revision->getTranslation('ro');
          $this->assertEquals("test entity - {$translation->id()} - ro{$revision_label}", $translation->label());
          $this->assertEquals("dedicated table - {$translation->id()} - value 1 - ro{$revision_label}", $translation->test_multiple_properties_multiple_values->value1);
          $this->assertEquals("dedicated table - {$translation->id()} - value 2 - ro{$revision_label}", $translation->test_multiple_properties_multiple_values->value2);
        }
      }
    }
  }

  /**
   * Asserts revisionable and/or translatable characteristics of an entity type.
   *
   * @param bool $revisionable
   *   Whether the entity type is revisionable or not.
   * @param bool $translatable
   *   Whether the entity type is translatable or not.
   */
  protected function assertEntityTypeSchema($revisionable, $translatable) {
    if ($revisionable && $translatable) {
      $this->assertRevisionableAndTranslatable();
    }
    elseif ($revisionable) {
      $this->assertRevisionable();
    }
    elseif ($translatable) {
      $this->assertTranslatable();
    }
    else {
      $this->assertNonRevisionableAndNonTranslatable();
    }
  }

  /**
   * Asserts the revisionable characteristics of an entity type.
   */
  protected function assertRevisionable() {
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $entity_type = $this->entityDefinitionUpdateManager->getEntityType($this->entityTypeId);
    $this->assertTrue($entity_type->isRevisionable());

    // Check that the required field definitions of a revisionable entity type
    // exists and are stored in the correct tables.
    $revision_key = $entity_type->getKey('revision');
    $revision_default_key = $entity_type->getRevisionMetadataKey('revision_default');
    $revision_field = $this->entityDefinitionUpdateManager->getFieldStorageDefinition($revision_key, $entity_type->id());
    $revision_default_field = $this->entityDefinitionUpdateManager->getFieldStorageDefinition($revision_default_key, $entity_type->id());
    $this->assertNotNull($revision_field);
    $this->assertNotNull($revision_default_field);

    $database_schema = $this->database->schema();
    $base_table = $entity_type->getBaseTable();
    $revision_table = $entity_type->getRevisionTable();
    $this->assertTrue($database_schema->tableExists($revision_table));

    $this->assertTrue($database_schema->fieldExists($base_table, $revision_key));
    $this->assertTrue($database_schema->fieldExists($revision_table, $revision_key));

    $this->assertFalse($database_schema->fieldExists($base_table, $revision_default_key));
    $this->assertTrue($database_schema->fieldExists($revision_table, $revision_default_key));

    // Also check the revision metadata keys, if they exist.
    foreach (['revision_log_message', 'revision_user', 'revision_created'] as $key) {
      if ($revision_metadata_key = $entity_type->getRevisionMetadataKey($key)) {
        $revision_metadata_field = $this->entityDefinitionUpdateManager->getFieldStorageDefinition($revision_metadata_key, $entity_type->id());
        $this->assertNotNull($revision_metadata_field);
        $this->assertFalse($database_schema->fieldExists($base_table, $revision_metadata_key));
        $this->assertTrue($database_schema->fieldExists($revision_table, $revision_metadata_key));
      }
    }
  }

  /**
   * Asserts the translatable characteristics of an entity type.
   */
  protected function assertTranslatable() {
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $entity_type = $this->entityDefinitionUpdateManager->getEntityType($this->entityTypeId);
    $this->assertTrue($entity_type->isTranslatable());

    // Check that the required field definitions of a translatable entity type
    // exists and are stored in the correct tables.
    $langcode_key = $entity_type->getKey('langcode');
    $default_langcode_key = $entity_type->getKey('default_langcode');
    $langcode_field = $this->entityDefinitionUpdateManager->getFieldStorageDefinition($langcode_key, $entity_type->id());
    $default_langcode_field = $this->entityDefinitionUpdateManager->getFieldStorageDefinition($default_langcode_key, $entity_type->id());
    $this->assertNotNull($langcode_field);
    $this->assertNotNull($default_langcode_field);

    $database_schema = $this->database->schema();
    $base_table = $entity_type->getBaseTable();
    $data_table = $entity_type->getDataTable();
    $this->assertTrue($database_schema->tableExists($data_table));

    $this->assertTrue($database_schema->fieldExists($base_table, $langcode_key));
    $this->assertTrue($database_schema->fieldExists($data_table, $langcode_key));

    $this->assertFalse($database_schema->fieldExists($base_table, $default_langcode_key));
    $this->assertTrue($database_schema->fieldExists($data_table, $default_langcode_key));
  }

  /**
   * Asserts the revisionable / translatable characteristics of an entity type.
   */
  protected function assertRevisionableAndTranslatable() {
    $this->assertRevisionable();
    $this->assertTranslatable();

    // Check that the required field definitions of a revisionable and
    // translatable entity type exists and are stored in the correct tables.
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $entity_type = $this->entityDefinitionUpdateManager->getEntityType($this->entityTypeId);
    $langcode_key = $entity_type->getKey('langcode');
    $revision_translation_affected_key = $entity_type->getKey('revision_translation_affected');
    $revision_translation_affected_field = $this->entityDefinitionUpdateManager->getFieldStorageDefinition($revision_translation_affected_key, $entity_type->id());
    $this->assertNotNull($revision_translation_affected_field);

    $database_schema = $this->database->schema();
    $base_table = $entity_type->getBaseTable();
    $data_table = $entity_type->getDataTable();
    $revision_table = $entity_type->getRevisionTable();
    $revision_data_table = $entity_type->getRevisionDataTable();
    $this->assertTrue($database_schema->tableExists($revision_data_table));

    $this->assertTrue($database_schema->fieldExists($base_table, $langcode_key));
    $this->assertTrue($database_schema->fieldExists($data_table, $langcode_key));
    $this->assertTrue($database_schema->fieldExists($revision_table, $langcode_key));
    $this->assertTrue($database_schema->fieldExists($revision_data_table, $langcode_key));

    $this->assertFalse($database_schema->fieldExists($base_table, $revision_translation_affected_key));
    $this->assertFalse($database_schema->fieldExists($revision_table, $revision_translation_affected_key));
    $this->assertTrue($database_schema->fieldExists($data_table, $revision_translation_affected_key));
    $this->assertTrue($database_schema->fieldExists($revision_data_table, $revision_translation_affected_key));

    // Also check the revision metadata keys, if they exist.
    foreach (['revision_log_message', 'revision_user', 'revision_created'] as $key) {
      if ($revision_metadata_key = $entity_type->getRevisionMetadataKey($key)) {
        $revision_metadata_field = $this->entityDefinitionUpdateManager->getFieldStorageDefinition($revision_metadata_key, $entity_type->id());
        $this->assertNotNull($revision_metadata_field);
        $this->assertFalse($database_schema->fieldExists($base_table, $revision_metadata_key));
        $this->assertTrue($database_schema->fieldExists($revision_table, $revision_metadata_key));
        $this->assertFalse($database_schema->fieldExists($data_table, $revision_metadata_key));
        $this->assertFalse($database_schema->fieldExists($revision_data_table, $revision_metadata_key));
      }
    }
  }

  /**
   * Asserts that an entity type is neither revisionable nor translatable.
   */
  protected function assertNonRevisionableAndNonTranslatable() {
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $entity_type = $this->entityDefinitionUpdateManager->getEntityType($this->entityTypeId);
    $this->assertFalse($entity_type->isRevisionable());
    $this->assertFalse($entity_type->isTranslatable());

    $database_schema = $this->database->schema();
    $this->assertTrue($database_schema->tableExists($entity_type->getBaseTable()));
    $this->assertFalse($database_schema->tableExists($entity_type->getDataTable()));
    $this->assertFalse($database_schema->tableExists($entity_type->getRevisionTable()));
    $this->assertFalse($database_schema->tableExists($entity_type->getRevisionDataTable()));
  }

}
