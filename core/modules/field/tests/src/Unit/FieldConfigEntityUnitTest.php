<?php

/**
 * @file
 * Contains \Drupal\Tests\field\Unit\FieldConfigEntityUnitTest.
 */

namespace Drupal\Tests\field\Unit;

use Drupal\Core\Entity\EntityType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\field\Entity\FieldConfig
 * @group field
 */
class FieldConfigEntityUnitTest extends UnitTestCase {

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * The entity type manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
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
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit\Framework\MockObject\MockObject
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
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fieldTypePluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityTypeId = $this->randomMachineName();
    $this->entityType = $this->createMock('\Drupal\Core\Config\Entity\ConfigEntityTypeInterface');

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);

    $this->uuid = $this->createMock('\Drupal\Component\Uuid\UuidInterface');

    $this->fieldTypePluginManager = $this->createMock('Drupal\Core\Field\FieldTypePluginManagerInterface');

    $container = new ContainerBuilder();
    $container->set('entity_field.manager', $this->entityFieldManager);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('uuid', $this->uuid);
    $container->set('plugin.manager.field.field_type', $this->fieldTypePluginManager);
    \Drupal::setContainer($container);

    // Create a mock FieldStorageConfig object.
    $this->fieldStorage = $this->createMock('\Drupal\field\FieldStorageConfigInterface');
    $this->fieldStorage->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('test_field'));
    $this->fieldStorage->expects($this->any())
      ->method('getName')
      ->will($this->returnValue('field_test'));
    $this->fieldStorage->expects($this->any())
      ->method('getSettings')
      ->willReturn([]);
    // Place the field in the mocked entity field manager's field registry.
    $this->entityFieldManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity_type')
      ->will($this->returnValue([
        $this->fieldStorage->getName() => $this->fieldStorage,
      ]));
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    // Mock the interfaces necessary to create a dependency on a bundle entity.
    $target_entity_type = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $target_entity_type->expects($this->any())
      ->method('getBundleConfigDependency')
      ->will($this->returnValue(['type' => 'config', 'name' => 'test.test_entity_type.id']));

    $this->entityTypeManager->expects($this->at(0))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
    $this->entityTypeManager->expects($this->at(1))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
    $this->entityTypeManager->expects($this->at(2))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
    $this->entityTypeManager->expects($this->at(3))
      ->method('getDefinition')
      ->with('test_entity_type')
      ->willReturn($target_entity_type);

    $this->fieldTypePluginManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_field')
      ->willReturn(['provider' => 'test_module', 'config_dependencies' => ['module' => ['test_module2']], 'class' => '\Drupal\Tests\field\Unit\DependencyFieldItem']);

    $this->fieldStorage->expects($this->once())
      ->method('getConfigDependencyName')
      ->will($this->returnValue('field.storage.test_entity_type.test_field'));

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
  public function testCalculateDependenciesIncorrectBundle() {
    $storage = $this->createMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $storage->expects($this->any())
      ->method('load')
      ->with('test_bundle_not_exists')
      ->will($this->returnValue(NULL));

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('bundle_entity_type')
      ->will($this->returnValue($storage));

    $target_entity_type = new EntityType([
      'id' => 'test_entity_type',
      'bundle_entity_type' => 'bundle_entity_type',
    ]);

    $this->entityTypeManager->expects($this->at(0))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
    $this->entityTypeManager->expects($this->at(1))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
    $this->entityTypeManager->expects($this->at(2))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
    $this->entityTypeManager->expects($this->at(3))
      ->method('getDefinition')
      ->with('test_entity_type')
      ->willReturn($target_entity_type);

    $this->fieldTypePluginManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_field')
      ->willReturn(['provider' => 'test_module', 'config_dependencies' => ['module' => ['test_module2']], 'class' => '\Drupal\Tests\field\Unit\DependencyFieldItem']);

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
   * @covers ::onDependencyRemoval
   */
  public function testOnDependencyRemoval() {
    $this->fieldTypePluginManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_field')
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
   * @covers ::toArray
   */
  public function testToArray() {
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
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));
    $this->entityType->expects($this->once())
      ->method('getKey')
      ->with('id')
      ->will($this->returnValue('id'));
    $this->entityType->expects($this->once())
      ->method('getPropertiesToExport')
      ->with('test_entity_type.test_bundle.field_test')
      ->will($this->returnValue(array_combine(array_keys($expected), array_keys($expected))));

    $export = $field->toArray();
    $this->assertEquals($expected, $export);
  }

  /**
   * @covers ::getType
   */
  public function testGetType() {
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

  public static function calculateDependencies(FieldDefinitionInterface $definition) {
    return ['module' => ['test_module3']];
  }

  public static function onDependencyRemoval($field_config, $dependencies) {
  }

}
