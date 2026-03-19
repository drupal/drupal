<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Unit;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\field\Entity\FieldConfig.
 */
#[CoversClass(FieldConfig::class)]
#[Group('field')]
class FieldConfigEntityUnitTest extends UnitTestCase {

  /**
   * The entity type manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $entityTypeManager;

  /**
   * The entity field manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $uuid;

  /**
   * The mock field storage.
   *
   * @var \Drupal\field\FieldStorageConfigInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fieldStorage;

  /**
   * The mock field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $fieldTypePluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeId = $this->randomMachineName();

    $this->entityTypeManager = $this->createStub(EntityTypeManagerInterface::class);
    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);

    $this->uuid = $this->createStub(UuidInterface::class);

    $this->fieldTypePluginManager = $this->createStub(FieldTypePluginManagerInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_field.manager', $this->entityFieldManager);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('uuid', $this->uuid);
    $container->set('plugin.manager.field.field_type', $this->fieldTypePluginManager);
    \Drupal::setContainer($container);

    // Create a mock FieldStorageConfig object.
    $this->fieldStorage = $this->createMock('\Drupal\field\FieldStorageConfigInterface');
    $this->fieldStorage
      ->method('getType')
      ->willReturn('test_field');
    $this->fieldStorage
      ->method('getName')
      ->willReturn('field_test');
    $this->fieldStorage
      ->method('getSettings')
      ->willReturn([]);
    // Place the field in the mocked entity field manager's field registry.
    $this->entityFieldManager
      ->method('getFieldStorageDefinitions')
      ->with('test_entity_type')
      ->willReturn([
        $this->fieldStorage->getName() => $this->fieldStorage,
      ]);
  }

  /**
   * Tests calculate dependencies.
   */
  public function testCalculateDependencies(): void {
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions');

    // Mock the interfaces necessary to create a dependency on a bundle entity.
    $target_entity_type = $this->createStub(EntityTypeInterface::class);
    $target_entity_type
      ->method('getBundleConfigDependency')
      ->willReturn(['type' => 'config', 'name' => 'test.test_entity_type.id']);

    $this->entityTypeManager
      ->method('getDefinition')
      ->willReturnMap([
        [$this->entityTypeId, TRUE, $this->createStub(ConfigEntityTypeInterface::class)],
        ['test_entity_type', TRUE, $target_entity_type],
      ]);

    $this->fieldTypePluginManager
      ->method('getDefinition')
      ->willReturn([
        'provider' => 'test_module',
        'config_dependencies' => ['module' => ['test_module2']],
        'class' => '\Drupal\Tests\field\Unit\DependencyFieldItem',
      ]);

    $this->fieldStorage->expects($this->once())
      ->method('getConfigDependencyName')
      ->willReturn('field.storage.test_entity_type.test_field');

    $field = new FieldConfig([
      'field_name' => $this->fieldStorage->getName(),
      'entity_type' => 'test_entity_type',
      'bundle' => 'test_bundle',
      'field_type' => 'test_field',
    ], $this->entityTypeId);
    $dependencies = $field->calculateDependencies()->getDependencies();
    $this->assertContains('field.storage.test_entity_type.test_field', $dependencies['config']);
    $this->assertContains('test.test_entity_type.id', $dependencies['config']);
    $this->assertEquals(['test_module', 'test_module2', 'test_module3'], $dependencies['module']);
  }

  /**
   * Tests that invalid bundles are handled.
   */
  public function testCalculateDependenciesIncorrectBundle(): void {
    $this->entityFieldManager->expects($this->never())
      ->method('getFieldStorageDefinitions');
    $this->fieldStorage->expects($this->never())
      ->method('getType');

    $storage = $this->createMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $storage->expects($this->once())
      ->method('load')
      ->with('test_bundle_not_exists')
      ->willReturn(NULL);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturn($storage);

    $target_entity_type = new EntityType([
      'id' => 'test_entity_type',
      'bundle_entity_type' => 'bundle_entity_type',
    ]);

    $this->entityTypeManager
      ->method('getDefinition')
      ->willReturnMap([
        [$this->entityTypeId, TRUE, $this->createStub(ConfigEntityTypeInterface::class)],
        ['test_entity_type', TRUE, $target_entity_type],
      ]);

    $this->fieldTypePluginManager
      ->method('getDefinition')
      ->willReturn([
        'provider' => 'test_module',
        'config_dependencies' => ['module' => ['test_module2']],
        'class' => '\Drupal\Tests\field\Unit\DependencyFieldItem',
      ]);

    $field = new FieldConfig([
      'field_name' => $this->fieldStorage->getName(),
      'entity_type' => 'test_entity_type',
      'bundle' => 'test_bundle_not_exists',
      'field_type' => 'test_field',
    ], $this->entityTypeId);
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Missing bundle entity, entity type bundle_entity_type, entity id test_bundle_not_exists.');
    $field->calculateDependencies();
  }

