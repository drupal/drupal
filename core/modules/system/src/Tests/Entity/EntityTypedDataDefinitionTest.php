<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityTypedDataDefinitionTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests deriving metadata of entity and field data types.
 *
 * @group Entity
 */
class EntityTypedDataDefinitionTest extends DrupalUnitTestBase {

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'text', 'node');

  public function setUp() {
    parent::setup();
    $this->typedDataManager = $this->container->get('typed_data_manager');
  }

  /**
   * Tests deriving metadata about fields.
   */
  public function testFields() {
    $field_definition = FieldDefinition::create('integer');
    // Fields are lists of complex data.
    $this->assertTrue($field_definition instanceof ListDataDefinitionInterface);
    $this->assertFalse($field_definition instanceof ComplexDataDefinitionInterface);
    $field_item_definition = $field_definition->getItemDefinition();
    $this->assertFalse($field_item_definition instanceof ListDataDefinitionInterface);
    $this->assertTrue($field_item_definition instanceof ComplexDataDefinitionInterface);

    // Derive metadata about field item properties.
    $this->assertEqual(array_keys($field_item_definition->getPropertyDefinitions()), array('value'));
    $this->assertEqual($field_item_definition->getPropertyDefinition('value')->getDataType(), 'integer');
    $this->assertEqual($field_item_definition->getMainPropertyName(), 'value');
    $this->assertNull($field_item_definition->getPropertyDefinition('invalid'));

    // Test accessing field item property metadata via the field definition.
    $this->assertTrue($field_definition instanceof FieldDefinitionInterface);
    $this->assertEqual(array_keys($field_definition->getPropertyDefinitions()), array('value'));
    $this->assertEqual($field_definition->getPropertyDefinition('value')->getDataType(), 'integer');
    $this->assertEqual($field_definition->getMainPropertyName(), 'value');
    $this->assertNull($field_definition->getPropertyDefinition('invalid'));

    // Test using the definition factory for field item lists and field items.
    $field_item = $this->typedDataManager->createDataDefinition('field_item:integer');
    $this->assertFalse($field_item instanceof ListDataDefinitionInterface);
    $this->assertTrue($field_item instanceof ComplexDataDefinitionInterface);
    // Comparison should ignore the internal static cache, so compare the
    // serialized objects instead.
    $this->assertEqual(serialize($field_item_definition), serialize($field_item));

    $field_definition2 = $this->typedDataManager->createListDataDefinition('field_item:integer');
    $this->assertTrue($field_definition2 instanceof ListDataDefinitionInterface);
    $this->assertFalse($field_definition2 instanceof ComplexDataDefinitionInterface);
    $this->assertEqual(serialize($field_definition), serialize($field_definition2));
  }

  /**
   * Tests deriving metadata about entities.
   */
  public function testEntities() {
    $entity_definition = EntityDataDefinition::create('node');
    // Entities are complex data.
    $this->assertFalse($entity_definition instanceof ListDataDefinitionInterface);
    $this->assertTrue($entity_definition instanceof ComplexDataDefinitionInterface);

    $field_definitions = $entity_definition->getPropertyDefinitions();
    // Comparison should ignore the internal static cache, so compare the
    // serialized objects instead.
    $this->assertEqual(serialize($field_definitions), serialize(\Drupal::entityManager()->getBaseFieldDefinitions('node')));
    $this->assertEqual($entity_definition->getPropertyDefinition('title')->getItemDefinition()->getDataType(), 'field_item:string');
    $this->assertNull($entity_definition->getMainPropertyName());
    $this->assertNull($entity_definition->getPropertyDefinition('invalid'));

    $entity_definition2 = $this->typedDataManager->createDataDefinition('entity:node');
    $this->assertFalse($entity_definition2 instanceof ListDataDefinitionInterface);
    $this->assertTrue($entity_definition2 instanceof ComplexDataDefinitionInterface);
    $this->assertEqual(serialize($entity_definition), serialize($entity_definition2));

    // Test that the definition factory creates the right definitions for all
    // entity data types variants.
    $this->assertEqual($this->typedDataManager->createDataDefinition('entity'), EntityDataDefinition::create());
    $this->assertEqual($this->typedDataManager->createDataDefinition('entity:node'), EntityDataDefinition::create('node'));

    // Config entities don't support typed data.
    $entity_definition = EntityDataDefinition::create('node_type');
    $this->assertEqual(array(), $entity_definition->getPropertyDefinitions());
  }

  /**
   * Tests deriving metadata from entity references.
   */
  public function testEntityReferences() {
    $reference_definition = DataReferenceDefinition::create('entity');
    $this->assertTrue($reference_definition instanceof DataReferenceDefinitionInterface);

    // Test retrieving metadata about the referenced data.
    $this->assertEqual($reference_definition->getTargetDefinition()->getDataType(), 'entity');
    $this->assertTrue($reference_definition->getTargetDefinition() instanceof EntityDataDefinitionInterface);

    // Test that the definition factory creates the right definition object.
    $reference_definition2 = $this->typedDataManager->createDataDefinition('entity_reference');
    $this->assertTrue($reference_definition2 instanceof DataReferenceDefinitionInterface);
    $this->assertEqual($reference_definition2, $reference_definition);
  }

}
