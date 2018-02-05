<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\layout_builder\Routing\LayoutBuilderRoutes;
use Drupal\layout_builder\SectionStorage\SectionStorageDefinition;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\layout_builder\Routing\LayoutBuilderRoutes
 *
 * @group layout_builder
 */
class LayoutBuilderRoutesTest extends UnitTestCase {

  /**
   * The Layout Builder route builder.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   */
  protected $sectionStorageManager;

  /**
   * The Layout Builder route builder.
   *
   * @var \Drupal\layout_builder\Routing\LayoutBuilderRoutes
   */
  protected $routeBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->sectionStorageManager = $this->prophesize(SectionStorageManagerInterface::class);
    $this->routeBuilder = new LayoutBuilderRoutes($this->sectionStorageManager->reveal());
  }

  /**
   * @covers ::onAlterRoutes
   */
  public function testOnAlterRoutes() {
    $expected = [
      'test_route1' => new Route('/test/path1'),
      'test_route_shared' => new Route('/test/path/shared2'),
      'test_route2' => new Route('/test/path2'),
    ];

    $section_storage_first = $this->prophesize(SectionStorageInterface::class);
    $section_storage_first->buildRoutes(Argument::type(RouteCollection::class))->shouldBeCalled()->will(function ($args) {
      /** @var \Symfony\Component\Routing\RouteCollection $collection */
      $collection = $args[0];
      $collection->add('test_route_shared', new Route('/test/path/shared1'));
      $collection->add('test_route1', new Route('/test/path1'));
    });
    $section_storage_second = $this->prophesize(SectionStorageInterface::class);
    $section_storage_second->buildRoutes(Argument::type(RouteCollection::class))->shouldBeCalled()->will(function ($args) {
      /** @var \Symfony\Component\Routing\RouteCollection $collection */
      $collection = $args[0];
      $collection->add('test_route_shared', new Route('/test/path/shared2'));
      $collection->add('test_route2', new Route('/test/path2'));
    });

    $this->sectionStorageManager->loadEmpty('first')->willReturn($section_storage_first->reveal());
    $this->sectionStorageManager->loadEmpty('second')->willReturn($section_storage_second->reveal());
    $definitions['first'] = new SectionStorageDefinition();
    $definitions['second'] = new SectionStorageDefinition();
    $this->sectionStorageManager->getDefinitions()->willReturn($definitions);

    $collection = new RouteCollection();
    $event = new RouteBuildEvent($collection);
    $this->routeBuilder->onAlterRoutes($event);
    $this->assertEquals($expected, $collection->all());
    $this->assertSame(array_keys($expected), array_keys($collection->all()));
  }

}
