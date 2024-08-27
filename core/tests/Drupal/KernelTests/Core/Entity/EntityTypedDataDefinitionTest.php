<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deriving metadata of entity and field data types.
 *
 * @group Entity
 */
class EntityTypedDataDefinitionTest extends KernelTestBase {

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'filter', 'text', 'entity_test', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');

    $this->typedDataManager = $this->container->get('typed_data_manager');
  }

  /**
   * Tests deriving metadata about fields.
   */
  public function testFields(): void {
    $field_definition = BaseFieldDefinition::create('integer');
    // Fields are lists of complex data.
    $this->assertInstanceOf(ListDataDefinitionInterface::class, $field_definition);
    $this->assertNotInstanceOf(ComplexDataDefinitionInterface::class, $field_definition);
    $field_item_definition = $field_definition->getItemDefinition();
    $this->assertNotInstanceOf(ListDataDefinitionInterface::class, $field_item_definition);
    $this->assertInstanceOf(ComplexDataDefinitionInterface::class, $field_item_definition);

    // Derive metadata about field item properties.
    $this->assertEquals(['value'], array_keys($field_item_definition->getPropertyDefinitions()));
    $this->assertEquals('integer', $field_item_definition->getPropertyDefinition('value')->getDataType());
    $this->assertEquals('value', $field_item_definition->getMainPropertyName());
    $this->assertNull($field_item_definition->getPropertyDefinition('invalid'));

    // Test accessing field item property metadata via the field definition.
    $this->assertInstanceOf(FieldDefinitionInterface::class, $field_definition);
    $this->assertEquals(['value'], array_keys($field_definition->getPropertyDefinitions()));
    $this->assertEquals('integer', $field_definition->getPropertyDefinition('value')->getDataType());
    $this->assertEquals('value', $field_definition->getMainPropertyName());
    $this->assertNull($field_definition->getPropertyDefinition('invalid'));

    // Test using the definition factory for field item lists and field items.
    $field_item = $this->typedDataManager->createDataDefinition('field_item:integer');
    $this->assertNotInstanceOf(ListDataDefinitionInterface::class, $field_item);
    $this->assertInstanceOf(ComplexDataDefinitionInterface::class, $field_item);
    // Comparison should ignore the internal static cache, so compare the
    // serialized objects instead.
    $this->assertEquals(serialize($field_item_definition), serialize($field_item));

    $field_definition2 = $this->typedDataManager->createListDataDefinition('field_item:integer');
    $this->assertInstanceOf(ListDataDefinitionInterface::class, $field_definition2);
    $this->assertNotInstanceOf(ComplexDataDefinitionInterface::class, $field_definition2);
    $this->assertEquals(serialize($field_definition), serialize($field_definition2));
  }

  /**
   * Tests deriving metadata about entities.
   */
  public function testEntities(): void {
    $this->installEntitySchema('entity_test_with_bundle');
    EntityTestBundle::create([
      'id' => 'article',
      'label' => 'Article',
    ])->save();

    $entity_definition = EntityDataDefinition::create('entity_test_with_bundle');
    $bundle_definition = EntityDataDefinition::create('entity_test_with_bundle', 'article');

    // Entities are complex data.
    $this->assertNotInstanceOf(ListDataDefinitionInterface::class, $entity_definition);
    $this->assertInstanceOf(ComplexDataDefinitionInterface::class, $entity_definition);

    // Entity definitions should inherit their labels from the entity type.
    $this->assertEquals('Test entity with bundle', $entity_definition->getLabel());
    $this->assertEquals('Article', $bundle_definition->getLabel());

    $field_definitions = $entity_definition->getPropertyDefinitions();
    // Comparison should ignore the internal static cache, so compare the
    // serialized objects instead.
    $this->assertEquals(serialize(\Drupal::service('entity_field.manager')->getBaseFieldDefinitions('entity_test_with_bundle')), serialize($field_definitions));
    $this->assertEquals('field_item:string', $entity_definition->getPropertyDefinition('name')->getItemDefinition()->getDataType());
    $this->assertNull($entity_definition->getMainPropertyName());
    $this->assertNull($entity_definition->getPropertyDefinition('invalid'));

    $entity_definition2 = $this->typedDataManager->createDataDefinition('entity:entity_test_with_bundle');
    $this->assertNotInstanceOf(ListDataDefinitionInterface::class, $entity_definition2);
    $this->assertInstanceOf(ComplexDataDefinitionInterface::class, $entity_definition2);
    $this->assertEquals(serialize($entity_definition), serialize($entity_definition2));

    // Test that the definition factory creates the right definitions for all
    // entity data types variants.
    $this->assertEquals(serialize(EntityDataDefinition::create()), serialize($this->typedDataManager->createDataDefinition('entity')));
    $this->assertEquals(serialize(EntityDataDefinition::create('entity_test_with_bundle')), serialize($this->typedDataManager->createDataDefinition('entity:entity_test_with_bundle')));

    // Config entities don't support typed data.
    $entity_definition = EntityDataDefinition::create('entity_test_bundle');
    $this->assertEquals([], $entity_definition->getPropertyDefinitions());
  }

  /**
   * Tests deriving metadata from entity references.
   */
  public function testEntityReferences(): void {
    $reference_definition = DataReferenceDefinition::create('entity');
    $this->assertInstanceOf(DataReferenceDefinitionInterface::class, $reference_definition);

    // Test retrieving metadata about the referenced data.
    $this->assertEquals('entity', $reference_definition->getTargetDefinition()->getDataType());
    $this->assertInstanceOf(EntityDataDefinitionInterface::class, $reference_definition->getTargetDefinition());

    // Test that the definition factory creates the right definition object.
    $reference_definition2 = $this->typedDataManager->createDataDefinition('entity_reference');
    $this->assertInstanceOf(DataReferenceDefinitionInterface::class, $reference_definition2);
    $this->assertEquals(serialize($reference_definition), serialize($reference_definition2));
  }

  /**
   * Tests that an entity annotation can mark the data definition as internal.
   *
   * @dataProvider entityDefinitionIsInternalProvider
   */
  public function testEntityDefinitionIsInternal($internal, $expected): void {
    $entity_type_id = $this->randomMachineName();

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->entityClassImplements(ConfigEntityInterface::class)->willReturn(FALSE);
    $entity_type->getKey('bundle')->willReturn(FALSE);
    $entity_type->getLabel()->willReturn($this->randomString());
    $entity_type->getConstraints()->willReturn([]);
    $entity_type->isInternal()->willReturn($internal);
    $entity_type->getBundleEntityType()->willReturn(NULL);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinitions()->willReturn([$entity_type_id => $entity_type->reveal()]);
    $this->container->set('entity_type.manager', $entity_type_manager->reveal());

    $entity_data_definition = EntityDataDefinition::create($entity_type_id);
    $this->assertSame($expected, $entity_data_definition->isInternal());
  }

  /**
   * Provides test cases for testEntityDefinitionIsInternal.
   */
  public static function entityDefinitionIsInternalProvider() {
    return [
      'internal' => [TRUE, TRUE],
      'external' => [FALSE, FALSE],
      'undefined' => [NULL, FALSE],
    ];
  }

}
