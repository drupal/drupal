<?php

namespace Drupal\Tests\Core\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbManager;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Breadcrumb\BreadcrumbManager
 * @group Breadcrumb
 */
class BreadcrumbManagerTest extends UnitTestCase {

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The breadcrumb object.
   *
   * @var \Drupal\Core\Breadcrumb\Breadcrumb
   */
  protected $breadcrumb;

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
    $this->breadcrumb = new Breadcrumb();

    $this->container = new ContainerBuilder();
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens()->willReturn(TRUE);
    $cache_contexts_manager->reveal();
    $this->container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($this->container);
  }

  /**
   * Tests the breadcrumb manager without any set breadcrumb.
   */
  public function testBuildWithoutBuilder() {
    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('system_breadcrumb', $this->breadcrumb, $route_match, ['builder' => NULL]);

    $breadcrumb = $this->breadcrumbManager->build($this->getMock('Drupal\Core\Routing\RouteMatchInterface'));
    $this->assertEquals([], $breadcrumb->getLinks());
    $this->assertEquals([], $breadcrumb->getCacheContexts());
    $this->assertEquals([], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

  /**
   * Tests the build method with a single breadcrumb builder.
   */
  public function testBuildWithSingleBuilder() {
    $builder = $this->getMock('Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface');
    $links = array('<a href="/example">Test</a>');
    $this->breadcrumb->setLinks($links);
    $this->breadcrumb->addCacheContexts(['foo'])->addCacheTags(['bar']);

    $builder->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(TRUE));

    $builder->expects($this->once())
      ->method('build')
      ->willReturn($this->breadcrumb);

    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('system_breadcrumb', $this->breadcrumb, $route_match, array('builder' => $builder));

    $this->breadcrumbManager->addBuilder($builder, 0);

    $breadcrumb = $this->breadcrumbManager->build($route_match);
    $this->assertEquals($links, $breadcrumb->getLinks());
    $this->assertEquals(['foo'], $breadcrumb->getCacheContexts());
    $this->assertEquals(['bar'], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
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
    $links2 = array('<a href="/example2">Test2</a>');
    $this->breadcrumb->setLinks($links2);
    $this->breadcrumb->addCacheContexts(['baz'])->addCacheTags(['qux']);
    $builder2->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(TRUE));
    $builder2->expects($this->once())
      ->method('build')
      ->willReturn($this->breadcrumb);

    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('system_breadcrumb', $this->breadcrumb, $route_match, array('builder' => $builder2));

    $this->breadcrumbManager->addBuilder($builder1, 0);
    $this->breadcrumbManager->addBuilder($builder2, 10);

    $breadcrumb = $this->breadcrumbManager->build($route_match);
    $this->assertEquals($links2, $breadcrumb->getLinks());
    $this->assertEquals(['baz'], $breadcrumb->getCacheContexts());
    $this->assertEquals(['qux'], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
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
    $links2 = ['<a href="/example2">Test2</a>'];
    $this->breadcrumb->setLinks($links2);
    $this->breadcrumb->addCacheContexts(['baz'])->addCacheTags(['qux']);
    $builder2->expects($this->once())
      ->method('applies')
      ->will($this->returnValue(TRUE));
    $builder2->expects($this->once())
      ->method('build')
      ->willReturn($this->breadcrumb);

    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('system_breadcrumb', $this->breadcrumb, $route_match, array('builder' => $builder2));

    $this->breadcrumbManager->addBuilder($builder1, 10);
    $this->breadcrumbManager->addBuilder($builder2, 0);

    $breadcrumb = $this->breadcrumbManager->build($route_match);
    $this->assertEquals($links2, $breadcrumb->getLinks());
    $this->assertEquals(['baz'], $breadcrumb->getCacheContexts());
    $this->assertEquals(['qux'], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
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
