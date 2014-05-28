<?php

/**
 * @file
 * Contains \Drupal\breakpoint\Tests\BreakpointConfigEntityUnitTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Entity\Breakpoint;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\breakpoint\Entity\Breakpoint
 *
 * @group Drupal
 * @group Config
 * @group Breakpoint
 */
class BreakpointConfigEntityUnitTest extends UnitTestCase {

  /**
   * The entity under test.
   *
   * @var \Drupal\breakpoint\Entity\Breakpoint|\PHPUnit_Framework_MockObject_MockObject
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
      'name' => '\Drupal\breakpoint\Entity\Breakpoint unit test',
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
   * @covers ::calculateDependencies
   */
  public function testCalculateDependenciesModule() {
    $values = array(
      'name' => 'test',
      'source' => 'test_module',
      'sourceType' => Breakpoint::SOURCE_TYPE_MODULE,
    );
    $entity = new Breakpoint($values, $this->entityTypeId);

    $dependencies = $entity->calculateDependencies();
    $this->assertArrayNotHasKey('theme', $dependencies);
    $this->assertContains('test_module', $dependencies['module']);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependenciesTheme() {
    $values = array(
      'name' => 'test',
      'source' => 'test_theme',
      'sourceType' => Breakpoint::SOURCE_TYPE_THEME,
    );
    $entity = new Breakpoint($values, $this->entityTypeId);

    $dependencies = $entity->calculateDependencies();
    $this->assertArrayNotHasKey('module', $dependencies);
    $this->assertContains('test_theme', $dependencies['theme']);
  }

  /**
   * @expectedException \Drupal\breakpoint\InvalidBreakpointNameException
   */
  public function testNameException () {
    new Breakpoint(array(
      'label' => $this->randomName(),
      'source' => 'custom_module',
      'sourceType' => 'oops',
    ));
  }

}
