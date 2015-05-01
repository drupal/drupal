<?php

/**
 * @file
 * Contains \Drupal\Tests\field\Unit\FieldConfigEntityUnitTest.
 */

namespace Drupal\Tests\field\Unit;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
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
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * The entity manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $uuid;

  /**
   * The mock field storage.
   *
   * @var \Drupal\field\FieldStorageConfigInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $fieldStorage;

  /**
   * The typed configuration manager used for testing.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedConfigManager;

  /**
   * The mock field type plugin manager;
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $fieldTypePluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityTypeId = $this->randomMachineName();
    $this->entityType = $this->getMock('\Drupal\Core\Config\Entity\ConfigEntityTypeInterface');

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $this->typedConfigManager = $this->getMock('Drupal\Core\Config\TypedConfigManagerInterface');

    $this->fieldTypePluginManager = $this->getMock('Drupal\Core\Field\FieldTypePluginManagerInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    $container->set('config.typed', $this->typedConfigManager);
    $container->set('plugin.manager.field.field_type', $this->fieldTypePluginManager);
    \Drupal::setContainer($container);

    // Create a mock FieldStorageConfig object.
    $this->fieldStorage = $this->getMock('\Drupal\field\FieldStorageConfigInterface');
    $this->fieldStorage->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('test_field'));
    $this->fieldStorage->expects($this->any())
      ->method('getName')
      ->will($this->returnValue('field_test'));
    $this->fieldStorage->expects($this->any())
      ->method('getSettings')
      ->willReturn(array());
    // Place the field in the mocked entity manager's field registry.
    $this->entityManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity_type')
      ->will($this->returnValue(array(
        $this->fieldStorage->getName() => $this->fieldStorage,
      )));
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    // Mock the interfaces necessary to create a dependency on a bundle entity.
    $bundle_entity = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityInterface');
    $bundle_entity->expects($this->any())
      ->method('getConfigDependencyName')
      ->will($this->returnValue('test.test_entity_type.id'));

    $storage = $this->getMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $storage->expects($this->any())
      ->method('load')
      ->with('test_bundle')
      ->will($this->returnValue($bundle_entity));

    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->with('bundle_entity_type')
      ->will($this->returnValue($storage));

    $target_entity_type = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $target_entity_type->expects($this->any())
      ->method('getBundleEntityType')
      ->will($this->returnValue('bundle_entity_type'));

    $this->entityManager->expects($this->at(0))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
    $this->entityManager->expects($this->at(1))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
    $this->entityManager->expects($this->at(2))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
    $this->entityManager->expects($this->at(3))
      ->method('getDefinition')
      ->with('test_entity_type')
      ->willReturn($target_entity_type);

    $this->fieldTypePluginManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_field')
      ->willReturn(['provider' => 'test_module', 'config_dependencies' =>['module' => ['test_module2']], 'class' => '\Drupal\Tests\field\Unit\DependencyFieldItem']);

    $this->fieldStorage->expects($this->once())
      ->method('getConfigDependencyName')
      ->will($this->returnValue('field.storage.test_entity_type.test_field'));

    $field = new FieldConfig(array(
      'field_name' => $this->fieldStorage->getName(),
      'entity_type' => 'test_entity_type',
      'bundle' => 'test_bundle',
      'field_type' => 'test_field',
    ), $this->entityTypeId);
    $dependencies = $field->calculateDependencies();
    $this->assertContains('field.storage.test_entity_type.test_field', $dependencies['config']);
    $this->assertContains('test.test_entity_type.id', $dependencies['config']);
    $this->assertEquals(['test_module', 'test_module2', 'test_module3'], $dependencies['module']);
  }

  /**
   * Test that invalid bundles are handled.
   *
   * @expectedException \LogicException
   * @expectedExceptionMessage Missing bundle entity, entity type <em class="placeholder">bundle_entity_type</em>, entity id <em class="placeholder">test_bundle_not_exists</em>.
   */
  public function testCalculateDependenciesIncorrectBundle() {
    $storage = $this->getMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $storage->expects($this->any())
      ->method('load')
      ->with('test_bundle_not_exists')
      ->will($this->returnValue(NULL));

    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->with('bundle_entity_type')
      ->will($this->returnValue($storage));

    $target_entity_type = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $target_entity_type->expects($this->any())
      ->method('getBundleEntityType')
      ->will($this->returnValue('bundle_entity_type'));

    $this->entityManager->expects($this->at(0))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
    $this->entityManager->expects($this->at(1))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
    $this->entityManager->expects($this->at(2))
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
    $this->entityManager->expects($this->at(3))
      ->method('getDefinition')
      ->with('test_entity_type')
      ->willReturn($target_entity_type);

    $this->fieldTypePluginManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_field')
      ->willReturn(['provider' => 'test_module', 'config_dependencies' =>['module' => ['test_module2']], 'class' => '\Drupal\Tests\field\Unit\DependencyFieldItem']);

    $field = new FieldConfig(array(
      'field_name' => $this->fieldStorage->getName(),
      'entity_type' => 'test_entity_type',
      'bundle' => 'test_bundle_not_exists',
      'field_type' => 'test_field',
    ), $this->entityTypeId);
    $field->calculateDependencies();
  }

  /**
   * @covers ::toArray
   */
  public function testToArray() {
    $field = new FieldConfig(array(
      'field_name' => $this->fieldStorage->getName(),
      'entity_type' => 'test_entity_type',
      'bundle' => 'test_bundle',
      'field_type' => 'test_field',
    ), $this->entityTypeId);

    $expected = array(
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
      'default_value' => array(),
      'default_value_callback' => '',
      'settings' => array(),
      'dependencies' => array(),
      'field_type' => 'test_field',
    );
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));
    $this->entityType->expects($this->once())
      ->method('getKey')
      ->with('id')
      ->will($this->returnValue('id'));
    $this->typedConfigManager->expects($this->once())
      ->method('getDefinition')
      ->will($this->returnValue(array('mapping' => array_fill_keys(array_keys($expected), ''))));

    $export = $field->toArray();
    $this->assertEquals($expected, $export);
  }

  /**
   * @covers ::getType
   */
  public function testGetType() {
    // Ensure that FieldConfig::getType() is not delegated to
    // FieldStorage.
    $this->entityManager->expects($this->never())
      ->method('getFieldStorageDefinitions');
    $this->fieldStorage->expects($this->never())
      ->method('getType');

    $field = new FieldConfig(array(
      'field_name' => $this->fieldStorage->getName(),
      'entity_type' => 'test_entity_type',
      'bundle' => 'test_bundle',
      'field_type' => 'test_field',
    ), $this->entityTypeId);

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

}
