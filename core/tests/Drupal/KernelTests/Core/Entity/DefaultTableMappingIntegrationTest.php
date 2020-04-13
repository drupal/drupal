<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;

/**
 * Tests the default table mapping class for content entities stored in SQL.
 *
 * @see \Drupal\Core\Entity\Sql\DefaultTableMapping
 * @see \Drupal\Core\Entity\Sql\TableMappingInterface
 *
 * @coversDefaultClass \Drupal\Core\Entity\Sql\DefaultTableMapping
 * @group Entity
 */
class DefaultTableMappingIntegrationTest extends EntityKernelTestBase {

  use EntityDefinitionTestTrait;

  /**
   * The table mapping for the tested entity type.
   *
   * @var \Drupal\Core\Entity\Sql\DefaultTableMapping
   */
  protected $tableMapping;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test_extra'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Setup some fields for entity_test_extra to create.
    $definitions['multivalued_base_field'] = BaseFieldDefinition::create('string')
      ->setName('multivalued_base_field')
      ->setTargetEntityTypeId('entity_test_mulrev')
      ->setTargetBundle('entity_test_mulrev')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      // Base fields are non-translatable and non-revisionable by default, but
      // we explicitly set these values here for extra clarity.
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE);
    $this->state->set('entity_test_mulrev.additional_base_field_definitions', $definitions);

    $this->tableMapping = $this->entityTypeManager->getStorage('entity_test_mulrev')->getTableMapping();

    // Ensure that the tables for the new field are created.
    $this->applyEntityUpdates('entity_test_mulrev');
  }

  /**
   * Tests DefaultTableMapping::getFieldTableName().
   *
   * @covers ::getFieldTableName
   */
  public function testGetFieldTableName() {
    // Test the field table name for a single-valued base field, which is stored
    // in the entity's base table.
    $expected = 'entity_test_mulrev';
    $this->assertEquals($this->tableMapping->getFieldTableName('uuid'), $expected);

    // Test the field table name for a translatable and revisionable base field,
    // which is stored in the entity's data table.
    $expected = 'entity_test_mulrev_property_data';
    $this->assertEquals($this->tableMapping->getFieldTableName('name'), $expected);

    // Test the field table name for a multi-valued base field, which is stored
    // in a dedicated table.
    $expected = 'entity_test_mulrev__multivalued_base_field';
    $this->assertEquals($this->tableMapping->getFieldTableName('multivalued_base_field'), $expected);
  }

  /**
   * Tests DefaultTableMapping::getTableNames().
   *
   * @covers ::getTableNames
   */
  public function testGetTableNames() {
    $storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('entity_test_mulrev');
    $dedicated_data_table = $this->tableMapping->getDedicatedDataTableName($storage_definitions['multivalued_base_field']);
    $dedicated_revision_table = $this->tableMapping->getDedicatedRevisionTableName($storage_definitions['multivalued_base_field']);

    // Check that both the data and the revision tables exist for a multi-valued
    // base field.
    $database_schema = \Drupal::database()->schema();
    $this->assertTrue($database_schema->tableExists($dedicated_data_table));
    $this->assertTrue($database_schema->tableExists($dedicated_revision_table));

    // Check that the table mapping contains both the data and the revision
    // tables exist for a multi-valued base field.
    $expected = [
      'entity_test_mulrev',
      'entity_test_mulrev_property_data',
      'entity_test_mulrev_revision',
      'entity_test_mulrev_property_revision',
      $dedicated_data_table,
      $dedicated_revision_table,
    ];
    $this->assertEquals($expected, $this->tableMapping->getTableNames());
  }

}
