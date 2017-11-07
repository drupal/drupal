<?php

namespace Drupal\Tests\system\Unit\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\system\EventSubscriber\AdminRouteSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\system\EventSubscriber\AdminRouteSubscriber
 * @group system
 */
class AdminRouteSubscriberTest extends UnitTestCase {

  /**
   * @covers ::alterRoutes
   * @covers ::isHtmlRoute
   *
   * @dataProvider providerTestAlterRoutes
   */
  public function testAlterRoutes(Route $route, $is_admin) {
    $collection = new RouteCollection();
    $collection->add('the_route', $route);
    (new AdminRouteSubscriber())->onAlterRoutes(new RouteBuildEvent($collection));

    $this->assertSame($is_admin, $route->getOption('_admin_route'));
  }

  public function providerTestAlterRoutes() {
    $data = [];
    $data['non-admin'] = [
      new Route('/foo'),
      NULL,
    ];
    $data['admin prefix'] = [
      new Route('/admin/foo'),
      TRUE,
    ];
    $data['admin option'] = [
      (new Route('/foo'))
        ->setOption('_admin_route', TRUE),
      TRUE,
    ];
    $data['admin prefix, non-HTML format'] = [
      (new Route('/admin/foo'))
        ->setRequirement('_format', 'json'),
      NULL,
    ];
    $data['admin option, non-HTML format'] = [
      (new Route('/foo'))
        ->setRequirement('_format', 'json')
        ->setOption('_admin_route', TRUE),
      TRUE,
    ];
    $data['admin prefix, HTML format'] = [
      (new Route('/admin/foo'))
        ->setRequirement('_format', 'html'),
      TRUE,
    ];
    $data['admin prefix, multi-format including HTML'] = [
      (new Route('/admin/foo'))
        ->setRequirement('_format', 'json|html'),
      TRUE,
    ];
    return $data;
  }

}
