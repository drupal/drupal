<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchemaConverter;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;

/**
 * Tests the SqlContentEntityStorageSchemaConverter class.
 *
 * @coversDefaultClass \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchemaConverter
 *
 * @group Entity
 * @group legacy
 */
class SqlContentEntityStorageSchemaConverterTest extends EntityKernelTestBase {

  use EntityDefinitionTestTrait;

  /**
   * @covers ::convertToRevisionable
   *
   * @expectedDeprecation \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchemaConverter is deprecated in Drupal 8.7.0, will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface::updateFieldableEntityType() instead. See https://www.drupal.org/node/3029997.
   */
  public function testConvertToRevisionable() {
    $schema = \Drupal::database()->schema();
    $this->assertFalse($schema->tableExists('entity_test_new'), 'Schema for the "entity_test_new" entity type does not exist.');

    // Check that the "entity_test_new" schema has been created.
    $this->enableNewEntityType();
    $this->assertTrue($schema->tableExists('entity_test_new'), 'Schema for the "entity_test_new" entity type has been created.');

    // Update the entity type definition to revisionable.
    $entity_type = clone \Drupal::entityTypeManager()->getDefinition('entity_test_new');
    $keys = $entity_type->getKeys();
    $keys['revision'] = 'revision_id';
    $entity_type->set('entity_keys', $keys);
    $entity_type->set('revision_table', 'entity_test_new_revision');
    $this->state->set('entity_test_new.entity_type', $entity_type);
    \Drupal::entityTypeManager()->clearCachedDefinitions();

    $revisionable_schema_converter = new SqlContentEntityStorageSchemaConverter(
      'entity_test_new',
      \Drupal::entityTypeManager(),
      \Drupal::entityDefinitionUpdateManager()
    );
    $sandbox = [];
    $revisionable_schema_converter->convertToRevisionable($sandbox, []);
    $this->assertTrue($schema->tableExists('entity_test_new_revision'), 'Schema for the "entity_test_new" entity type has been created.');
  }

}
