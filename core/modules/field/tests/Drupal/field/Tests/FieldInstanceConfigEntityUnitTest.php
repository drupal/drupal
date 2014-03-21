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
   * The field info provider.
   *
   * @var \Drupal\field\FieldInfo|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $fieldInfo;

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
    $this->entityType->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('entity'));

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $this->fieldInfo = $this->getMockBuilder('\Drupal\field\FieldInfo')
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    $container->set('field.info', $this->fieldInfo);
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
    $this->fieldInfo->expects($this->any())
      ->method('getField')
      ->with('test_entity_type', 'test_field')
      ->will($this->returnValue($field));
    $values = array('field_name' => 'test_field', 'entity_type' => 'test_entity_type', $this->entityTypeId, 'bundle' => 'test_bundle');
    $entity = new FieldInstanceConfig($values, $this->entityTypeId);
    $dependencies = $entity->calculateDependencies();
    $this->assertContains('field.field.test_entity_type.test_field', $dependencies['entity']);
  }

}
