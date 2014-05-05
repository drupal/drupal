<?php

/**
 * @file
 * Contains \Drupal\breakpoint\Tests\BreakpointGroupConfigEntityUnitTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Entity\Breakpoint;
use Drupal\breakpoint\Entity\BreakpointGroup;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\breakpoint\Entity\BreakpointGroup
 *
 * @group Drupal
 * @group Config
 * @group Breakpoint
 */
class BreakpointGroupConfigEntityUnitTest extends UnitTestCase {

  /**
   * The entity under test.
   *
   * @var \Drupal\breakpoint\Entity\BreakpointGroup|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

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
      'name' => '\Drupal\breakpoint\Entity\BreakpointGroup unit test',
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
      ->will($this->returnValue('breakpoint'));

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
   * Sets up the entity to test.
   */
  public function setUpEntity($values) {
    // Mocking the entity under test because the class contains calls to
    // procedural code.
    $this->entity = $this->getMockBuilder('\Drupal\breakpoint\Entity\BreakpointGroup')
      ->setConstructorArgs(array($values, $this->entityTypeId))
      ->setMethods(array('getBreakpoints'))
      ->getMock();
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependenciesModule() {
    $this->setUpEntity(
      array(
        'name' => 'test',
        'source' => 'test_module',
        'sourceType' => Breakpoint::SOURCE_TYPE_MODULE,
      )
    );
    $breakpoint = $this->getMock('\Drupal\breakpoint\BreakpointInterface');
    $breakpoint->expects($this->once())
      ->method('getConfigDependencyName')
      ->will($this->returnValue('breakpoint.breakpoint.test'));

    $this->entity->expects($this->once())
      ->method('getBreakpoints')
      ->will($this->returnValue(array($breakpoint)));

    $dependencies = $this->entity->calculateDependencies();
    $this->assertArrayNotHasKey('theme', $dependencies);
    $this->assertContains('test_module', $dependencies['module']);
    $this->assertContains('breakpoint.breakpoint.test', $dependencies['entity']);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependenciesTheme() {
    $this->setUpEntity(
      array(
        'name' => 'test',
        'source' => 'test_theme',
        'sourceType' => Breakpoint::SOURCE_TYPE_THEME,
      )
    );

    $breakpoint = $this->getMockBuilder('\Drupal\breakpoint\Entity\Breakpoint')
                       ->disableOriginalConstructor()->getMock();
    $breakpoint->expects($this->once())
               ->method('getConfigDependencyName')
               ->will($this->returnValue('breakpoint.breakpoint.test'));

    $this->entity->expects($this->once())
                 ->method('getBreakpoints')
                 ->will($this->returnValue(array($breakpoint)));

    $dependencies = $this->entity->calculateDependencies();
    $this->assertArrayNotHasKey('module', $dependencies);
    $this->assertContains('test_theme', $dependencies['theme']);
    $this->assertContains('breakpoint.breakpoint.test', $dependencies['entity']);
  }

  /**
   * @expectedException \Drupal\breakpoint\InvalidBreakpointNameException
   */
  public function testNameException () {
    new BreakpointGroup(array(
      'label' => $this->randomName(),
      'source' => 'custom_module',
      'sourceType' => 'oops',
    ));
  }

}
