<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

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

  /**
   * The table mapping for the tested entity type.
   *
   * @var \Drupal\Core\Entity\Sql\TableMappingInterface
   */
  protected $tableMapping;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test_extra'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Setup some fields for entity_test_extra to create.
    $definitions['multivalued_base_field'] = BaseFieldDefinition::create('string')
      ->setName('multivalued_base_field')
      ->setTargetEntityTypeId('entity_test_mulrev')
      ->setTargetBundle('entity_test_mulrev')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $this->state->set('entity_test_mulrev.additional_base_field_definitions', $definitions);

    $this->entityManager->clearCachedDefinitions();
    $this->tableMapping = $this->entityManager->getStorage('entity_test_mulrev')->getTableMapping();
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

}