  /**
   * Tests on dependency removal.
   */
  public function testOnDependencyRemoval(): void {
    $this->entityFieldManager->expects($this->never())
      ->method('getFieldStorageDefinitions');
    $this->fieldStorage->expects($this->never())
      ->method('getType');

    $this->fieldTypePluginManager
      ->method('getDefinition')
      ->willReturn(['class' => '\Drupal\Tests\field\Unit\DependencyFieldItem']);

    $field = new FieldConfig([
      'field_name' => $this->fieldStorage->getName(),
      'entity_type' => 'test_entity_type',
      'bundle' => 'test_bundle',
      'field_type' => 'test_field',
      'dependencies' => [
        'module' => [
          'fruiter',
        ],
      ],
      'third_party_settings' => [
        'fruiter' => [
          'fruit' => 'apple',
        ],
      ],
    ]);
    $changed = $field->onDependencyRemoval(['module' => ['fruiter']]);
    $this->assertTrue($changed);
  }

  /**
   * Tests to array.
   */
  public function testToArray(): void {
    $this->entityFieldManager->expects($this->never())
      ->method('getFieldStorageDefinitions');
    $this->fieldStorage->expects($this->never())
      ->method('getType');

    $field = new FieldConfig([
      'field_name' => $this->fieldStorage->getName(),
      'entity_type' => 'test_entity_type',
      'bundle' => 'test_bundle',
      'field_type' => 'test_field',
    ], $this->entityTypeId);

    $expected = [
      'id' => 'test_entity_type.test_bundle.field_test',
      'uuid' => NULL,
      'status' => TRUE,
      'langcode' => 'en',
      'field_name' => 'field_test',
      'entity_type' => 'test_entity_type',
      'bundle' => 'test_bundle',
      'label' => '',
      'description' => '',
      'required' => FALSE,
      'default_value' => [],
      'default_value_callback' => '',
      'settings' => [],
      'dependencies' => [],
      'field_type' => 'test_field',
    ];
    $entity_type = $this->createMock(ConfigEntityTypeInterface::class);
    $this->entityTypeManager
      ->method('getDefinition')
      ->willReturn($entity_type);
    $entity_type->expects($this->once())
      ->method('getKey')
      ->with('id')
      ->willReturn('id');
    $entity_type->expects($this->once())
      ->method('getPropertiesToExport')
      ->with('test_entity_type.test_bundle.field_test')
      ->willReturn(array_combine(array_keys($expected), array_keys($expected)));

    $export = $field->toArray();
    $this->assertEquals($expected, $export);
  }

  /**
   * Tests get type.
   */
  public function testGetType(): void {
    // Ensure that FieldConfig::getType() is not delegated to
    // FieldStorage.
    $this->entityFieldManager->expects($this->never())
      ->method('getFieldStorageDefinitions');
    $this->fieldStorage->expects($this->never())
      ->method('getType');

    $field = new FieldConfig([
      'field_name' => $this->fieldStorage->getName(),
      'entity_type' => 'test_entity_type',
      'bundle' => 'test_bundle',
      'field_type' => 'test_field',
    ], $this->entityTypeId);

    $this->assertEquals('test_field', $field->getType());
  }

}

/**
 * A test class.
 *
 * @see \Drupal\Tests\field\Unit\FieldConfigEntityUnitTest::testCalculateDependencies()
 */
class DependencyFieldItem {

  /**
   * Gets the dependencies for this field item.
   */
  public static function calculateDependencies(FieldDefinitionInterface $definition) {
    return ['module' => ['test_module3']];
  }

  /**
   * Informs the entity that entities it depends on will be deleted.
   */
  public static function onDependencyRemoval($field_config, $dependencies) {
  }

}
