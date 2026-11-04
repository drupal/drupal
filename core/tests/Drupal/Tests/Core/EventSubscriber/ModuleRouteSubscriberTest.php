<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\EventSubscriber\ModuleRouteSubscriber;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests Drupal\Core\EventSubscriber\ModuleRouteSubscriber.
 */
#[CoversClass(ModuleRouteSubscriber::class)]
#[Group('EventSubscriber')]
class ModuleRouteSubscriberTest extends UnitTestCase {

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface&Stub $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moduleHandler = $this->createStub(ModuleHandlerInterface::class);

    $value_map = [
      ['enabled', TRUE],
      ['disabled', FALSE],
    ];

    $this->moduleHandler
      ->method('moduleExists')
      ->willReturnMap($value_map);
  }

  /**
   * Tests that removeRoute() removes routes when the module is not enabled.
   *
   * @param string $route_name
   *   The machine name for the route.
   * @param array $requirements
   *   An array of requirements to use for the route.
   * @param bool $removed
   *   Whether or not the route is expected to be removed from the collection.
   *
   * @see ::onAlterRoutes
   */
  #[DataProvider('providerTestRemoveRoute')]
  public function testRemoveRoute(string $route_name, array $requirements, bool $removed): void {
    $collection = new RouteCollection();
    $route = new Route('', [], $requirements);
    $collection->add($route_name, $route);

    $event = new RouteBuildEvent($collection);
    $route_subscriber = new ModuleRouteSubscriber($this->moduleHandler);
    $route_subscriber->onAlterRoutes($event);

    if ($removed) {
      $this->assertNull($collection->get($route_name));
    }
    else {
      $this->assertInstanceOf('Symfony\Component\Routing\Route', $collection->get($route_name));
    }
  }

  /**
   * Data provider for testRemoveRoute().
   */
  public static function providerTestRemoveRoute(): array {
    return [
      ['enabled', ['_module_dependencies' => 'enabled'], FALSE],
      ['disabled', ['_module_dependencies' => 'disabled'], TRUE],
      ['enabled_or', ['_module_dependencies' => 'disabled,enabled'], FALSE],
      ['enabled_or', ['_module_dependencies' => 'enabled,disabled'], FALSE],
      ['disabled_or', ['_module_dependencies' => 'disabled,disabled'], TRUE],
      ['enabled_and', ['_module_dependencies' => 'enabled+enabled'], FALSE],
      ['enabled_and', ['_module_dependencies' => 'enabled+disabled'], TRUE],
      ['enabled_and', ['_module_dependencies' => 'disabled+enabled'], TRUE],
      ['disabled_and', ['_module_dependencies' => 'disabled+disabled'], TRUE],
      ['no_dependencies', [], FALSE],
    ];
  }

}
