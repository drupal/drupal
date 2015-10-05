<?php

/**
 * @file
 * Contains \Drupal\Tests\field\Unit\FieldStorageConfigEntityUnitTest.
 */

namespace Drupal\Tests\field\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\field\Entity\FieldStorageConfig
 *
 * @group field
 */
class FieldStorageConfigEntityUnitTest extends UnitTestCase {

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
  protected function setUp() {
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
    // Create a mock entity type for FieldStorageConfig.
    $fieldStorageConfigentityType = $this->getMock('\Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $fieldStorageConfigentityType->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('field'));

    // Create a mock entity type to attach the field to.
    $attached_entity_type_id = $this->randomMachineName();
    $attached_entity_type = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $attached_entity_type->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('entity_provider_module'));

    // Get definition is called three times. Twice in
    // ConfigEntityBase::addDependency() to get the provider of the field config
    // entity type and once in FieldStorageConfig::calculateDependencies() to
    // get the provider of the entity type that field is attached to.
    $this->entityManager->expects($this->at(0))
      ->method('getDefinition')
      ->with('field_storage_config')
      ->will($this->returnValue($fieldStorageConfigentityType));
    $this->entityManager->expects($this->at(1))
      ->method('getDefinition')
      ->with($attached_entity_type_id)
      ->will($this->returnValue($attached_entity_type));
    $this->entityManager->expects($this->at(2))
      ->method('getDefinition')
      ->with('field_storage_config')
      ->will($this->returnValue($fieldStorageConfigentityType));

    $field_storage = new FieldStorageConfig(array(
      'entity_type' => $attached_entity_type_id,
      'field_name' => 'test_field',
      'type' => 'test_field_type',
      'module' => 'test_module',
    ));

    $dependencies = $field_storage->calculateDependencies()->getDependencies();
    $this->assertContains('test_module', $dependencies['module']);
    $this->assertContains('entity_provider_module', $dependencies['module']);
  }

}
