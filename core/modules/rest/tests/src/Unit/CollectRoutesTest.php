<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\rest\Plugin\views\display\RestExport;
use Drupal\views\Entity\View;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the REST export view plugin.
 *
 * @group rest
 */
class CollectRoutesTest extends UnitTestCase {

  /**
   * The REST export instance.
   *
   * @var \Drupal\rest\Plugin\views\display\RestExport
   */
  protected $restExport;

  /**
   * The RouteCollection.
   */
  protected $routes;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $view = new View(['id' => 'test_view'], 'view');

    $view_executable = $this->getMockBuilder('\Drupal\views\ViewExecutable')
      ->onlyMethods(['initHandlers', 'getTitle'])
      ->disableOriginalConstructor()
      ->getMock();
    $view_executable->expects($this->any())
      ->method('getTitle')
      ->willReturn('View title');

    $view_executable->storage = $view;
    $view_executable->argument = [];

    $display_manager = $this->getMockBuilder('\Drupal\views\Plugin\ViewsPluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $container->set('plugin.manager.views.display', $display_manager);

    $access_manager = $this->getMockBuilder('\Drupal\views\Plugin\ViewsPluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $container->set('plugin.manager.views.access', $access_manager);

    $route_provider = $this->getMockBuilder('\Drupal\Core\Routing\RouteProviderInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $container->set('router.route_provider', $route_provider);

    $container->setParameter('authentication_providers', ['basic_auth' => 'basic_auth']);

    $state = $this->createMock('\Drupal\Core\State\StateInterface');
    $container->set('state', $state);

    $style_manager = $this->getMockBuilder('\Drupal\views\Plugin\ViewsPluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $container->set('plugin.manager.views.style', $style_manager);
    $container->set('renderer', $this->createMock('Drupal\Core\Render\RendererInterface'));

    $authentication_collector = $this->createMock('\Drupal\Core\Authentication\AuthenticationCollectorInterface');
    $container->set('authentication_collector', $authentication_collector);
    $authentication_collector->expects($this->any())
      ->method('getSortedProviders')
      ->willReturn(['basic_auth' => 'data', 'cookie' => 'data']);

    $container->setParameter('serializer.format_providers', ['json']);

    \Drupal::setContainer($container);

    $this->restExport = RestExport::create($container, [], "test_routes", []);
    $this->restExport->view = $view_executable;

    // Initialize a display.
    $this->restExport->display = ['id' => 'page_1'];

    // Set the style option.
    $this->restExport->setOption('style', ['type' => 'serializer']);

    // Set the auth option.
    $this->restExport->setOption('auth', ['basic_auth']);

    $display_manager->expects($this->once())
      ->method('getDefinition')
      ->willReturn(['id' => 'test', 'provider' => 'test']);

    $none = $this->getMockBuilder('\Drupal\views\Plugin\views\access\None')
      ->disableOriginalConstructor()
      ->getMock();

    $access_manager->expects($this->once())
      ->method('createInstance')
      ->willReturn($none);

    $style_plugin = $this->getMockBuilder('\Drupal\rest\Plugin\views\style\Serializer')
      ->onlyMethods(['getFormats', 'init'])
      ->disableOriginalConstructor()
      ->getMock();

    $style_plugin->expects($this->once())
      ->method('getFormats')
      ->willReturn(['json']);

    $style_plugin->expects($this->once())
      ->method('init')
      ->with($view_executable)
      ->willReturn(TRUE);

    $style_manager->expects($this->once())
      ->method('createInstance')
      ->willReturn($style_plugin);

    $this->routes = new RouteCollection();
    $this->routes->add('test_1', new Route('/test/1'));
    $this->routes->add('view.test_view.page_1', new Route('/test/2'));

    $view->addDisplay('page', NULL, 'page_1');
  }

  /**
   * Tests if adding a requirement to a route only modify one route.
   */
  public function testRoutesRequirements(): void {
    $this->restExport->collectRoutes($this->routes);

    $requirements_1 = $this->routes->get('test_1')->getRequirements();
    $requirements_2 = $this->routes->get('view.test_view.page_1')->getRequirements();

    $this->assertCount(0, $requirements_1, 'First route has no requirement.');
    $this->assertCount(1, $requirements_2, 'Views route with rest export had the format requirement added.');

    // Check auth options.
    $auth = $this->routes->get('view.test_view.page_1')->getOption('_auth');
    $this->assertCount(1, $auth, 'View route with rest export has an auth option added');
    $this->assertEquals('basic_auth', $auth[0], 'View route with rest export has the correct auth option added');
  }

}
