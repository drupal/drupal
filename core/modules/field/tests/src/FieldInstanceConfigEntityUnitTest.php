<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldInstanceConfigEntityUnitTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\field\Entity\FieldInstanceConfig;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\field\Entity\FieldInstanceConfig
 *
 * @group Drupal
 * @group Config
 */
class FieldInstanceConfigEntityUnitTest extends UnitTestCase {

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
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\field\Entity\FieldInstanceConfig unit test',
      'group' => 'Entity',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->entityTypeId = $this->randomName();
    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $this->typedConfigManager = $this->getMock('Drupal\Core\Config\TypedConfigManagerInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    $container->set('config.typed', $this->typedConfigManager);
    \Drupal::setContainer($container);

    // Create a mock FieldConfig object.
    $this->field = $this->getMock('\Drupal\field\FieldConfigInterface');
    $this->field->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('test_field'));
    $this->field->expects($this->any())
      ->method('getName')
      ->will($this->returnValue('field_test'));

    // Place the field in the mocked entity manager's field registry.
    $this->entityManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity_type')
      ->will($this->returnValue(array(
        $this->field->getName() => $this->field,
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

    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($target_entity_type));

    $this->field->expects($this->once())
      ->method('getConfigDependencyName')
      ->will($this->returnValue('field.field.test_entity_type.test_field'));

    $values = array('field_name' => $this->field->getName(), 'entity_type' => 'test_entity_type', 'bundle' => 'test_bundle');
    $entity = new FieldInstanceConfig($values, $this->entityTypeId);
    $dependencies = $entity->calculateDependencies();
    $this->assertContains('field.field.test_entity_type.test_field', $dependencies['entity']);
    $this->assertContains('test.test_entity_type.id', $dependencies['entity']);
  }

  /**
   * @covers ::toArray()
   */
  public function testToArray() {
    $values = array('field_name' => $this->field->getName(), 'entity_type' => 'test_entity_type', 'bundle' => 'test_bundle');
    $instance = new FieldInstanceConfig($values, $this->entityTypeId);

    $expected = array(
      'id' => 'test_entity_type.test_bundle.field_test',
      'uuid' => NULL,
      'status' => TRUE,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'field_uuid' => NULL,
      'field_name' => 'field_test',
      'entity_type' => 'test_entity_type',
      'bundle' => 'test_bundle',
      'label' => '',
      'description' => '',
      'required' => FALSE,
      'default_value' => array(),
      'default_value_function' => '',
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

    $export = $instance->toArray();
    $this->assertEquals($expected, $export);
  }
}
