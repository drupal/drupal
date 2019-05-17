<?php

namespace Drupal\KernelTests\Core\Entity\Sql;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * @group Entity
 */
class SqlContentEntityStorageSchemaTest extends EntityKernelTestBase {

  /**
   * The key-value collection for tracking installed storage schema.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $installedStorageSchema;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /* @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory */
    $key_value_factory = $this->container->get('keyvalue');
    $this->installedStorageSchema = $key_value_factory->get('entity.storage_schema.sql');
    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');
  }

  /**
   * Tests updating a shared table field definition.
   */
  public function testOnFieldStorageDefinitionUpdateShared() {
    // Install the test entity type with an additional field. Use a multi-column
    // field so that field name and column name(s) do not match.
    $field = BaseFieldDefinition::create('shape')
      // Avoid creating a foreign key which is irrelevant for this test.
      ->setSetting('foreign_key_name', NULL)
      ->setName('shape')
      ->setProvider('entity_test');
    $this->state->set('entity_test.additional_base_field_definitions', [
      'shape' => $field,
    ]);
    $this->entityDefinitionUpdateManager->installFieldStorageDefinition(
      'shape',
      'entity_test',
      'entity_test',
      $field
    );

    // Make sure the field is not marked as NOT NULL initially.
    $expected = [
      'entity_test' => [
        'fields' => [
          'shape__shape' => [
            'type' => 'varchar',
            'length' => 32,
            'not null' => FALSE,
          ],
          'shape__color' => [
            'type' => 'varchar',
            'length' => 32,
            'not null' => FALSE,
          ],
        ],
      ],
    ];
    $actual = $this->installedStorageSchema->get('entity_test.field_schema_data.shape');
    $this->assertSame($expected, $actual);

    // Make the field an entity key, so that it will get marked as NOT NULL.
    $entity_type = $this->entityDefinitionUpdateManager->getEntityType('entity_test');
    $original_keys = $entity_type->getKeys();
    $entity_type->set('entity_keys', $original_keys + ['shape' => 'shape']);
    $this->entityDefinitionUpdateManager->updateEntityType($entity_type);

    // Update the field and make sure the schema got updated.
    $this->entityDefinitionUpdateManager->updateFieldStorageDefinition($field);
    $expected['entity_test']['fields']['shape__shape']['not null'] = TRUE;
    $expected['entity_test']['fields']['shape__color']['not null'] = TRUE;
    $actual = $this->installedStorageSchema->get('entity_test.field_schema_data.shape');
    $this->assertSame($expected, $actual);

    // Remove the entity key again and check that the schema got reverted.
    $entity_type->set('entity_keys', $original_keys);
    $this->entityDefinitionUpdateManager->updateEntityType($entity_type);

    $this->entityDefinitionUpdateManager->updateFieldStorageDefinition($field);
    $expected['entity_test']['fields']['shape__shape']['not null'] = FALSE;
    $expected['entity_test']['fields']['shape__color']['not null'] = FALSE;
    $actual = $this->installedStorageSchema->get('entity_test.field_schema_data.shape');
    $this->assertSame($expected, $actual);

    // Now add an entity and repeat the process.
    $entity_storage = $this->entityTypeManager->getStorage('entity_test');
    $entity_storage->create([
      'shape' => [
        'shape' => 'rectangle',
        'color' => 'pink',
      ],
    ])->save();

    $entity_type->set('entity_keys', $original_keys + ['shape' => 'shape']);
    $this->entityDefinitionUpdateManager->updateEntityType($entity_type);

    $this->entityDefinitionUpdateManager->updateFieldStorageDefinition($field);
    $expected['entity_test']['fields']['shape__shape']['not null'] = TRUE;
    $expected['entity_test']['fields']['shape__color']['not null'] = TRUE;
    $actual = $this->installedStorageSchema->get('entity_test.field_schema_data.shape');
    $this->assertSame($expected, $actual);

    $entity_type->set('entity_keys', $original_keys);
    $this->entityDefinitionUpdateManager->updateEntityType($entity_type);
    $this->entityDefinitionUpdateManager->updateFieldStorageDefinition($field);
    $expected['entity_test']['fields']['shape__shape']['not null'] = FALSE;
    $expected['entity_test']['fields']['shape__color']['not null'] = FALSE;
    $actual = $this->installedStorageSchema->get('entity_test.field_schema_data.shape');
    $this->assertSame($expected, $actual);
  }

}
