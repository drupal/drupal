<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity\TypedData;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\Tests\Core\Entity\ContentEntityBaseMockableClass;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Validator\Constraint;

/**
 * Tests Drupal\Core\Entity\Plugin\DataType\EntityAdapter.
 */
#[CoversClass(EntityAdapter::class)]
#[Group('Entity')]
#[Group('TypedData')]
class EntityAdapterUnitTest extends UnitTestCase {

  /**
   * The bundle used for testing.
   */
  protected string $bundle;

  /**
   * The content entity used for testing.
   */
  protected ContentEntityBaseMockableClass $entity;

  /**
   * The content entity adapter under test.
   */
  protected EntityAdapter $entityAdapter;

  /**
   * The entity type used for testing.
   */
  protected EntityTypeInterface&Stub $entityType;

  /**
   * The entity type manager used for testing.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * The entity field manager.
   */
  protected EntityFieldManagerInterface&MockObject $entityFieldManager;

  /**
   * The type ID of the entity under test.
   */
  protected string $entityTypeId;

  /**
   * The typed data manager used for testing.
   */
  protected TypedDataManagerInterface&MockObject $typedDataManager;

  /**
   * The field item list returned by the typed data manager.
   */
  protected FieldItemListInterface&Stub $fieldItemList;

  /**
   * The field type manager used for testing.
   */
  protected FieldTypePluginManager&Stub $fieldTypePluginManager;

  /**
   * The language manager.
   */
  protected LanguageManagerInterface&MockObject $languageManager;

  /**
   * The UUID generator used for testing.
   */
  protected UuidInterface&Stub $uuid;

  /**
   * The entity ID.
   */
  protected int $id;

  /**
   * Field definitions.
   *
   * @var array{'id': \Drupal\Core\Field\BaseFieldDefinition, 'revision_id': \Drupal\Core\Field\BaseFieldDefinition}
   */
  protected array $fieldDefinitions;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->id = 1;
    $values = [
      'id' => $this->id,
      'uuid' => '3bb9ee60-bea5-4622-b89b-a63319d10b3a',
      'defaultLangcode' => [LanguageInterface::LANGCODE_DEFAULT => 'en'],
    ];
    $this->entityTypeId = $this->randomMachineName();
    $this->bundle = $this->randomMachineName();

