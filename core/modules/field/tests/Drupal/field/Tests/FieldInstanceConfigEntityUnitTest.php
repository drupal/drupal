<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldInstanceConfigEntityUnitTest.
 */

namespace Drupal\field\Tests;

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

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    \Drupal::setContainer($container);

  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $field = $this->getMock('\Drupal\field\FieldConfigInterface');
    // The field name property is public and accessed this way in the field
    // instance config entity constructor.
    $field->name = 'test_field';
    $field->expects($this->once())
      ->method('getConfigDependencyName')
      ->will($this->returnValue('field.field.test_entity_type.test_field'));

    $field_storage = $this->getMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $field_storage
      ->expects($this->any())
      ->method('load')
      ->with('test_entity_type.test_field')
      ->will($this->returnValue($field));

    // Mock the interfaces necessary to create a dependency on a bundle entity.
    $bundle_entity = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityInterface');
    $bundle_entity->expects($this->any())
      ->method('getConfigDependencyName')
      ->will($this->returnValue('test.test_entity_type.id'));

    $storage = $this->getMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $storage
      ->expects($this->any())
      ->method('load')
      ->with('test_bundle')
      ->will($this->returnValue($bundle_entity));

    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValueMap(array(
        array('field_config', $field_storage),
        array('bundle_entity_type', $storage),
      )));

    $target_entity_type = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $target_entity_type->expects($this->any())
      ->method('getBundleEntityType')
      ->will($this->returnValue('bundle_entity_type'));

    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($target_entity_type));

    $values = array('field_name' => 'test_field', 'entity_type' => 'test_entity_type', 'bundle' => 'test_bundle');
    $entity = new FieldInstanceConfig($values, $this->entityTypeId);
    $dependencies = $entity->calculateDependencies();
    $this->assertContains('field.field.test_entity_type.test_field', $dependencies['entity']);
    $this->assertContains('test.test_entity_type.id', $dependencies['entity']);
  }

}
