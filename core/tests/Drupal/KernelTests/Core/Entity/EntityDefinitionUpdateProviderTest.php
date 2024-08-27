<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;

/**
 * Tests EntityDefinitionUpdateManager functionality.
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityDefinitionUpdateManager
 *
 * @group Entity
 * @group #slow
 */
class EntityDefinitionUpdateProviderTest extends EntityKernelTestBase {

  use EntityDefinitionTestTrait;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

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
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test_update', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->database = $this->container->get('database');

    // Install every entity type's schema that wasn't installed in the parent
    // method.
    foreach (array_diff_key($this->entityTypeManager->getDefinitions(), array_flip(['user', 'entity_test'])) as $entity_type_id => $entity_type) {
      $this->installEntitySchema($entity_type_id);
    }
  }

  /**
   * Tests deleting a base field when it has existing data.
   *
   * @dataProvider baseFieldDeleteWithExistingDataTestCases
   */
  public function testBaseFieldDeleteWithExistingData($entity_type_id, $create_entity_revision, $base_field_revisionable, $create_entity_translation): void {
    // Enable an additional language.
    ConfigurableLanguage::createFromLangcode('ro')->save();

    /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $schema_handler = $this->database->schema();

    // Create an entity without the base field, to ensure NULL values are not
    // added to the dedicated table storage to be purged.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->create();
    $entity->save();

    // Add the base field and run the update.
    $this->addBaseField('string', $entity_type_id, $base_field_revisionable, TRUE, $create_entity_translation);
    $this->applyEntityUpdates();

    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $storage->getTableMapping();
    $storage_definition = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions($entity_type_id)['new_base_field'];

    // Save an entity with the base field populated.
    $entity = $storage->create(['new_base_field' => 'foo']);
    $entity->save();

    if ($create_entity_translation) {
      $translation = $entity->addTranslation('ro', ['new_base_field' => 'foo-ro']);
      $translation->save();
    }

    if ($create_entity_revision) {
      $entity->setNewRevision(TRUE);
      $entity->isDefaultRevision(FALSE);
      $entity->new_base_field = 'bar';
      $entity->save();

      if ($create_entity_translation) {
        $translation = $entity->getTranslation('ro');
        $translation->new_base_field = 'bar-ro';
        $translation->save();
      }
    }

    // Remove the base field and apply updates.
    $this->removeBaseField($entity_type_id);
    $this->applyEntityUpdates();

    // Check that the base field's column is deleted.
    $this->assertFalse($schema_handler->fieldExists($entity_type_id, 'new_base_field'), 'Column deleted from shared table for new_base_field.');

    // Check that a dedicated 'deleted' table was created for the deleted base
    // field.
    $dedicated_deleted_table_name = $table_mapping->getDedicatedDataTableName($storage_definition, TRUE);
    $this->assertTrue($schema_handler->tableExists($dedicated_deleted_table_name), 'A dedicated table was created for the deleted new_base_field.');

    $expected[] = [
      'bundle' => $entity->bundle(),
      'deleted' => '1',
      'entity_id' => '2',
      'revision_id' => '2',
      'langcode' => 'en',
      'delta' => '0',
      'new_base_field_value' => 'foo',
    ];

    if ($create_entity_translation) {
      $expected[] = [
        'bundle' => $entity->bundle(),
        'deleted' => '1',
        'entity_id' => '2',
        'revision_id' => '2',
        'langcode' => 'ro',
        'delta' => '0',
        'new_base_field_value' => 'foo-ro',
      ];
    }

    // Check that the deleted field's data is preserved in the dedicated
    // 'deleted' table.
    $result = $this->database->select($dedicated_deleted_table_name, 't')
      ->fields('t')
      ->orderBy('revision_id', 'ASC')
      ->orderBy('langcode', 'ASC')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
    $this->assertSameSize($expected, $result);

    // Use assertEquals and not assertSame here to prevent that a different
    // sequence of the columns in the table will affect the check.
    $this->assertEquals($expected, $result);

    if ($create_entity_revision) {
      $dedicated_deleted_revision_table_name = $table_mapping->getDedicatedRevisionTableName($storage_definition, TRUE);
      $this->assertTrue($schema_handler->tableExists($dedicated_deleted_revision_table_name), 'A dedicated revision table was created for the deleted new_base_field.');

      if ($base_field_revisionable) {
        $expected[] = [
          'bundle' => $entity->bundle(),
          'deleted' => '1',
          'entity_id' => '2',
          'revision_id' => '3',
          'langcode' => 'en',
          'delta' => '0',
          'new_base_field_value' => 'bar',
        ];

        if ($create_entity_translation) {
          $expected[] = [
            'bundle' => $entity->bundle(),
            'deleted' => '1',
            'entity_id' => '2',
            'revision_id' => '3',
            'langcode' => 'ro',
            'delta' => '0',
            'new_base_field_value' => 'bar-ro',
          ];
        }
      }

      $result = $this->database->select($dedicated_deleted_revision_table_name, 't')
        ->fields('t')
        ->orderBy('revision_id', 'ASC')
        ->orderBy('langcode', 'ASC')
        ->execute()
        ->fetchAll(\PDO::FETCH_ASSOC);
      $this->assertSameSize($expected, $result);

      // Use assertEquals and not assertSame here to prevent that a different
      // sequence of the columns in the table will affect the check.
      $this->assertEquals($expected, $result);
    }

    // Check that the field storage definition is marked for purging.
    $deleted_storage_definitions = \Drupal::service('entity_field.deleted_fields_repository')->getFieldStorageDefinitions();
    $this->assertArrayHasKey($storage_definition->getUniqueStorageIdentifier(), $deleted_storage_definitions, 'The base field is marked for purging.');

    // Purge field data, and check that the storage definition has been
    // completely removed once the data is purged.
    field_purge_batch(10);
    $deleted_storage_definitions = \Drupal::service('entity_field.deleted_fields_repository')->getFieldStorageDefinitions();
    $this->assertEmpty($deleted_storage_definitions, 'The base field has been deleted.');
    $this->assertFalse($schema_handler->tableExists($dedicated_deleted_table_name), 'A dedicated field table was deleted after new_base_field was purged.');

    if (isset($dedicated_deleted_revision_table_name)) {
      $this->assertFalse($schema_handler->tableExists($dedicated_deleted_revision_table_name), 'A dedicated field revision table was deleted after new_base_field was purged.');
    }
  }

  /**
   * Test cases for ::testBaseFieldDeleteWithExistingData.
   */
  public static function baseFieldDeleteWithExistingDataTestCases() {
    return [
      'Non-revisionable, non-translatable entity type' => [
        'entity_test_update',
        FALSE,
        FALSE,
        FALSE,
      ],
      'Non-revisionable, non-translatable custom data table' => [
        'entity_test_mul',
        FALSE,
        FALSE,
        FALSE,
      ],
      'Non-revisionable, non-translatable entity type, revisionable base field' => [
        'entity_test_update',
        FALSE,
        TRUE,
        FALSE,
      ],
      'Non-revisionable, non-translatable custom data table, revisionable base field' => [
        'entity_test_mul',
        FALSE,
        TRUE,
        FALSE,
      ],
      'Revisionable, translatable entity type, non revisionable and non-translatable base field' => [
        'entity_test_mulrev',
        TRUE,
        FALSE,
        FALSE,
      ],
      'Revisionable, translatable entity type, revisionable and non-translatable base field' => [
        'entity_test_mulrev',
        TRUE,
        TRUE,
        FALSE,
      ],
      'Revisionable and non-translatable entity type, revisionable and non-translatable base field' => [
        'entity_test_rev',
        TRUE,
        TRUE,
        FALSE,
      ],
      'Revisionable and non-translatable entity type, non-revisionable and non-translatable base field' => [
        'entity_test_rev',
        TRUE,
        FALSE,
        FALSE,
      ],
      'Revisionable and translatable entity type, non-revisionable and translatable base field' => [
        'entity_test_mulrev',
        TRUE,
        FALSE,
        TRUE,
      ],
      'Revisionable and translatable entity type, revisionable and translatable base field' => [
        'entity_test_mulrev',
        TRUE,
        TRUE,
        TRUE,
      ],
    ];
  }

  /**
   * Tests adding a base field with initial values inherited from another field.
   *
   * @dataProvider initialValueFromFieldTestCases
   */
  public function testInitialValueFromField($default_initial_value, $expected_value): void {
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_update');
    $db_schema = $this->database->schema();

    // Create two entities before adding the base field.
    /** @var \Drupal\entity_test_update\Entity\EntityTestUpdate $entity */
    $storage->create([
      'name' => 'First entity',
      'test_single_property' => 'test existing value',
    ])->save();

    // The second entity does not have any value for the 'test_single_property'
    // field, allowing us to test the 'default_value' parameter of
    // \Drupal\Core\Field\BaseFieldDefinition::setInitialValueFromField().
    $storage->create([
      'name' => 'Second entity',
    ])->save();

    // Add a base field with an initial value inherited from another field.
    $definitions['new_base_field'] = BaseFieldDefinition::create('string')
      ->setName('new_base_field')
      ->setLabel('A new base field')
      ->setInitialValueFromField('name');
    $definitions['another_base_field'] = BaseFieldDefinition::create('string')
      ->setName('another_base_field')
      ->setLabel('Another base field')
      ->setInitialValueFromField('test_single_property', $default_initial_value);

    $this->state->set('entity_test_update.additional_base_field_definitions', $definitions);

    $this->assertFalse($db_schema->fieldExists('entity_test_update', 'new_base_field'), "New field 'new_base_field' does not exist before applying the update.");
    $this->assertFalse($db_schema->fieldExists('entity_test_update', 'another_base_field'), "New field 'another_base_field' does not exist before applying the update.");
    $this->entityDefinitionUpdateManager->installFieldStorageDefinition('new_base_field', 'entity_test_update', 'entity_test', $definitions['new_base_field']);
    $this->entityDefinitionUpdateManager->installFieldStorageDefinition('another_base_field', 'entity_test_update', 'entity_test', $definitions['another_base_field']);
    $this->assertTrue($db_schema->fieldExists('entity_test_update', 'new_base_field'), "New field 'new_base_field' has been created on the 'entity_test_update' table.");
    $this->assertTrue($db_schema->fieldExists('entity_test_update', 'another_base_field'), "New field 'another_base_field' has been created on the 'entity_test_update' table.");

    // Check that the initial values have been applied.
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_update');
    $entities = $storage->loadMultiple();
    $this->assertEquals('First entity', $entities[1]->get('new_base_field')->value);
    $this->assertEquals('Second entity', $entities[2]->get('new_base_field')->value);

    $this->assertEquals('test existing value', $entities[1]->get('another_base_field')->value);
    $this->assertEquals($expected_value, $entities[2]->get('another_base_field')->value);
  }

  /**
   * Test cases for ::testInitialValueFromField.
   */
  public static function initialValueFromFieldTestCases() {
    return [
      'literal value' => [
        'test initial value',
        'test initial value',
      ],
      'indexed array' => [
        ['value' => 'test initial value'],
        'test initial value',
      ],
      'empty array' => [
        [],
        NULL,
      ],
      'null' => [
        NULL,
        NULL,
      ],
    ];
  }

}
