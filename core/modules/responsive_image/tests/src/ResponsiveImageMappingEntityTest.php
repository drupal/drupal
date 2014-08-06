<?php

/**
 * @file
 * Contains \Drupal\responsive_image\Tests\ResponsiveImageMappingEntityTest.
 */

namespace Drupal\responsive_image\Tests;

use Drupal\responsive_image\Entity\ResponsiveImageMapping;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\responsive_image\Entity\ResponsiveImageMapping
 * @group responsive_image
 */
class ResponsiveImageMappingEntityTest extends UnitTestCase {

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
   * The ID of the breakpoint group used for testing.
   *
   * @var string
   */
  protected $breakpointGroupId;

  /**
   * The breakpoint group used for testing.
   *
   * @var \Drupal\breakpoint\Entity\BreakpointGroup|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $breakpointGroup;

  /**
   * The breakpoint group storage used for testing.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $breakpointGroupStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->entityTypeId = $this->randomMachineName();
    $this->provider = $this->randomMachineName();
    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
                     ->method('getProvider')
                     ->will($this->returnValue($this->provider));

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
                        ->method('getDefinition')
                        ->with($this->entityTypeId)
                        ->will($this->returnValue($this->entityType));

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $this->breakpointGroupId = $this->randomMachineName(9);
    $this->breakpointGroup = $this->getMock('Drupal\breakpoint\Entity\BreakpointGroup', array(), array(array('name' => 'test', 'id' => $this->breakpointGroupId)));

    $this->breakpointGroupStorage = $this->getMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $this->breakpointGroupStorage
      ->expects($this->any())
      ->method('load')
      ->with($this->breakpointGroupId)
      ->will($this->returnValue($this->breakpointGroup));

    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValue($this->breakpointGroupStorage));

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $responsive_image_mapping = new ResponsiveImageMapping(array(), $this->entityTypeId);
    // Set the breakpoint group after creating the entity to avoid the calls
    // in the constructor.
    $responsive_image_mapping->setBreakpointGroup($this->breakpointGroupId);
    $this->breakpointGroup->expects($this->once())
      ->method('getConfigDependencyName')
      ->will($this->returnValue('breakpoint.breakpoint_group.' . $this->breakpointGroupId));

    $dependencies = $responsive_image_mapping->calculateDependencies();
    $this->assertContains('breakpoint.breakpoint_group.' . $this->breakpointGroupId, $dependencies['entity']);
  }

}
