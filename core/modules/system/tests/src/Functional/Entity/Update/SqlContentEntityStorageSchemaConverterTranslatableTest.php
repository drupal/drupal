<?php

namespace Drupal\Tests\system\Functional\Entity\Update;

use Drupal\Core\Entity\Sql\TemporaryTableMapping;

/**
 * Tests converting a translatable entity type with data to revisionable.
 *
 * @group Entity
 * @group Update
 * @group legacy
 */
class SqlContentEntityStorageSchemaConverterTranslatableTest extends SqlContentEntityStorageSchemaConverterTestBase {

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
