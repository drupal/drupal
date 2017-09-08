<?php

namespace Drupal\Tests\system\Functional\Entity\Update;

use Drupal\Core\Entity\Sql\TemporaryTableMapping;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\system\Tests\Entity\EntityDefinitionTestTrait;

/**
 * Tests updating an entity type with existing data to be revisionable.
 *
 * @group Entity
 * @group Update
 */
class SqlContentEntityStorageSchemaConverterTest extends UpdatePathTestBase {

  use EntityDefinitionTestTrait;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityManager = \Drupal::entityManager();
    $this->entityDefinitionUpdateManager = \Drupal::entityDefinitionUpdateManager();
    $this->lastInstalledSchemaRepository = \Drupal::service('entity.last_installed_schema.repository');
    $this->installedStorageSchema = \Drupal::keyValue('entity.storage_schema.sql');
    $this->state = \Drupal::state();
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../fixtures/update/drupal-8.0.0-rc1-filled.standard.entity_test_update_mul.php.gz',
      __DIR__ . '/../../../../fixtures/update/drupal-8.entity-test-schema-converter-enabled.php',
    ];
  }

  /**
   * Tests the conversion of an entity type to revisionable.
   */
  public function testMakeRevisionable() {
    // Check that entity type is not revisionable prior to running the update
    // process.
    $entity_test_update = $this->lastInstalledSchemaRepository->getLastInstalledDefinition('entity_test_update');
    $this->assertFalse($entity_test_update->isRevisionable());

    // Make the entity type revisionable and translatable and run the updates.
    $this->updateEntityTypeToRevisionableAndTranslatable();

    $this->runUpdates();

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_test_update */
    $entity_test_update = $this->lastInstalledSchemaRepository->getLastInstalledDefinition('entity_test_update');
    $this->assertTrue($entity_test_update->isRevisionable());

    $field_storage_definitions = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions('entity_test_update');
    $this->assertTrue(isset($field_storage_definitions['revision_translation_affected']));

    /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_update');
    $this->assertEqual(count($storage->loadMultiple()), 102, 'All test entities were found.');

    // Check that each field value was copied correctly to the revision tables.
    for ($i = 1; $i <= 102; $i++) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
      $revision = $storage->loadRevision($i);

      $this->assertEqual($i, $revision->id());
      $this->assertEqual($i, $revision->getRevisionId());

      // Check that the correct initial value was provided for the
      // 'revision_translation_affected' field.
      $this->assertTrue($revision->revision_translation_affected->value);

      $this->assertEqual($i . ' - test single property', $revision->test_single_property->value);

      $this->assertEqual($i . ' - test multiple properties - value1', $revision->test_multiple_properties->value1);
      $this->assertEqual($i . ' - test multiple properties - value2', $revision->test_multiple_properties->value2);

      $this->assertEqual($i . ' - test single property multiple values 0', $revision->test_single_property_multiple_values->value);
      $this->assertEqual($i . ' - test single property multiple values 1', $revision->test_single_property_multiple_values[1]->value);

      $this->assertEqual($i . ' - test multiple properties multiple values - value1 0', $revision->test_multiple_properties_multiple_values[0]->value1);
      $this->assertEqual($i . ' - test multiple properties multiple values - value2 0', $revision->test_multiple_properties_multiple_values[0]->value2);
      $this->assertEqual($i . ' - test multiple properties multiple values - value1 1', $revision->test_multiple_properties_multiple_values[1]->value1);
      $this->assertEqual($i . ' - test multiple properties multiple values - value2 1', $revision->test_multiple_properties_multiple_values[1]->value2);

      $this->assertEqual($i . ' - field test configurable field - value1 0', $revision->field_test_configurable_field[0]->value1);
      $this->assertEqual($i . ' - field test configurable field - value2 0', $revision->field_test_configurable_field[0]->value2);
      $this->assertEqual($i . ' - field test configurable field - value1 1', $revision->field_test_configurable_field[1]->value1);
      $this->assertEqual($i . ' - field test configurable field - value2 1', $revision->field_test_configurable_field[1]->value2);

      $this->assertEqual($i . ' - test entity base field info', $revision->test_entity_base_field_info->value);

      // Do the same checks for translated field values.
      $translation = $revision->getTranslation('ro');

      $this->assertEqual($i . ' - test single property - ro', $translation->test_single_property->value);

      $this->assertEqual($i . ' - test multiple properties - value1 - ro', $translation->test_multiple_properties->value1);
      $this->assertEqual($i . ' - test multiple properties - value2 - ro', $translation->test_multiple_properties->value2);

      $this->assertEqual($i . ' - test single property multiple values 0 - ro', $translation->test_single_property_multiple_values[0]->value);
      $this->assertEqual($i . ' - test single property multiple values 1 - ro', $translation->test_single_property_multiple_values[1]->value);

      $this->assertEqual($i . ' - test multiple properties multiple values - value1 0 - ro', $translation->test_multiple_properties_multiple_values[0]->value1);
      $this->assertEqual($i . ' - test multiple properties multiple values - value2 0 - ro', $translation->test_multiple_properties_multiple_values[0]->value2);
      $this->assertEqual($i . ' - test multiple properties multiple values - value1 1 - ro', $translation->test_multiple_properties_multiple_values[1]->value1);
      $this->assertEqual($i . ' - test multiple properties multiple values - value2 1 - ro', $translation->test_multiple_properties_multiple_values[1]->value2);

      $this->assertEqual($i . ' - field test configurable field - value1 0 - ro', $translation->field_test_configurable_field[0]->value1);
      $this->assertEqual($i . ' - field test configurable field - value2 0 - ro', $translation->field_test_configurable_field[0]->value2);
      $this->assertEqual($i . ' - field test configurable field - value1 1 - ro', $translation->field_test_configurable_field[1]->value1);
      $this->assertEqual($i . ' - field test configurable field - value2 1 - ro', $translation->field_test_configurable_field[1]->value2);

      $this->assertEqual($i . ' - test entity base field info - ro', $translation->test_entity_base_field_info->value);
    }

    // Check that temporary tables have been removed at the end of the process.
    $schema = \Drupal::database()->schema();
    foreach ($storage->getTableMapping()->getTableNames() as $table_name) {
      $this->assertFalse($schema->tableExists(TemporaryTableMapping::getTempTableName($table_name)));
    }

    // Check that backup tables have been removed at the end of the process.
    $schema = \Drupal::database()->schema();
    foreach ($storage->getTableMapping()->getTableNames() as $table_name) {
      $this->assertFalse($schema->tableExists(TemporaryTableMapping::getTempTableName($table_name, 'old_')));
    }
  }

  /**
   * Tests that a failed "make revisionable" update preserves the existing data.
   */
  public function testMakeRevisionableErrorHandling() {
    $original_entity_type = $this->lastInstalledSchemaRepository->getLastInstalledDefinition('entity_test_update');
    $original_storage_definitions = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions('entity_test_update');

    $original_entity_schema_data = $this->installedStorageSchema->get('entity_test_update.entity_schema_data', []);
    foreach ($original_storage_definitions as $storage_definition) {
      $original_field_schema_data[$storage_definition->getName()] = $this->installedStorageSchema->get('entity_test_update.field_schema_data.' . $storage_definition->getName(), []);
    }

    // Check that entity type is not revisionable prior to running the update
    // process.
    $this->assertFalse($original_entity_type->isRevisionable());

    // Make the update throw an exception during the entity save process.
    \Drupal::state()->set('entity_test_update.throw_exception', TRUE);

    // Since the update process is interrupted by the exception thrown above,
    // we can not do the full post update testing offered by UpdatePathTestBase.
    $this->checkFailedUpdates = FALSE;

    // Make the entity type revisionable and run the updates.
    $this->updateEntityTypeToRevisionableAndTranslatable();

    $this->runUpdates();

    // Check that the update failed.
    $this->assertRaw('<strong>' . t('Failed:') . '</strong>');

    // Check that the last installed entity type definition is kept as
    // non-revisionable.
    $new_entity_type = $this->lastInstalledSchemaRepository->getLastInstalledDefinition('entity_test_update');
    $this->assertFalse($new_entity_type->isRevisionable(), 'The entity type is kept unchanged.');

    // Check that the last installed field storage definitions did not change by
    // looking at the 'langcode' field, which is updated automatically.
    $new_storage_definitions = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions('entity_test_update');
    $langcode_key = $original_entity_type->getKey('langcode');
    $this->assertEqual($original_storage_definitions[$langcode_key]->isRevisionable(), $new_storage_definitions[$langcode_key]->isRevisionable(), "The 'langcode' field is kept unchanged.");

    /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_update');

    // Check that installed storage schema did not change.
    $new_entity_schema_data = $this->installedStorageSchema->get('entity_test_update.entity_schema_data', []);
    $this->assertEqual($original_entity_schema_data, $new_entity_schema_data);

    foreach ($new_storage_definitions as $storage_definition) {
      $new_field_schema_data[$storage_definition->getName()] = $this->installedStorageSchema->get('entity_test_update.field_schema_data.' . $storage_definition->getName(), []);
    }
    $this->assertEqual($original_field_schema_data, $new_field_schema_data);

    // Check that temporary tables have been removed.
    $schema = \Drupal::database()->schema();
    foreach ($storage->getTableMapping()->getTableNames() as $table_name) {
      $this->assertFalse($schema->tableExists(TemporaryTableMapping::getTempTableName($table_name)));
    }

    // Check that the original tables still exist and their data is intact.
    $this->assertTrue($schema->tableExists('entity_test_update'));
    $this->assertTrue($schema->tableExists('entity_test_update_data'));

    $base_table_count = \Drupal::database()->select('entity_test_update')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEqual($base_table_count, 102);

    $data_table_count = \Drupal::database()->select('entity_test_update_data')
      ->countQuery()
      ->execute()
      ->fetchField();
    // There are two records for each entity, one for English and one for
    // Romanian.
    $this->assertEqual($data_table_count, 204);

    $base_table_row = \Drupal::database()->select('entity_test_update')
      ->fields('entity_test_update')
      ->condition('id', 1, '=')
      ->condition('langcode', 'en', '=')
      ->execute()
      ->fetchAllAssoc('id');
    $this->assertEqual('843e9ac7-3351-4cc1-a202-2dbffffae21c', $base_table_row[1]->uuid);

    $data_table_row = \Drupal::database()->select('entity_test_update_data')
      ->fields('entity_test_update_data')
      ->condition('id', 1, '=')
      ->condition('langcode', 'en', '=')
      ->execute()
      ->fetchAllAssoc('id');
    $this->assertEqual('1 - test single property', $data_table_row[1]->test_single_property);
    $this->assertEqual('1 - test multiple properties - value1', $data_table_row[1]->test_multiple_properties__value1);
    $this->assertEqual('1 - test multiple properties - value2', $data_table_row[1]->test_multiple_properties__value2);
    $this->assertEqual('1 - test entity base field info', $data_table_row[1]->test_entity_base_field_info);
  }

}