    $this->entityType = $this->createStub(EntityTypeInterface::class);
    $this->entityType
      ->method('getKeys')
      ->willReturn([
        'id' => 'id',
        'uuid' => 'uuid',
        'langcode' => 'langcode',
      ]);
    $this->entityType
      ->method('getKey')
      ->willReturnMap([
        ['id', 'id'],
        ['uuid', 'uuid'],
        ['langcode', 'langcode'],
      ]);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager
      ->expects($this->exactly(6))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);

    $this->uuid = $this->createStub(UuidInterface::class);

    $this->typedDataManager = $this->createMock(TypedDataManagerInterface::class);
    $this->typedDataManager
      ->expects($this->never())
      ->method('getDefinition')
      ->with('entity')
      ->willReturn(['class' => '\Drupal\Core\Entity\Plugin\DataType\EntityAdapter']);
    $this->typedDataManager
      ->method('getDefaultConstraints')
      ->willReturn([]);

    $validation_constraint_manager = $this->createStub(ConstraintManager::class);
    $validation_constraint_manager
      ->method('create')
      ->willReturn($this->createStub(Constraint::class));
    $this->typedDataManager
      ->method('getValidationConstraintManager')
      ->willReturn($validation_constraint_manager);

    $not_specified = new Language(['id' => LanguageInterface::LANGCODE_NOT_SPECIFIED, 'locked' => TRUE]);
    $this->languageManager = $this->createMock('\Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager
      ->method('getLanguages')
      ->willReturn([LanguageInterface::LANGCODE_NOT_SPECIFIED => $not_specified]);

    $this->languageManager
      ->expects($this->never())
      ->method('getLanguage')
      ->with(LanguageInterface::LANGCODE_NOT_SPECIFIED)
      ->willReturn($not_specified);

    $this->fieldTypePluginManager = $this->createStub(FieldTypePluginManager::class);
    $this->fieldTypePluginManager
      ->method('getDefaultStorageSettings')
      ->willReturn([]);
    $this->fieldTypePluginManager
      ->method('getDefaultFieldSettings')
      ->willReturn([]);

    $this->fieldItemList = $this->createStub(FieldItemListInterface::class);
    $this->fieldTypePluginManager
      ->method('createFieldItemList')
      ->willReturn($this->fieldItemList);

    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('entity_field.manager', $this->entityFieldManager);
    $container->set('uuid', $this->uuid);
    $container->set('typed_data_manager', $this->typedDataManager);
    $container->set('language_manager', $this->languageManager);
    $container->set('plugin.manager.field.field_type', $this->fieldTypePluginManager);
    \Drupal::setContainer($container);

    $this->fieldDefinitions = [
      'id' => BaseFieldDefinition::create('integer'),
      'revision_id' => BaseFieldDefinition::create('integer'),
    ];
    $this->entityFieldManager
      ->expects($this->once())
      ->method('getFieldDefinitions')
      ->with($this->entityTypeId, $this->bundle)
      ->willReturn($this->fieldDefinitions);

    $this->entity = new ContentEntityBaseMockableClass($values, $this->entityTypeId, $this->bundle);

    $this->entityAdapter = EntityAdapter::createFromEntity($this->entity);
  }

  /**
   * Reinitializes the field item list as a mock object.
   */
  protected function setUpMockFieldItemList(): void {
    $this->fieldTypePluginManager = $this->createStub(FieldTypePluginManager::class);
    $this->fieldItemList = $this->createMock(FieldItemListInterface::class);
    $this->fieldTypePluginManager
      ->method('createFieldItemList')
      ->willReturn($this->fieldItemList);
    \Drupal::getContainer()->set('plugin.manager.field.field_type', $this->fieldTypePluginManager);
  }

  /**
   * Tests get constraints.
   */
  public function testGetConstraints(): void {
    $this->assertIsArray($this->entityAdapter->getConstraints());
  }

  /**
   * Tests get name.
   */
  public function testGetName(): void {
    $this->assertNull($this->entityAdapter->getName());
  }

  /**
   * Tests get root.
   */
  public function testGetRoot(): void {
    $this->assertSame(spl_object_hash($this->entityAdapter), spl_object_hash($this->entityAdapter->getRoot()));
  }

  /**
   * Tests get property path.
   */
  public function testGetPropertyPath(): void {
    $this->assertSame('', $this->entityAdapter->getPropertyPath());
  }

  /**
   * Tests get parent.
   */
  public function testGetParent(): void {
    $this->assertNull($this->entityAdapter->getParent());
  }

  /**
   * Tests set context.
   */
  public function testSetContext(): void {
    $name = $this->randomMachineName();
    $parent = $this->createStub(TraversableTypedDataInterface::class);
    // Our mocked entity->setContext() returns NULL, so assert that.
    $this->assertNull($this->entityAdapter->setContext($name, $parent));
    $this->assertEquals($name, $this->entityAdapter->getName());
    $this->assertEquals($parent, $this->entityAdapter->getParent());
  }

  /**
   * Tests get value.
   */
  public function testGetValue(): void {
    $this->assertEquals($this->entity, $this->entityAdapter->getValue());
  }

  /**
   * Tests get entity.
   */
  public function testGetEntity(): void {
    $this->assertSame($this->entity, $this->entityAdapter->getEntity());
  }

  /**
   * Tests set value.
   */
  public function testSetValue(): void {
    $this->entityAdapter->setValue(NULL);
    $this->assertNull($this->entityAdapter->getValue());
  }

  /**
   * Tests get.
   */
  public function testGet(): void {
    $this->assertInstanceOf('\Drupal\Core\Field\FieldItemListInterface', $this->entityAdapter->get('id'));
  }

  /**
   * Tests get invalid field.
   */
  public function testGetInvalidField(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->entityAdapter->get('invalid');
  }

  /**
   * Tests get without data.
   */
  public function testGetWithoutData(): void {
    $this->entityAdapter->setValue(NULL);
    $this->expectException(MissingDataException::class);
    $this->entityAdapter->get('id');
  }

  /**
   * Tests set.
   */
  public function testSet(): void {
    $this->setUpMockFieldItemList();
    $id_items = [['value' => $this->id + 1]];

    $this->fieldItemList->expects($this->once())
      ->method('setValue')
      ->with($id_items);

    $this->entityAdapter->set('id', $id_items);
  }

  /**
   * Tests set without data.
   */
  public function testSetWithoutData(): void {
    $this->entityAdapter->setValue(NULL);
    $id_items = [['value' => $this->id + 1]];
    $this->expectException(MissingDataException::class);
    $this->entityAdapter->set('id', $id_items);
  }

  /**
   * Tests get properties.
   */
  public function testGetProperties(): void {
    $fields = $this->entityAdapter->getProperties();
    $this->assertInstanceOf('Drupal\Core\Field\FieldItemListInterface', $fields['id']);
    $this->assertInstanceOf('Drupal\Core\Field\FieldItemListInterface', $fields['revision_id']);
  }

  /**
   * Tests to array.
   */
  public function testToArray(): void {
    $array = $this->entityAdapter->toArray();
    // Mock field objects return NULL values, so test keys only.
    $this->assertArrayHasKey('id', $array);
    $this->assertArrayHasKey('revision_id', $array);
    $this->assertCount(2, $array);
  }

  /**
   * Tests to array without data.
   */
  public function testToArrayWithoutData(): void {
    $this->entityAdapter->setValue(NULL);
    $this->expectException(MissingDataException::class);
    $this->entityAdapter->toArray();
  }

  /**
   * Tests is empty.
   */
  public function testIsEmpty(): void {
    $this->assertFalse($this->entityAdapter->isEmpty());
    $this->entityAdapter->setValue(NULL);
    $this->assertTrue($this->entityAdapter->isEmpty());
  }

  /**
   * Tests on change.
   */
  public function testOnChange(): void {
    $entity = $this->createMock('\Drupal\Core\Entity\ContentEntityInterface');
    $entity->expects($this->once())
      ->method('onChange')
      ->with('foo')
      ->willReturn(NULL);
    $this->entityAdapter->setValue($entity);
    $this->entityAdapter->onChange('foo');
  }

  /**
   * Tests get data definition.
   */
  public function testGetDataDefinition(): void {
    $definition = $this->entityAdapter->getDataDefinition();
    $this->assertInstanceOf('\Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface', $definition);
    $this->assertEquals($definition->getEntityTypeId(), $this->entityTypeId);
    $this->assertEquals($definition->getBundles(), [$this->bundle]);
  }

  /**
   * Tests get string.
   */
  public function testGetString(): void {
    $entity = $this->createMock('\Drupal\Core\Entity\ContentEntityInterface');
    $entity->expects($this->once())
      ->method('label')
      ->willReturn('foo');
    $this->entityAdapter->setValue($entity);
    $this->assertEquals('foo', $this->entityAdapter->getString());
    $this->entityAdapter->setValue(NULL);
    $this->assertEquals('', $this->entityAdapter->getString());
  }

  /**
   * Tests apply default value.
   */
  public function testApplyDefaultValue(): void {
    $this->setUpMockFieldItemList();
    // For each field on the entity the mock method has to be invoked once.
    $this->fieldItemList->expects($this->exactly(2))
      ->method('applyDefaultValue');
    $this->entityAdapter->applyDefaultValue();
  }

  /**
   * Tests get iterator.
   */
  public function testGetIterator(): void {
    // Content entity test.
    $iterator = $this->entityAdapter->getIterator();
    $fields = iterator_to_array($iterator);
    $this->assertArrayHasKey('id', $fields);
    $this->assertArrayHasKey('revision_id', $fields);
    $this->assertCount(2, $fields);

    $this->entityAdapter->setValue(NULL);
    $this->assertEquals(new \ArrayIterator([]), $this->entityAdapter->getIterator());
  }

}
