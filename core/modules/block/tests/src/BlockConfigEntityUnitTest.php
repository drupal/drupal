<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockConfigEntityUnitTest.
 */

namespace Drupal\block\Tests;

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
  public function setUp() {
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
    // Mock the entity under test so that we can mock getPluginBags().
    $entity = $this->getMockBuilder('\Drupal\block\Entity\Block')
      ->setConstructorArgs(array($values, $this->entityTypeId))
      ->setMethods(array('getPluginBags'))
      ->getMock();
    // Create a configurable plugin that would add a dependency.
    $instance_id = $this->randomMachineName();
    $instance = new TestConfigurablePlugin(array(), $instance_id, array('provider' => 'test'));

    // Create a plugin bag to contain the instance.
    $plugin_bag = $this->getMockBuilder('\Drupal\Core\Plugin\DefaultPluginBag')
      ->disableOriginalConstructor()
      ->setMethods(array('get'))
      ->getMock();
    $plugin_bag->expects($this->atLeastOnce())
      ->method('get')
      ->with($instance_id)
      ->will($this->returnValue($instance));
    $plugin_bag->addInstanceId($instance_id);

    // Return the mocked plugin bag.
    $entity->expects($this->once())
      ->method('getPluginBags')
      ->will($this->returnValue(array($plugin_bag)));

    $dependencies = $entity->calculateDependencies();
    $this->assertContains('test', $dependencies['module']);
    $this->assertContains('stark', $dependencies['theme']);
  }

}
