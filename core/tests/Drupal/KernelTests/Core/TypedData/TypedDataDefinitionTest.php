<?php

namespace Drupal\KernelTests\Core\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deriving metadata of core data types.
 *
 * @group TypedData
 */
class TypedDataDefinitionTest extends KernelTestBase {

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  protected function setUp() {
    parent::setup();
    $this->typedDataManager = $this->container->get('typed_data_manager');
  }

  /**
   * Tests deriving metadata about list items.
   */
  public function testLists() {
    $list_definition = ListDataDefinition::create('string');
    $this->assertTrue($list_definition instanceof ListDataDefinitionInterface);
    $item_definition = $list_definition->getItemDefinition();
    $this->assertTrue($item_definition instanceof DataDefinitionInterface);
    $this->assertEqual($item_definition->getDataType(), 'string');

    // Test using the definition factory.
    $list_definition2 = $this->typedDataManager->createListDataDefinition('string');
    $this->assertTrue($list_definition2 instanceof ListDataDefinitionInterface);
    $this->assertEqual($list_definition, $list_definition2);

    // Test creating the definition of data with type 'list', which is the same
    // as creating a definition of a list of items of type 'any'.
    $list_definition = $this->typedDataManager->createDataDefinition('list');
    $this->assertTrue($list_definition instanceof ListDataDefinitionInterface);
    $this->assertEqual($list_definition->getDataType(), 'list');
    $this->assertEqual($list_definition->getItemDefinition()->getDataType(), 'any');
  }

  /**
   * Tests deriving metadata about maps.
   */
  public function testMaps() {
    $map_definition = MapDataDefinition::create()
      ->setPropertyDefinition('one', DataDefinition::create('string'))
      ->setPropertyDefinition('two', DataDefinition::create('string'))
      ->setPropertyDefinition('three', DataDefinition::create('string'));

    $this->assertTrue($map_definition instanceof ComplexDataDefinitionInterface);

    // Test retrieving metadata about contained properties.
    $this->assertEqual(array_keys($map_definition->getPropertyDefinitions()), ['one', 'two', 'three']);
    $this->assertEqual($map_definition->getPropertyDefinition('one')->getDataType(), 'string');
    $this->assertNull($map_definition->getMainPropertyName());
    $this->assertNull($map_definition->getPropertyDefinition('invalid'));

    // Test using the definition factory.
    $map_definition2 = $this->typedDataManager->createDataDefinition('map');
    $this->assertTrue($map_definition2 instanceof ComplexDataDefinitionInterface);
    $map_definition2->setPropertyDefinition('one', DataDefinition::create('string'))
      ->setPropertyDefinition('two', DataDefinition::create('string'))
      ->setPropertyDefinition('three', DataDefinition::create('string'));
    $this->assertEqual($map_definition, $map_definition2);
  }

  /**
   * Tests deriving metadata from data references.
   */
  public function testDataReferences() {
    $language_reference_definition = DataReferenceDefinition::create('language');
    $this->assertTrue($language_reference_definition instanceof DataReferenceDefinitionInterface);

    // Test retrieving metadata about the referenced data.
    $this->assertEqual($language_reference_definition->getTargetDefinition()->getDataType(), 'language');

    // Test using the definition factory.
    $language_reference_definition2 = $this->typedDataManager->createDataDefinition('language_reference');
    $this->assertTrue($language_reference_definition2 instanceof DataReferenceDefinitionInterface);
    $this->assertEqual($language_reference_definition, $language_reference_definition2);
  }

}
