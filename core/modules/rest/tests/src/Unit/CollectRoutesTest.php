<?php

namespace Drupal\Tests\rest\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\rest\Plugin\views\display\RestExport;
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
  protected function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();

    $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
      ->disableOriginalConstructor()
      ->getMock();

    $this->view = $this->getMock('\Drupal\views\Entity\View', array('initHandlers'), array(
      array('id' => 'test_view'),
      'view',
    ));

    $view_executable = $this->getMock('\Drupal\views\ViewExecutable', array('initHandlers', 'getTitle'), array(), '', FALSE);
    $view_executable->expects($this->any())
      ->method('getTitle')
      ->willReturn('View title');

    $view_executable->storage = $this->view;
    $view_executable->argument = array();

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

    $state = $this->getMock('\Drupal\Core\State\StateInterface');
    $container->set('state', $state);

    $style_manager = $this->getMockBuilder('\Drupal\views\Plugin\ViewsPluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $container->set('plugin.manager.views.style', $style_manager);
    $container->set('renderer', $this->getMock('Drupal\Core\Render\RendererInterface'));

    \Drupal::setContainer($container);

    $this->restExport = RestExport::create($container, array(), "test_routes", array());
    $this->restExport->view = $view_executable;

    // Initialize a display.
    $this->restExport->display = array('id' => 'page_1');

    // Set the style option.
    $this->restExport->setOption('style', array('type' => 'serializer'));

    $display_manager->expects($this->once())
      ->method('getDefinition')
      ->will($this->returnValue(array('id' => 'test', 'provider' => 'test')));

    $none = $this->getMockBuilder('\Drupal\views\Plugin\views\access\None')
      ->disableOriginalConstructor()
      ->getMock();

    $access_manager->expects($this->once())
      ->method('createInstance')
      ->will($this->returnValue($none));

    $style_plugin = $this->getMock('\Drupal\rest\Plugin\views\style\Serializer', array('getFormats', 'init'), array(), '', FALSE);

    $style_plugin->expects($this->once())
      ->method('getFormats')
      ->will($this->returnValue(array('json')));

    $style_plugin->expects($this->once())
      ->method('init')
      ->with($view_executable)
      ->will($this->returnValue(TRUE));

    $style_manager->expects($this->once())
      ->method('createInstance')
      ->will($this->returnValue($style_plugin));

    $this->routes = new RouteCollection();
    $this->routes->add('test_1', new Route('/test/1'));
    $this->routes->add('view.test_view.page_1', new Route('/test/2'));

    $this->view->addDisplay('page', NULL, 'page_1');
  }

  /**
   * Tests if adding a requirement to a route only modify one route.
   */
  public function testRoutesRequirements() {
    $this->restExport->collectRoutes($this->routes);

    $requirements_1 = $this->routes->get('test_1')->getRequirements();
    $requirements_2 = $this->routes->get('view.test_view.page_1')->getRequirements();

    $this->assertEquals(count($requirements_1), 0, 'First route has no requirement.');
    $this->assertEquals(count($requirements_2), 2, 'Views route with rest export had the format and method requirements added.');
  }
}
