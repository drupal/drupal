<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Breadcrumb\BreadcrumbManagerTest.
 */

namespace Drupal\Tests\Core\Breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the breadcrumb manager.
 *
 * @group Drupal
 * @group Breadcrumb
 *
 * @coversDefaultClass \Drupal\Core\Breadcrumb\BreadcrumbManager
 */
class BreadcrumbManagerTest extends UnitTestCase {

  /**
   * The tested breadcrumb manager.
   *
   * @var \Drupal\Core\Breadcrumb\BreadcrumbManager
   */
  protected $breadcrumbManager;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->breadcrumbManager = new BreadcrumbManager($this->moduleHandler);
  }

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Breadcrumb manager',
      'description' => 'Tests the breadcrumb manager.',
      'group' => 'Menu'
    );
  }

  /**
   * Tests the breadcrumb manager without any set breadcrumb.
   */
  public function testBuildWithoutBuilder() {
    $result = $this->breadcrumbManager->build($this->getMock('Drupal\Core\Routing\RouteMatchInterface'));
    $this->assertEquals(array(), $result);
  }

  /**
   * Tests the build method with a single breadcrumb builder.
   */
  public function testBuildWithSingleBuilder() {
    $builder = $this->getMock('Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface');
    $breadcrumb = array('<a href="/example">Test</a>');

    $builder->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(TRUE));

    $builder->expects($this->once())
      ->method('build')
      ->will($this->returnValue($breadcrumb));

    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('system_breadcrumb', $breadcrumb, $route_match, array('builder' => $builder));

    $this->breadcrumbManager->addBuilder($builder, 0);

    $result = $this->breadcrumbManager->build($route_match);
    $this->assertEquals($breadcrumb, $result);
  }

  /**
   * Tests multiple breadcrumb builder with different priority.
   */
  public function testBuildWithMultipleApplyingBuilders() {
    $builder1 = $this->getMock('Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface');
    $builder1->expects($this->never())
      ->method('applies');
    $builder1->expects($this->never())
      ->method('build');

    $builder2 = $this->getMock('Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface');
    $breadcrumb2 = array('<a href="/example2">Test2</a>');
    $builder2->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(TRUE));
    $builder2->expects($this->once())
      ->method('build')
      ->will($this->returnValue($breadcrumb2));

    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('system_breadcrumb', $breadcrumb2, $route_match, array('builder' => $builder2));

    $this->breadcrumbManager->addBuilder($builder1, 0);
    $this->breadcrumbManager->addBuilder($builder2, 10);

    $result = $this->breadcrumbManager->build($route_match);
    $this->assertEquals($breadcrumb2, $result);
  }

  /**
   * Tests multiple breadcrumb builders of which one returns NULL.
   */
  public function testBuildWithOneNotApplyingBuilders() {
    $builder1 = $this->getMock('Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface');
    $builder1->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(FALSE));
    $builder1->expects($this->never())
      ->method('build');

    $builder2 = $this->getMock('Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface');
    $breadcrumb2 = array('<a href="/example2">Test2</a>');
    $builder2->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(TRUE));
    $builder2->expects($this->once())
      ->method('build')
      ->will($this->returnValue($breadcrumb2));

    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('system_breadcrumb', $breadcrumb2, $route_match, array('builder' => $builder2));

    $this->breadcrumbManager->addBuilder($builder1, 10);
    $this->breadcrumbManager->addBuilder($builder2, 0);

    $result = $this->breadcrumbManager->build($route_match);
    $this->assertEquals($breadcrumb2, $result);
  }

  /**
   * Tests a breadcrumb builder with a bad return value.
   *
   * @expectedException \UnexpectedValueException
   */
  public function testBuildWithInvalidBreadcrumbResult() {
    $builder = $this->getMock('Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface');
    $builder->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(TRUE));
    $builder->expects($this->once())
      ->method('build')
      ->will($this->returnValue('invalid_result'));

    $this->breadcrumbManager->addBuilder($builder, 0);
    $this->breadcrumbManager->build($this->getMock('Drupal\Core\Routing\RouteMatchInterface'));
  }

}
