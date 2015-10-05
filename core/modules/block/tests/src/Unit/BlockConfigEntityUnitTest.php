<?php

/**
 * @file
 * Contains \Drupal\Tests\block\Unit\BlockConfigEntityUnitTest.
 */

namespace Drupal\Tests\block\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\Core\Plugin\Fixtures\TestConfigurablePlugin;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\block\Entity\Block
 * @group block
 */
class BlockConfigEntityUnitTest extends UnitTestCase {

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
  protected function setUp() {
    $this->entityTypeId = $this->randomMachineName();

    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('block'));

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));

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
    $values = array('theme' => 'stark');
    // Mock the entity under test so that we can mock getPluginCollections().
    $entity = $this->getMockBuilder('\Drupal\block\Entity\Block')
      ->setConstructorArgs(array($values, $this->entityTypeId))
      ->setMethods(array('getPluginCollections'))
      ->getMock();
    // Create a configurable plugin that would add a dependency.
    $instance_id = $this->randomMachineName();
    $instance = new TestConfigurablePlugin(array(), $instance_id, array('provider' => 'test'));

    // Create a plugin collection to contain the instance.
    $plugin_collection = $this->getMockBuilder('\Drupal\Core\Plugin\DefaultLazyPluginCollection')
      ->disableOriginalConstructor()
      ->setMethods(array('get'))
      ->getMock();
    $plugin_collection->expects($this->atLeastOnce())
      ->method('get')
      ->with($instance_id)
      ->will($this->returnValue($instance));
    $plugin_collection->addInstanceId($instance_id);

    // Return the mocked plugin collection.
    $entity->expects($this->once())
      ->method('getPluginCollections')
      ->will($this->returnValue(array($plugin_collection)));

    $dependencies = $entity->calculateDependencies()->getDependencies();
    $this->assertContains('test', $dependencies['module']);
    $this->assertContains('stark', $dependencies['theme']);
  }

}
