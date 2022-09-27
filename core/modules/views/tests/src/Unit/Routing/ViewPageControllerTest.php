<?php

namespace Drupal\Tests\views\Unit\Routing;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Routing\ViewPageController;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\views\Routing\ViewPageController
 * @group views
 */
class ViewPageControllerTest extends UnitTestCase {

  /**
   * The page controller of views.
   *
   * @var \Drupal\views\Routing\ViewPageController
   */
  public $pageController;

  /**
   * A render array expected for every page controller render array result.
   *
   * @var array
   */
  protected $defaultRenderArray = [
    '#cache_properties' => ['#view_id', '#view_display_show_admin_links', '#view_display_plugin_id'],
    '#view_id' => 'test_page_view',
    '#view_display_plugin_id' => NULL,
    '#view_display_show_admin_links' => NULL,
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->pageController = new ViewPageController();
  }

  /**
   * Tests the page controller.
   */
  public function testPageController() {
    $build = [
      '#type' => 'view',
      '#name' => 'test_page_view',
      '#display_id' => 'default',
      '#embed' => FALSE,
      '#arguments' => [],
      '#cache' => [
        'keys' => ['view', 'test_page_view', 'display', 'default'],
      ],
    ] + $this->defaultRenderArray;

    $request = new Request();
    $request->attributes->set('view_id', 'test_page_view');
    $request->attributes->set('display_id', 'default');
    $options = [
      '_view_display_plugin_class' => '\Drupal\views\Plugin\views\display\Page',
    ];
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/test', ['view_id' => 'test_page_view', 'display_id' => 'default'], [], $options));
    $route_match = RouteMatch::createFromRequest($request);

    $output = $this->pageController->handle($route_match->getParameter('view_id'), $route_match->getParameter('display_id'), $route_match);
    $this->assertIsArray($output);
    $this->assertEquals($build, $output);
  }

  /**
   * Tests the page controller with arguments on a non overridden page view.
   */
  public function testHandleWithArgumentsWithoutOverridden() {
    $request = new Request();
    $request->attributes->set('view_id', 'test_page_view');
    $request->attributes->set('display_id', 'page_1');
    // Add the argument to the request.
    $request->attributes->set('arg_0', 'test-argument');
    $options = [
      '_view_argument_map' => ['arg_0' => 'arg_0'],
      '_view_display_plugin_class' => '\Drupal\views\Plugin\views\display\Page',
    ];
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/test/{arg_0}', ['view_id' => 'test_page_view', 'display_id' => 'default'], [], $options));
    $route_match = RouteMatch::createFromRequest($request);

    $result = $this->pageController->handle($route_match->getParameter('view_id'), $route_match->getParameter('display_id'), $route_match);

    $build = [
      '#type' => 'view',
      '#name' => 'test_page_view',
      '#display_id' => 'page_1',
      '#embed' => FALSE,
      '#arguments' => ['test-argument'],
      '#cache' => [
        'keys' => ['view', 'test_page_view', 'display', 'page_1', 'args', 'test-argument'],
      ],
    ] + $this->defaultRenderArray;

    $this->assertEquals($build, $result);
  }

  /**
   * Tests the page controller with arguments of an overridden page view.
   *
   * Note: This test does not care about upcasting for now.
   */
  public function testHandleWithArgumentsOnOverriddenRoute() {
    $request = new Request();
    $request->attributes->set('view_id', 'test_page_view');
    $request->attributes->set('display_id', 'page_1');
    // Add the argument to the request.
    $request->attributes->set('parameter', 'test-argument');
    $options = [
      '_view_argument_map' => [
        'arg_0' => 'parameter',
      ],
      '_view_display_plugin_class' => '\Drupal\views\Plugin\views\display\Page',
    ];
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/test/{parameter}', ['view_id' => 'test_page_view', 'display_id' => 'default'], [], $options));
    $route_match = RouteMatch::createFromRequest($request);

    $result = $this->pageController->handle($route_match->getParameter('view_id'), $route_match->getParameter('display_id'), $route_match);

    $build = [
      '#type' => 'view',
      '#name' => 'test_page_view',
      '#display_id' => 'page_1',
      '#embed' => FALSE,
      '#arguments' => ['test-argument'],
      '#cache' => [
        'keys' => ['view', 'test_page_view', 'display', 'page_1', 'args', 'test-argument'],
      ],
    ] + $this->defaultRenderArray;

    $this->assertEquals($build, $result);
  }

  /**
   * Tests the page controller with arguments of an overridden page view.
   *
   * This test care about upcasted values and ensures that the raw variables
   * are pulled in.
   */
  public function testHandleWithArgumentsOnOverriddenRouteWithUpcasting() {
    $request = new Request();
    $request->attributes->set('view_id', 'test_page_view');
    $request->attributes->set('display_id', 'page_1');
    // Add the argument to the request.
    $request->attributes->set('test_entity', $this->createMock('Drupal\Core\Entity\EntityInterface'));
    $raw_variables = new InputBag(['test_entity' => 'example_id']);
    $request->attributes->set('_raw_variables', $raw_variables);
    $options = [
      '_view_argument_map' => [
        'arg_0' => 'test_entity',
      ],
      '_view_display_plugin_class' => '\Drupal\views\Plugin\views\display\Page',
    ];
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/test/{test_entity}', ['view_id' => 'test_page_view', 'display_id' => 'default'], [], $options));
    $route_match = RouteMatch::createFromRequest($request);

    $result = $this->pageController->handle($route_match->getParameter('view_id'), $route_match->getParameter('display_id'), $route_match);

    $build = [
      '#type' => 'view',
      '#name' => 'test_page_view',
      '#display_id' => 'page_1',
      '#embed' => FALSE,
      '#arguments' => ['example_id'],
      '#cache' => [
        'keys' => ['view', 'test_page_view', 'display', 'page_1', 'args', 'example_id'],
      ],
    ] + $this->defaultRenderArray;

    $this->assertEquals($build, $result);
  }

}

// @todo https://www.drupal.org/node/2571679 replace
//   views_add_contextual_links().
namespace Drupal\views\Routing;

if (!function_exists('views_add_contextual_links')) {

  function views_add_contextual_links() {
  }

}
