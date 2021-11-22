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
   * The last installed schema repository service.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $lastInstalledSchemaRepository;

  /**
   * The key-value collection for tracking installed storage schema.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $installedStorageSchema;

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
   * An array of entities are created during the test.
   *
   * @var \Drupal\entity_test_update\Entity\EntityTestUpdate[]
   */
  protected $testEntities = [];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'content_translation',
    'entity_test_update',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');
    $this->lastInstalledSchemaRepository = $this->container->get('entity.last_installed_schema.repository');
    $this->installedStorageSchema = $this->container->get('keyvalue')->get('entity.storage_schema.sql');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->database = $this->container->get('database');

    // Add a non-revisionable bundle field to test revision field table
    // handling.
    $this->addBundleField('string', FALSE, TRUE);

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
      $this->expectException(EntityStorageException::class);
      $this->expectExceptionMessage('Converting an entity type from revisionable to non-revisionable or from translatable to non-translatable is not supported.');
    }

    // Check that existing data can be retrieved from the storage before the
    // entity schema is updated.
    if ($data_migration_supported) {
      $this->assertEntityData($initial_rev, $initial_mul);
    }

    // Enable the creation of a new base field during a fieldable entity type
    // update.
    $this->state->set('entity_test_update.install_new_base_field_during_update', TRUE);

    // Simulate a batch run since we are converting the entities one by one.
    $sandbox = [];
    do {
      $this->entityDefinitionUpdateManager->updateFieldableEntityType($updated_entity_type, $updated_field_storage_definitions, $sandbox);
    } while ($sandbox['#finished'] != 1);

    $this->assertEntityTypeSchema($new_rev, $new_mul, TRUE);
    $this->assertEntityData($initial_rev, $initial_mul);

    $change_list = $this->entityDefinitionUpdateManager->getChangeList();
    $this->assertArrayNotHasKey('entity_test_update', $change_list, "There are no remaining updates for the 'entity_test_update' entity type.");

    // Check that we can still save new entities after the schema has been
    // updated.
    $this->insertData($new_rev, $new_mul);

    // Check that the backup tables have been kept in place.
    $this->assertBackupTables();
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
    $next_id = $storage->getQuery()->accessCheck(FALSE)->count()->execute() + 1;

    // Create test entities with two translations and two revisions.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    for ($i = $next_id; $i <= $next_id + 2; $i++) {
      $entity = $storage->create([
        'id' => $i,
        'type' => 'test_bundle',
        'name' => 'test entity - ' . $i . ' - en',
        'new_bundle_field' => 'bundle field - ' . $i . ' - en',
        'test_multiple_properties' => [
          'value1' => 'shared table - ' . $i . ' - value 1 - en',
          'value2' => 'shared table - ' . $i . ' - value 2 - en',
        ],
        'test_multiple_properties_multiple_values' => [
          [
            'value1' => 'dedicated table - ' . $i . ' - delta 0 - value 1 - en',
            'value2' => 'dedicated table - ' . $i . ' - delta 0 - value 2 - en',
          ],
          [
            'value1' => 'dedicated table - ' . $i . ' - delta 1 - value 1 - en',
            'value2' => 'dedicated table - ' . $i . ' - delta 1 - value 2 - en',
          ],
        ],
      ]);
      $entity->save();

      if ($translatable) {
        $translation = $entity->addTranslation('ro', [
          'name' => 'test entity - ' . $i . ' - ro',
          'new_bundle_field' => 'bundle field - ' . $i . ' - ro',
          'test_multiple_properties' => [
            'value1' => 'shared table - ' . $i . ' - value 1 - ro',
            'value2' => 'shared table - ' . $i . ' - value 2 - ro',
          ],
          'test_multiple_properties_multiple_values' => [
            [
              'value1' => 'dedicated table - ' . $i . ' - delta 0 - value 1 - ro',
              'value2' => 'dedicated table - ' . $i . ' - delta 0 - value 2 - ro',
            ],
            [
              'value1' => 'dedicated table - ' . $i . ' - delta 1 - value 1 - ro',
              'value2' => 'dedicated table - ' . $i . ' - delta 1 - value 2 - ro',
            ],
          ],
        ]);
        $translation->save();
      }
      $this->testEntities[$entity->id()] = $entity;

      if ($revisionable) {
        // Create a new pending revision.
        $revision_2 = $storage->createRevision($entity, FALSE);
        $revision_2->name = 'test entity - ' . $i . ' - en - rev2';
        $revision_2->new_bundle_field = 'bundle field - ' . $i . ' - en - rev2';
        $revision_2->test_multiple_properties->value1 = 'shared table - ' . $i . ' - value 1 - en - rev2';
        $revision_2->test_multiple_properties->value2 = 'shared table - ' . $i . ' - value 2 - en - rev2';
        $revision_2->test_multiple_properties_multiple_values[0]->value1 = 'dedicated table - ' . $i . ' - delta 0 - value 1 - en - rev2';
        $revision_2->test_multiple_properties_multiple_values[0]->value2 = 'dedicated table - ' . $i . ' - delta 0 - value 2 - en - rev2';
        $revision_2->test_multiple_properties_multiple_values[1]->value1 = 'dedicated table - ' . $i . ' - delta 1 - value 1 - en - rev2';
        $revision_2->test_multiple_properties_multiple_values[1]->value2 = 'dedicated table - ' . $i . ' - delta 1 - value 2 - en - rev2';
        $revision_2->save();

        if ($translatable) {
          $revision_2_translation = $storage->createRevision($entity->getTranslation('ro'), FALSE);
          $revision_2_translation->name = 'test entity - ' . $i . ' - ro - rev2';
          $revision_2->new_bundle_field = 'bundle field - ' . $i . ' - ro - rev2';
          $revision_2->test_multiple_properties->value1 = 'shared table - ' . $i . ' - value 1 - ro - rev2';
          $revision_2->test_multiple_properties->value2 = 'shared table - ' . $i . ' - value 2 - ro - rev2';
          $revision_2_translation->test_multiple_properties_multiple_values[0]->value1 = 'dedicated table - ' . $i . ' - delta 0 - value 1 - ro - rev2';
          $revision_2_translation->test_multiple_properties_multiple_values[0]->value2 = 'dedicated table - ' . $i . ' - delta 0 - value 2 - ro - rev2';
          $revision_2_translation->test_multiple_properties_multiple_values[1]->value1 = 'dedicated table - ' . $i . ' - delta 1 - value 1 - ro - rev2';
          $revision_2_translation->test_multiple_properties_multiple_values[1]->value2 = 'dedicated table - ' . $i . ' - delta 1 - value 2 - ro - rev2';
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
   *
   * @internal
   */
  protected function assertEntityData(bool $revisionable, bool $translatable): void {
    $entities = $this->entityTypeManager->getStorage($this->entityTypeId)->loadMultiple();
    $this->assertCount(3, $entities);
    foreach ($entities as $entity_id => $entity) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $this->assertEquals("test entity - {$entity->id()} - en", $entity->label());
      $this->assertEquals("bundle field - {$entity->id()} - en", $entity->new_bundle_field->value);
      $this->assertEquals("shared table - {$entity->id()} - value 1 - en", $entity->test_multiple_properties->value1);
      $this->assertEquals("shared table - {$entity->id()} - value 2 - en", $entity->test_multiple_properties->value2);
      $this->assertEquals("dedicated table - {$entity->id()} - delta 0 - value 1 - en", $entity->test_multiple_properties_multiple_values[0]->value1);
      $this->assertEquals("dedicated table - {$entity->id()} - delta 0 - value 2 - en", $entity->test_multiple_properties_multiple_values[0]->value2);
      $this->assertEquals("dedicated table - {$entity->id()} - delta 1 - value 1 - en", $entity->test_multiple_properties_multiple_values[1]->value1);
      $this->assertEquals("dedicated table - {$entity->id()} - delta 1 - value 2 - en", $entity->test_multiple_properties_multiple_values[1]->value2);

      if ($translatable) {
        $translation = $entity->getTranslation('ro');
        $this->assertEquals("test entity - {$translation->id()} - ro", $translation->label());
        $this->assertEquals("bundle field - {$entity->id()} - ro", $translation->new_bundle_field->value);
        $this->assertEquals("shared table - {$translation->id()} - value 1 - ro", $translation->test_multiple_properties->value1);
        $this->assertEquals("shared table - {$translation->id()} - value 2 - ro", $translation->test_multiple_properties->value2);
        $this->assertEquals("dedicated table - {$translation->id()} - delta 0 - value 1 - ro", $translation->test_multiple_properties_multiple_values[0]->value1);
        $this->assertEquals("dedicated table - {$translation->id()} - delta 0 - value 2 - ro", $translation->test_multiple_properties_multiple_values[0]->value2);
        $this->assertEquals("dedicated table - {$translation->id()} - delta 1 - value 1 - ro", $translation->test_multiple_properties_multiple_values[1]->value1);
        $this->assertEquals("dedicated table - {$translation->id()} - delta 1 - value 2 - ro", $translation->test_multiple_properties_multiple_values[1]->value2);
      }
    }

    if ($revisionable) {
      $revisions_result = $this->entityTypeManager
        ->getStorage($this->entityTypeId)
        ->getQuery()
        ->accessCheck(FALSE)
        ->allRevisions()
        ->execute();
      $revisions = $this->entityTypeManager->getStorage($this->entityTypeId)->loadMultipleRevisions(array_keys($revisions_result));
      $this->assertCount(6, $revisions);

      foreach ($revisions as $revision) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
        $revision_label = $revision->isDefaultRevision() ? NULL : ' - rev2';
        $this->assertEquals("test entity - {$revision->id()} - en{$revision_label}", $revision->label());
        $this->assertEquals("bundle field - {$revision->id()} - en{$revision_label}", $revision->new_bundle_field->value);
        $this->assertEquals("shared table - {$revision->id()} - value 1 - en{$revision_label}", $revision->test_multiple_properties->value1);
        $this->assertEquals("shared table - {$revision->id()} - value 2 - en{$revision_label}", $revision->test_multiple_properties->value2);
        $this->assertEquals("dedicated table - {$revision->id()} - delta 0 - value 1 - en{$revision_label}", $revision->test_multiple_properties_multiple_values[0]->value1);
        $this->assertEquals("dedicated table - {$revision->id()} - delta 0 - value 2 - en{$revision_label}", $revision->test_multiple_properties_multiple_values[0]->value2);
        $this->assertEquals("dedicated table - {$revision->id()} - delta 1 - value 1 - en{$revision_label}", $revision->test_multiple_properties_multiple_values[1]->value1);
        $this->assertEquals("dedicated table - {$revision->id()} - delta 1 - value 2 - en{$revision_label}", $revision->test_multiple_properties_multiple_values[1]->value2);

        if ($translatable) {
          $translation = $revision->getTranslation('ro');
          $this->assertEquals("test entity - {$translation->id()} - ro{$revision_label}", $translation->label());
          $this->assertEquals("bundle field - {$entity->id()} - ro{$revision_label}", $translation->new_bundle_field->value);
          $this->assertEquals("shared table - {$revision->id()} - value 1 - ro{$revision_label}", $translation->test_multiple_properties->value1);
          $this->assertEquals("shared table - {$revision->id()} - value 2 - ro{$revision_label}", $translation->test_multiple_properties->value2);
          $this->assertEquals("dedicated table - {$translation->id()} - delta 0 - value 1 - ro{$revision_label}", $translation->test_multiple_properties_multiple_values[0]->value1);
          $this->assertEquals("dedicated table - {$translation->id()} - delta 0 - value 2 - ro{$revision_label}", $translation->test_multiple_properties_multiple_values[0]->value2);
          $this->assertEquals("dedicated table - {$translation->id()} - delta 1 - value 1 - ro{$revision_label}", $translation->test_multiple_properties_multiple_values[1]->value1);
          $this->assertEquals("dedicated table - {$translation->id()} - delta 1 - value 2 - ro{$revision_label}", $translation->test_multiple_properties_multiple_values[1]->value2);
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
   * @param bool $new_base_field
   *   (optional) Whether a new base field was added as part of the update.
   *   Defaults to FALSE.
   *
   * @internal
   */
  protected function assertEntityTypeSchema(bool $revisionable, bool $translatable, bool $new_base_field = FALSE): void {
    // Check whether the 'new_base_field' field has been installed correctly.
    $field_storage_definition = $this->entityDefinitionUpdateManager->getFieldStorageDefinition('new_base_field', $this->entityTypeId);
    if ($new_base_field) {
      $this->assertNotNull($field_storage_definition);
    }
    else {
      $this->assertNull($field_storage_definition);
    }

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

    $this->assertBundleFieldSchema($revisionable);
  }

  /**
   * Asserts the revisionable characteristics of an entity type.
   *
   * @internal
   */
  protected function assertRevisionable(): void {
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
   *
   * @internal
   */
  protected function assertTranslatable(): void {
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
   *
   * @internal
   */
  protected function assertRevisionableAndTranslatable(): void {
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
   *
   * @internal
   */
  protected function assertNonRevisionableAndNonTranslatable(): void {
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

  /**
   * Asserts that the bundle field schema is correct.
   *
   * @param bool $revisionable
   *   Whether the entity type is revisionable or not.
   *
   * @internal
   */
  protected function assertBundleFieldSchema(bool $revisionable): void {
    $entity_type_id = 'entity_test_update';
    $field_storage_definition = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id)['new_bundle_field'];
    $database_schema = $this->database->schema();
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->getTableMapping();
    $this->assertTrue($database_schema->tableExists($table_mapping->getDedicatedDataTableName($field_storage_definition)));
    if ($revisionable) {
      $this->assertTrue($database_schema->tableExists($table_mapping->getDedicatedRevisionTableName($field_storage_definition)));
    }
  }

  /**
   * Asserts that the backup tables have been kept after a successful update.
   *
   * @internal
   */
  protected function assertBackupTables(): void {
    $backups = \Drupal::keyValue('entity.update_backup')->getAll();
    $backup = reset($backups);

    $schema = $this->database->schema();
    foreach ($backup['table_mapping']->getTableNames() as $table_name) {
      $this->assertTrue($schema->tableExists($table_name));
    }
  }

  /**
   * Tests that a failed entity schema update preserves the existing data.
   */
  public function testFieldableEntityTypeUpdatesErrorHandling() {
    $schema = $this->database->schema();

    // First, convert the entity type to be translatable for better coverage and
    // insert some initial data.
    $entity_type = $this->getUpdatedEntityTypeDefinition(FALSE, TRUE);
    $field_storage_definitions = $this->getUpdatedFieldStorageDefinitions(FALSE, TRUE);
    $this->entityDefinitionUpdateManager->updateFieldableEntityType($entity_type, $field_storage_definitions);
    $this->assertEntityTypeSchema(FALSE, TRUE);
    $this->insertData(FALSE, TRUE);

    $tables = $schema->findTables('old_%');
    $this->assertCount(4, $tables);
    foreach ($tables as $table) {
      $schema->dropTable($table);
    }

    $original_entity_type = $this->lastInstalledSchemaRepository->getLastInstalledDefinition('entity_test_update');
    $original_storage_definitions = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions('entity_test_update');

    $original_entity_schema_data = $this->installedStorageSchema->get('entity_test_update.entity_schema_data', []);
    $original_field_schema_data = [];
    foreach ($original_storage_definitions as $storage_definition) {
      $original_field_schema_data[$storage_definition->getName()] = $this->installedStorageSchema->get('entity_test_update.field_schema_data.' . $storage_definition->getName(), []);
    }

    // Check that entity type is not revisionable prior to running the update
    // process.
    $this->assertFalse($entity_type->isRevisionable());

    // Make the update throw an exception during the entity save process.
    \Drupal::state()->set('entity_test_update.throw_exception', TRUE);
    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('The entity update process failed while processing the entity type entity_test_update, ID: 1.');

    try {
      $updated_entity_type = $this->getUpdatedEntityTypeDefinition(TRUE, TRUE);
      $updated_field_storage_definitions = $this->getUpdatedFieldStorageDefinitions(TRUE, TRUE);

      // Simulate a batch run since we are converting the entities one by one.
      $sandbox = [];
      do {
        $this->entityDefinitionUpdateManager->updateFieldableEntityType($updated_entity_type, $updated_field_storage_definitions, $sandbox);
      } while ($sandbox['#finished'] != 1);
    }
    catch (EntityStorageException $e) {
      throw $e;
    }
    // Allow other tests to be performed after the exception has been thrown.
    finally {
      $this->assertSame('Peekaboo!', $e->getPrevious()->getMessage());

      // Check that the last installed entity type definition is kept as
      // non-revisionable.
      $new_entity_type = $this->lastInstalledSchemaRepository->getLastInstalledDefinition('entity_test_update');
      $this->assertFalse($new_entity_type->isRevisionable(), 'The entity type is kept unchanged.');

      // Check that the last installed field storage definitions did not change by
      // looking at the 'langcode' field, which is updated automatically.
      $new_storage_definitions = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions('entity_test_update');
      $langcode_key = $original_entity_type->getKey('langcode');
      $this->assertEquals($original_storage_definitions[$langcode_key]->isRevisionable(), $new_storage_definitions[$langcode_key]->isRevisionable(), "The 'langcode' field is kept unchanged.");

      /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('entity_test_update');
      $table_mapping = $storage->getTableMapping();

      // Check that installed storage schema did not change.
      $new_entity_schema_data = $this->installedStorageSchema->get('entity_test_update.entity_schema_data', []);
      $this->assertEquals($original_entity_schema_data, $new_entity_schema_data);

      foreach ($new_storage_definitions as $storage_definition) {
        $new_field_schema_data[$storage_definition->getName()] = $this->installedStorageSchema->get('entity_test_update.field_schema_data.' . $storage_definition->getName(), []);
      }
      $this->assertEquals($original_field_schema_data, $new_field_schema_data);

      // Check that temporary tables have been removed.
      $tables = $schema->findTables('tmp_%');
      $this->assertCount(0, $tables);

      $current_table_names = $storage->getCustomTableMapping($original_entity_type, $original_storage_definitions)->getTableNames();
      foreach ($current_table_names as $table_name) {
        $this->assertTrue($schema->tableExists($table_name));
      }

      // Check that backup tables do not exist anymore, since they were
      // restored/renamed.
      $tables = $schema->findTables('old_%');
      $this->assertCount(0, $tables);

      // Check that the original tables still exist and their data is intact.
      $this->assertTrue($schema->tableExists('entity_test_update'));
      $this->assertTrue($schema->tableExists('entity_test_update_data'));

      // Check that the revision tables have not been created.
      $this->assertFalse($schema->tableExists('entity_test_update_revision'));
      $this->assertFalse($schema->tableExists('entity_test_update_revision_data'));

      $base_table_count = $this->database->select('entity_test_update')
        ->countQuery()
        ->execute()
        ->fetchField();
      $this->assertEquals(3, $base_table_count);

      $data_table_count = $this->database->select('entity_test_update_data')
        ->countQuery()
        ->execute()
        ->fetchField();
      // There are two records for each entity, one for English and one for
      // Romanian.
      $this->assertEquals(6, $data_table_count);

      $base_table_row = $this->database->select('entity_test_update')
        ->fields('entity_test_update')
        ->condition('id', 1, '=')
        ->condition('langcode', 'en', '=')
        ->execute()
        ->fetchAllAssoc('id');
      $this->assertEquals($this->testEntities[1]->uuid(), $base_table_row[1]->uuid);

      $data_table_row = $this->database->select('entity_test_update_data')
        ->fields('entity_test_update_data')
        ->condition('id', 1, '=')
        ->condition('langcode', 'en', '=')
        ->execute()
        ->fetchAllAssoc('id');
      $this->assertEquals('test entity - 1 - en', $data_table_row[1]->name);
      $this->assertEquals('shared table - 1 - value 1 - en', $data_table_row[1]->test_multiple_properties__value1);
      $this->assertEquals('shared table - 1 - value 2 - en', $data_table_row[1]->test_multiple_properties__value2);

      $data_table_row = $this->database->select('entity_test_update_data')
        ->fields('entity_test_update_data')
        ->condition('id', 1, '=')
        ->condition('langcode', 'ro', '=')
        ->execute()
        ->fetchAllAssoc('id');
      $this->assertEquals('test entity - 1 - ro', $data_table_row[1]->name);
      $this->assertEquals('shared table - 1 - value 1 - ro', $data_table_row[1]->test_multiple_properties__value1);
      $this->assertEquals('shared table - 1 - value 2 - ro', $data_table_row[1]->test_multiple_properties__value2);

      $dedicated_table_name = $table_mapping->getFieldTableName('test_multiple_properties_multiple_values');
      $dedicated_table_row = $this->database->select($dedicated_table_name)
        ->fields($dedicated_table_name)
        ->condition('entity_id', 1, '=')
        ->condition('langcode', 'en', '=')
        ->execute()
        ->fetchAllAssoc('delta');
      $this->assertEquals('dedicated table - 1 - delta 0 - value 1 - en', $dedicated_table_row[0]->test_multiple_properties_multiple_values_value1);
      $this->assertEquals('dedicated table - 1 - delta 0 - value 2 - en', $dedicated_table_row[0]->test_multiple_properties_multiple_values_value2);
      $this->assertEquals('dedicated table - 1 - delta 1 - value 1 - en', $dedicated_table_row[1]->test_multiple_properties_multiple_values_value1);
      $this->assertEquals('dedicated table - 1 - delta 1 - value 2 - en', $dedicated_table_row[1]->test_multiple_properties_multiple_values_value2);

      $dedicated_table_row = $this->database->select($dedicated_table_name)
        ->fields($dedicated_table_name)
        ->condition('entity_id', 1, '=')
        ->condition('langcode', 'ro', '=')
        ->execute()
        ->fetchAllAssoc('delta');
      $this->assertEquals('dedicated table - 1 - delta 0 - value 1 - ro', $dedicated_table_row[0]->test_multiple_properties_multiple_values_value1);
      $this->assertEquals('dedicated table - 1 - delta 0 - value 2 - ro', $dedicated_table_row[0]->test_multiple_properties_multiple_values_value2);
      $this->assertEquals('dedicated table - 1 - delta 1 - value 1 - ro', $dedicated_table_row[1]->test_multiple_properties_multiple_values_value1);
      $this->assertEquals('dedicated table - 1 - delta 1 - value 2 - ro', $dedicated_table_row[1]->test_multiple_properties_multiple_values_value2);
    }
  }

  /**
   * Tests the removal of the backup tables after a successful update.
   */
  public function testFieldableEntityTypeUpdatesRemoveBackupTables() {
    $schema = $this->database->schema();

    // Convert the entity type to be revisionable.
    $entity_type = $this->getUpdatedEntityTypeDefinition(TRUE, FALSE);
    $field_storage_definitions = $this->getUpdatedFieldStorageDefinitions(TRUE, FALSE);
    $this->entityDefinitionUpdateManager->updateFieldableEntityType($entity_type, $field_storage_definitions);

    // Check that backup tables are kept by default.
    $tables = $schema->findTables('old_%');
    $this->assertCount(4, $tables);
    foreach ($tables as $table) {
      $schema->dropTable($table);
    }

    // Make the entity update process drop the backup tables after a successful
    // update.
    $settings = Settings::getAll();
    $settings['entity_update_backup'] = FALSE;
    new Settings($settings);

    $entity_type = $this->getUpdatedEntityTypeDefinition(TRUE, TRUE);
    $field_storage_definitions = $this->getUpdatedFieldStorageDefinitions(TRUE, TRUE);
    $this->entityDefinitionUpdateManager->updateFieldableEntityType($entity_type, $field_storage_definitions);

    // Check that backup tables have been dropped.
    $tables = $schema->findTables('old_%');
    $this->assertCount(0, $tables);
  }

}
