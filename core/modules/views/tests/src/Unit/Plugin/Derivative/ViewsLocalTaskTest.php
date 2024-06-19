<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\Derivative;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\Derivative\ViewsLocalTask;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\views\Plugin\Derivative\ViewsLocalTask
 * @group views
 */
class ViewsLocalTaskTest extends UnitTestCase {

  /**
   * The mocked route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeProvider;

  /**
   * The mocked key value storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $state;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $viewStorage;

  protected $baseDefinition = [
    'class' => '\Drupal\views\Plugin\Menu\LocalTask\ViewsLocalTask',
    'deriver' => '\Drupal\views\Plugin\Derivative\ViewsLocalTask',
  ];

  /**
   * The tested local task derivative class.
   *
   * @var \Drupal\views\Plugin\Derivative\ViewsLocalTask
   */
  protected $localTaskDerivative;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->routeProvider = $this->createMock('Drupal\Core\Routing\RouteProviderInterface');
    $this->state = $this->createMock('Drupal\Core\State\StateInterface');
    $this->viewStorage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');

    $this->localTaskDerivative = new TestViewsLocalTask($this->routeProvider, $this->state, $this->viewStorage);
  }

  /**
   * Tests fetching the derivatives on no view with hook menu.
   *
   * @see \Drupal\views\Plugin\Derivative\ViewsLocalTask::getDerivativeDefinitions()
   */
  public function testGetDerivativeDefinitionsWithoutHookMenuViews(): void {
    $result = [];
    $this->localTaskDerivative->setApplicableMenuViews($result);

    $definitions = $this->localTaskDerivative->getDerivativeDefinitions($this->baseDefinition);
    $this->assertEquals([], $definitions);
  }

  /**
   * Tests fetching the derivatives on a view with without a local task.
   */
  public function testGetDerivativeDefinitionsWithoutLocalTask(): void {
    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $display_plugin = $this->getMockBuilder('Drupal\views\Plugin\views\display\PathPluginBase')
      ->onlyMethods(['getOption'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $display_plugin->expects($this->once())
      ->method('getOption')
      ->with('menu')
      ->willReturn(['type' => 'normal']);
    $executable->display_handler = $display_plugin;

    $storage = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->getMock();
    $storage->expects($this->any())
      ->method('id')
      ->willReturn('example_view');
    $storage->expects($this->any())
      ->method('getExecutable')
      ->willReturn($executable);

    $this->viewStorage->expects($this->any())
      ->method('load')
      ->with('example_view')
      ->willReturn($storage);

    $result = [['example_view', 'page_1']];
    $this->localTaskDerivative->setApplicableMenuViews($result);

    $definitions = $this->localTaskDerivative->getDerivativeDefinitions($this->baseDefinition);
    $this->assertEquals([], $definitions);
  }

  /**
   * Tests fetching the derivatives on a view with a default local task.
   */
  public function testGetDerivativeDefinitionsWithLocalTask(): void {
    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $storage = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->getMock();
    $storage->expects($this->any())
      ->method('id')
      ->willReturn('example_view');
    $storage->expects($this->any())
      ->method('getExecutable')
      ->willReturn($executable);
    $executable->storage = $storage;

    $this->viewStorage->expects($this->any())
      ->method('load')
      ->with('example_view')
      ->willReturn($storage);

    $display_plugin = $this->getMockBuilder('Drupal\views\Plugin\views\display\PathPluginBase')
      ->onlyMethods(['getOption'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $display_plugin->expects($this->once())
      ->method('getOption')
      ->with('menu')
      ->willReturn([
        'type' => 'tab',
        'weight' => 12,
        'title' => 'Example title',
      ]);
    $executable->display_handler = $display_plugin;

    $result = [['example_view', 'page_1']];
    $this->localTaskDerivative->setApplicableMenuViews($result);

    // Mock the view route names state.
    $view_route_names = [];
    $view_route_names['example_view.page_1'] = 'view.example_view.page_1';
    $this->state->expects($this->once())
      ->method('get')
      ->with('views.view_route_names')
      ->willReturn($view_route_names);

    $definitions = $this->localTaskDerivative->getDerivativeDefinitions($this->baseDefinition);
    $this->assertCount(1, $definitions);
    $this->assertEquals('view.example_view.page_1', $definitions['view.example_view.page_1']['route_name']);
    $this->assertEquals(12, $definitions['view.example_view.page_1']['weight']);
    $this->assertEquals('Example title', $definitions['view.example_view.page_1']['title']);
    $this->assertEquals($this->baseDefinition['class'], $definitions['view.example_view.page_1']['class']);
    $this->assertArrayNotHasKey('base_route', $definitions['view.example_view.page_1']);
  }

  /**
   * Tests fetching the derivatives on a view which overrides an existing route.
   */
  public function testGetDerivativeDefinitionsWithOverrideRoute(): void {
    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $storage = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->getMock();
    $storage->expects($this->any())
      ->method('id')
      ->willReturn('example_view');
    $storage->expects($this->any())
      ->method('getExecutable')
      ->willReturn($executable);
    $executable->storage = $storage;

    $this->viewStorage->expects($this->any())
      ->method('load')
      ->with('example_view')
      ->willReturn($storage);

    $display_plugin = $this->getMockBuilder('Drupal\views\Plugin\views\display\PathPluginBase')
      ->onlyMethods(['getOption'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $display_plugin->expects($this->once())
      ->method('getOption')
      ->with('menu')
      ->willReturn(['type' => 'tab', 'weight' => 12]);
    $executable->display_handler = $display_plugin;

    $result = [['example_view', 'page_1']];
    $this->localTaskDerivative->setApplicableMenuViews($result);

    // Mock the view route names state.
    $view_route_names = [];
    // Setup a view which overrides an existing route.
    $view_route_names['example_view.page_1'] = 'example_overridden_route';
    $this->state->expects($this->once())
      ->method('get')
      ->with('views.view_route_names')
      ->willReturn($view_route_names);

    $definitions = $this->localTaskDerivative->getDerivativeDefinitions($this->baseDefinition);
    $this->assertCount(0, $definitions);
  }

  /**
   * Tests fetching the derivatives on a view with a default local task.
   */
  public function testGetDerivativeDefinitionsWithDefaultLocalTask(): void {
    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $storage = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->getMock();
    $storage->expects($this->any())
      ->method('id')
      ->willReturn('example_view');
    $storage->expects($this->any())
      ->method('getExecutable')
      ->willReturn($executable);
    $executable->storage = $storage;

    $this->viewStorage->expects($this->any())
      ->method('load')
      ->with('example_view')
      ->willReturn($storage);

    $display_plugin = $this->getMockBuilder('Drupal\views\Plugin\views\display\PathPluginBase')
      ->onlyMethods(['getOption'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $display_plugin->expects($this->exactly(2))
      ->method('getOption')
      ->with('menu')
      ->willReturn([
        'type' => 'default tab',
        'weight' => 12,
        'title' => 'Example title',
      ]);
    $executable->display_handler = $display_plugin;

    $result = [['example_view', 'page_1']];
    $this->localTaskDerivative->setApplicableMenuViews($result);

    // Mock the view route names state.
    $view_route_names = [];
    $view_route_names['example_view.page_1'] = 'view.example_view.page_1';
    $this->state->expects($this->exactly(2))
      ->method('get')
      ->with('views.view_route_names')
      ->willReturn($view_route_names);

    $definitions = $this->localTaskDerivative->getDerivativeDefinitions($this->baseDefinition);
    $this->assertCount(1, $definitions);
    $plugin = $definitions['view.example_view.page_1'];
    $this->assertEquals('view.example_view.page_1', $plugin['route_name']);
    $this->assertEquals(12, $plugin['weight']);
    $this->assertEquals('Example title', $plugin['title']);
    $this->assertEquals($this->baseDefinition['class'], $plugin['class']);
    $this->assertEquals('view.example_view.page_1', $plugin['base_route']);

    // Setup the prefix of the derivative.
    $definitions['views_view:view.example_view.page_1'] = $definitions['view.example_view.page_1'];
    unset($definitions['view.example_view.page_1']);
    $this->localTaskDerivative->alterLocalTasks($definitions);

    $plugin = $definitions['views_view:view.example_view.page_1'];
    $this->assertCount(1, $definitions);
    $this->assertEquals('view.example_view.page_1', $plugin['route_name']);
    $this->assertEquals(12, $plugin['weight']);
    $this->assertEquals('Example title', $plugin['title']);
    $this->assertEquals($this->baseDefinition['class'], $plugin['class']);
    $this->assertEquals('view.example_view.page_1', $plugin['base_route']);
  }

  /**
   * Tests fetching the derivatives on a view with a local task and a parent.
   *
   * The parent is defined by another module, not views.
   */
  public function testGetDerivativeDefinitionsWithExistingLocalTask(): void {
    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $storage = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->getMock();
    $storage->expects($this->any())
      ->method('id')
      ->willReturn('example_view');
    $storage->expects($this->any())
      ->method('getExecutable')
      ->willReturn($executable);
    $executable->storage = $storage;

    $this->viewStorage->expects($this->any())
      ->method('load')
      ->with('example_view')
      ->willReturn($storage);

    $display_plugin = $this->getMockBuilder('Drupal\views\Plugin\views\display\PathPluginBase')
      ->onlyMethods(['getOption', 'getPath'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $display_plugin->expects($this->exactly(2))
      ->method('getOption')
      ->with('menu')
      ->willReturn([
        'type' => 'tab',
        'weight' => 12,
        'title' => 'Example title',
      ]);
    $display_plugin->expects($this->once())
      ->method('getPath')
      ->willReturn('path/example');
    $executable->display_handler = $display_plugin;

    $result = [['example_view', 'page_1']];
    $this->localTaskDerivative->setApplicableMenuViews($result);

    // Mock the view route names state.
    $view_route_names = [];
    $view_route_names['example_view.page_1'] = 'view.example_view.page_1';
    $this->state->expects($this->exactly(2))
      ->method('get')
      ->with('views.view_route_names')
      ->willReturn($view_route_names);

    // Mock the route provider.
    $route_collection = new RouteCollection();
    $route_collection->add('test_route', new Route('/path'));
    $this->routeProvider->expects($this->any())
      ->method('getRoutesByPattern')
      ->with('/path')
      ->willReturn($route_collection);

    // Setup the existing local task of the test_route.
    $definitions['test_route_tab'] = $other_tab = [
      'route_name' => 'test_route',
      'title' => 'Test route',
      'base_route' => 'test_route',
    ];

    $definitions += $this->localTaskDerivative->getDerivativeDefinitions($this->baseDefinition);

    // Setup the prefix of the derivative.
    $definitions['views_view:view.example_view.page_1'] = $definitions['view.example_view.page_1'];
    unset($definitions['view.example_view.page_1']);
    $this->localTaskDerivative->alterLocalTasks($definitions);

    $plugin = $definitions['views_view:view.example_view.page_1'];
    $this->assertCount(2, $definitions);

    // Ensure the other local task was not changed.
    $this->assertEquals($other_tab, $definitions['test_route_tab']);

    $this->assertEquals('view.example_view.page_1', $plugin['route_name']);
    $this->assertEquals(12, $plugin['weight']);
    $this->assertEquals('Example title', $plugin['title']);
    $this->assertEquals($this->baseDefinition['class'], $plugin['class']);
    $this->assertEquals('test_route', $plugin['base_route']);
  }

}

/**
 * Replaces the applicable views call for easier testability.
 */
class TestViewsLocalTask extends ViewsLocalTask {

  protected $result;

  /**
   * Sets applicable views result.
   */
  public function setApplicableMenuViews($result) {
    $this->result = $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function getApplicableMenuViews() {
    return $this->result;
  }

}
