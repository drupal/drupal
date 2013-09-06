<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\DisplayPageTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\views\EventSubscriber\RouteSubscriber;
use Drupal\views\Tests\ViewUnitTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the page display plugin.
 *
 * @see Drupal\views\Plugin\display\Page
 */
class DisplayPageTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_page_display', 'test_page_display_route');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'user', 'menu_link', 'field');

  /**
   * The router dumper to get all routes.
   *
   * @var \Drupal\Core\Routing\MatcherDumper
   */
  protected $routerDumper;

  public static function getInfo() {
    return array(
      'name' => 'Display: Page plugin',
      'description' => 'Tests the page display plugin.',
      'group' => 'Views Plugins',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Setup the needed tables in order to make the drupal router working.
    $this->installSchema('system', 'router');
    $this->installSchema('system', 'url_alias');
    $this->installSchema('system', 'menu_router');
    $this->installSchema('menu_link', 'menu_links');
  }

  /**
   * Checks the behavior of the page for access denied/not found behaviours.
   */
  public function testPageResponses() {
    // @todo Importing a route should fire a container rebuild.
    $this->container->get('router.builder')->rebuild();

    $subrequest = Request::create('/test_page_display_403', 'GET');
    $response = $this->container->get('http_kernel')->handle($subrequest, HttpKernelInterface::SUB_REQUEST);
    $this->assertEqual($response->getStatusCode(), 403);

    $subrequest = Request::create('/test_page_display_404', 'GET');
    $response = $this->container->get('http_kernel')->handle($subrequest, HttpKernelInterface::SUB_REQUEST);
    $this->assertEqual($response->getStatusCode(), 404);

    $subrequest = Request::create('/test_page_display_200', 'GET');
    $response = $this->container->get('http_kernel')->handle($subrequest, HttpKernelInterface::SUB_REQUEST);
    $this->assertEqual($response->getStatusCode(), 200);

    // Test accessing a disabled page for a view.
    $view = views_get_view('test_page_display');
    // Disable the view, rebuild menu, and request the page again.
    $view->storage->disable()->save();
    $subrequest = Request::create('/test_page_display_200', 'GET');
    $response = $this->container->get('http_kernel')->handle($subrequest, HttpKernelInterface::SUB_REQUEST);
    $this->assertEqual($response->getStatusCode(), 404);
  }

  /**
   * Checks that the router items are properly registered
   */
  public function testPageRouterItems() {
    $subscriber = new RouteSubscriber();
    $collection = new RouteCollection();
    $subscriber->dynamicRoutes(new RouteBuildEvent($collection, 'dynamic_routes'));

    // Check the controller defaults.
    foreach ($collection as $id => $route) {
      if (strpos($id, 'test_page_display_route') === 0) {
        $this->assertEqual($route->getDefault('_controller'), 'Drupal\views\Routing\ViewPageController::handle');
        $this->assertEqual($route->getDefault('view_id'), 'test_page_display_route');
        $this->assertEqual($route->getDefault('display_id'), str_replace('test_page_display_route.', '', $id));
      }
    }

    // Check the generated patterns and default values.
    $route = $collection->get('view.test_page_display_route.page_1');
    $this->assertEqual($route->getPath(), '/test_route_without_arguments');

    $route = $collection->get('view.test_page_display_route.page_2');
    $this->assertEqual($route->getPath(), '/test_route_with_argument/{arg_id}');
    $this->assertTrue($route->hasDefault('arg_id'), 'A default value is set for the optional argument id.');

    $route = $collection->get('view.test_page_display_route.page_3');
    $this->assertEqual($route->getPath(), '/test_route_with_argument/{arg_id}/suffix');
    $this->assertFalse($route->hasDefault('arg_id'), 'No default value is set for the required argument id.');

    $route = $collection->get('view.test_page_display_route.page_4');
    $this->assertEqual($route->getPath(), '/test_route_with_argument/{arg_id}/suffix/{arg_id_2}');
    $this->assertFalse($route->hasDefault('arg_id'), 'No default value is set for the required argument id.');
    $this->assertTrue($route->hasDefault('arg_id_2'), 'A default value is set for the optional argument id_2.');

    $route = $collection->get('view.test_page_display_route.page_5');
    $this->assertEqual($route->getPath(), '/test_route_with_argument/{arg_id}/{arg_id_2}');
    $this->assertTrue($route->hasDefault('arg_id'), 'A default value is set for the optional argument id.');
    $this->assertTrue($route->hasDefault('arg_id_2'), 'A default value is set for the optional argument id_2.');

    $route = $collection->get('view.test_page_display_route.page_6');
    $this->assertEqual($route->getPath(), '/test_route_with_argument/{arg_id}/{arg_id_2}');
    $this->assertFalse($route->hasDefault('arg_id'), 'No default value is set for the required argument id.');
    $this->assertFalse($route->hasDefault('arg_id_2'), 'No default value is set for the required argument id_2.');
  }

}
