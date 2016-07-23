<?php

namespace Drupal\Tests\views\Unit;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\Plugin\views\cache\None as NoneCache;
use Drupal\views\Plugin\views\pager\None as NonePager;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\views\ViewExecutable
 * @group views
 */
class ViewExecutableTest extends UnitTestCase {

  /**
   * Indicates that a display is enabled.
   */
  const DISPLAY_ENABLED = TRUE;

  /**
   * Indicates that a display is disabled.
   */
  const DISPLAY_DISABLED = FALSE;

  /**
   * A mocked display collection.
   *
   * @var \Drupal\views\DisplayPluginCollection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $displayHandlers;

  /**
   * The mocked view executable.
   *
   * @var \Drupal\views\ViewExecutableFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $viewExecutableFactory;

  /**
   * The tested view executable.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $executable;

  /**
   * The mocked view entity.
   *
   * @var \Drupal\views\ViewEntityInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $view;

  /**
   * The mocked user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $user;

  /**
   * The mocked views data.
   *
   * @var \Drupal\views\ViewsData|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $viewsData;

  /**
   * The mocked display handler.
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $displayHandler;

  /**
   * The mocked route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProvider;

  /**
   * The mocked none cache plugin.
   *
   * @var \Drupal\views\Plugin\views\cache\None|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $noneCache;

  /**
   * The mocked cache plugin that returns a successful result.
   *
   * @var \Drupal\views\Plugin\views\cache\None|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $successCache;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->view = $this->getMock('Drupal\views\ViewEntityInterface');
    $this->user = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->viewsData = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $this->displayHandler = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayRouterInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $this->displayHandlers = $this->getMockBuilder('Drupal\views\DisplayPluginCollection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->executable = new ViewExecutable($this->view, $this->user, $this->viewsData, $this->routeProvider);
    $this->executable->display_handler = $this->displayHandler;
    $this->executable->displayHandlers = $this->displayHandlers;

    $this->viewExecutableFactory = $this->getMockBuilder('Drupal\views\ViewExecutableFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $module_handler = $this->getMockBuilder(ModuleHandlerInterface::class)
      ->getMock();

    $this->noneCache = $this->getMockBuilder(NoneCache::class)
      ->disableOriginalConstructor()
      ->getMock();

    $success_cache = $this->prophesize(CachePluginBase::class);
    $success_cache->cacheGet('results')->willReturn(TRUE);
    $this->successCache = $success_cache->reveal();

    $cache_manager = $this->prophesize(PluginManagerInterface::class);
    $cache_manager->createInstance('none')->willReturn($this->noneCache);

    $translation = $this->getStringTranslationStub();
    $container = new ContainerBuilder();
    $container->set('string_translation', $translation);
    $container->set('views.executable', $this->viewExecutableFactory);
    $container->set('module_handler', $module_handler);
    $container->set('plugin.manager.views.cache', $cache_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::getUrl
   */
  public function testGetUrlWithOverriddenUrl() {
    $url = Url::fromRoute('example');
    $this->executable->override_url = $url;

    $this->assertSame($url, $this->executable->getUrl());
  }

  /**
   * @covers ::getUrl
   */
  public function testGetUrlWithPathNoPlaceholders() {
    $this->displayHandler->expects($this->any())
      ->method('getRoutedDisplay')
      ->willReturn($this->displayHandler);
    $this->displayHandlers->expects($this->any())
      ->method('get')
      ->willReturn($this->displayHandler);
    $this->displayHandler->expects($this->any())
      ->method('getUrlInfo')
      ->willReturn(Url::fromRoute('views.test.page_1'));
    $this->displayHandler->expects($this->any())
      ->method('getPath')
      ->willReturn('test-path');

    $this->assertEquals(Url::fromRoute('views.test.page_1'), $this->executable->getUrl());
  }

  /**
   * @expectedException \InvalidArgumentException
   *
   * @covers ::getUrl
   */
  public function testGetUrlWithoutRouterDisplay() {
    $this->displayHandler = $this->getMock('Drupal\views\Plugin\views\display\DisplayPluginInterface');
    $this->displayHandlers->expects($this->any())
      ->method('get')
      ->willReturn($this->displayHandler);
    $this->executable->display_handler = $this->displayHandler;

    $this->executable->getUrl();
  }

  /**
   * @covers ::getUrl
   */
  public function testGetUrlWithPlaceholdersAndArgs() {
    $this->displayHandler->expects($this->any())
      ->method('getRoutedDisplay')
      ->willReturn($this->displayHandler);
    $this->displayHandlers->expects($this->any())
      ->method('get')
      ->willReturn($this->displayHandler);
    $this->displayHandler->expects($this->any())
      ->method('getUrlInfo')
      ->willReturn(Url::fromRoute('views.test.page_1'));
    $this->displayHandler->expects($this->any())
      ->method('getPath')
      ->willReturn('test-path/%');

    $route = new Route('/test-path/{arg_0}');
    $this->routeProvider->expects($this->any())
      ->method('getRouteByName')
      ->with('views.test.page_1')
      ->willReturn($route);

    $this->assertEquals(Url::fromRoute('views.test.page_1', ['arg_0' => 'test']), $this->executable->getUrl(['test']));
  }

  /**
   * @covers ::getUrl
   */
  public function testGetUrlWithPlaceholdersAndWithoutArgs() {
    $this->displayHandler->expects($this->any())
      ->method('getRoutedDisplay')
      ->willReturn($this->displayHandler);
    $this->displayHandlers->expects($this->any())
      ->method('get')
      ->willReturn($this->displayHandler);
    $this->displayHandler->expects($this->any())
      ->method('getUrlInfo')
      ->willReturn(Url::fromRoute('views.test.page_1'));
    $this->displayHandler->expects($this->any())
      ->method('getPath')
      ->willReturn('test-path/%/%');

    $route = new Route('/test-path/{arg_0}/{arg_1}');
    $this->routeProvider->expects($this->any())
      ->method('getRouteByName')
      ->with('views.test.page_1')
      ->willReturn($route);

    $this->assertEquals(Url::fromRoute('views.test.page_1', ['arg_0' => '*', 'arg_1' => '*']), $this->executable->getUrl());
  }

  /**
   * @covers ::getUrl
   */
  public function testGetUrlWithPlaceholdersAndWithoutArgsAndExceptionValue() {
    $this->displayHandler->expects($this->any())
      ->method('getRoutedDisplay')
      ->willReturn($this->displayHandler);
    $this->displayHandlers->expects($this->any())
      ->method('get')
      ->willReturn($this->displayHandler);
    $this->displayHandler->expects($this->any())
      ->method('getUrlInfo')
      ->willReturn(Url::fromRoute('views.test.page_1'));
    $this->displayHandler->expects($this->any())
      ->method('getPath')
      ->willReturn('test-path/%/%');

    $route = new Route('/test-path/{arg_0}/{arg_1}');
    $this->routeProvider->expects($this->any())
      ->method('getRouteByName')
      ->with('views.test.page_1')
      ->willReturn($route);

    $argument_handler = $this->getMockBuilder('Drupal\views\Plugin\views\argument\ArgumentPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $argument_handler->options['exception']['value'] = 'exception_0';
    $this->executable->argument['key_1'] = $argument_handler;
    $argument_handler = $this->getMockBuilder('Drupal\views\Plugin\views\argument\ArgumentPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $argument_handler->options['exception']['value'] = 'exception_1';
    $this->executable->argument['key_2'] = $argument_handler;

    $this->assertEquals(Url::fromRoute('views.test.page_1', ['arg_0' => 'exception_0', 'arg_1' => 'exception_1']), $this->executable->getUrl());
  }

  /**
   * @covers ::buildThemeFunctions
   */
  public function testBuildThemeFunctions() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    unset($view->display_handler);
    $expected = [
      'test_hook__test_view',
      'test_hook'
    ];
    $this->assertEquals($expected, $view->buildThemeFunctions('test_hook'));

    $view->display_handler = $display;
    $expected = [
      'test_hook__test_view__default',
      'test_hook__default',
      'test_hook__one',
      'test_hook__two',
      'test_hook__and_three',
      'test_hook__test_view',
      'test_hook'
    ];
    $this->assertEquals($expected, $view->buildThemeFunctions('test_hook'));

    //Change the name of the display plugin and make sure that is in the array.
    $view->display_handler->display['display_plugin'] = 'default2';

    $expected = [
      'test_hook__test_view__default',
      'test_hook__default',
      'test_hook__one',
      'test_hook__two',
      'test_hook__and_three',
      'test_hook__test_view__default2',
      'test_hook__default2',
      'test_hook__test_view',
      'test_hook'
    ];
    $this->assertEquals($expected, $view->buildThemeFunctions('test_hook'));
  }

  /**
   * @covers ::generateHandlerId
   */
  public function testGenerateHandlerId() {
    // Test the generateHandlerId() method.
    $test_ids = ['test' => 'test', 'test_1' => 'test_1'];
    $this->assertEquals(ViewExecutable::generateHandlerId('new', $test_ids), 'new');
    $this->assertEquals(ViewExecutable::generateHandlerId('test', $test_ids), 'test_2');
  }

  /**
   * @covers ::addHandler
   */
  public function testAddHandler() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    $views_data = [];
    $views_data['test_field'] = [
      'field' => ['id' => 'standard'],
      'filter' => ['id' => 'standard'],
      'argument' => ['id' => 'standard'],
      'sort' => ['id' => 'standard'],
    ];

    $this->viewsData->expects($this->atLeastOnce())
      ->method('get')
      ->with('test_entity')
      ->willReturn($views_data);

    foreach (['field', 'filter', 'argument', 'sort'] as $handler_type) {
      $display->expects($this->atLeastOnce())
        ->method('setOption')
        ->with($this->callback(function($argument) {
          return $argument;
        }), ['test_field' => [
          'id' => 'test_field',
          'table' => 'test_entity',
          'field' => 'test_field',
          'plugin_id' => 'standard',
        ]]);
    }

    foreach (['field', 'filter', 'argument', 'sort'] as $handler_type) {
      $view->addHandler('default', $handler_type, 'test_entity', 'test_field');
    }
  }

  /**
   * @covers ::addHandler
   */
  public function testAddHandlerWithEntityField() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    $views_data = [];
    $views_data['table']['entity type'] = 'test_entity_type';
    $views_data['test_field'] = [
      'entity field' => 'test_field',
      'field' => ['id' => 'standard'],
      'filter' => ['id' => 'standard'],
      'argument' => ['id' => 'standard'],
      'sort' => ['id' => 'standard'],
    ];

    $this->viewsData->expects($this->atLeastOnce())
      ->method('get')
      ->with('test_entity')
      ->willReturn($views_data);

    foreach (['field', 'filter', 'argument', 'sort'] as $handler_type) {
      $display->expects($this->atLeastOnce())
        ->method('setOption')
        ->with($this->callback(function($argument) {
          return $argument;
        }), ['test_field' => [
          'id' => 'test_field',
          'table' => 'test_entity',
          'field' => 'test_field',
          'entity_type' => 'test_entity_type',
          'entity_field' => 'test_field',
          'plugin_id' => 'standard',
        ]]);
    }

    foreach (['field', 'filter', 'argument', 'sort'] as $handler_type) {
      $view->addHandler('default', $handler_type, 'test_entity', 'test_field');
    }
  }

  /**
   * @covers ::attachDisplays
   */
  public function testAttachDisplays() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    $display->expects($this->atLeastOnce())
      ->method('acceptAttachments')
      ->willReturn(TRUE);
    $display->expects($this->atLeastOnce())
      ->method('getAttachedDisplays')
      ->willReturn(['page_1']);

    $cloned_view = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $this->viewExecutableFactory->expects($this->atLeastOnce())
      ->method('get')
      ->willReturn($cloned_view);

    $page_display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $page_display->expects($this->atLeastOnce())
      ->method('isEnabled')
      ->willReturn(TRUE);

    $display_collection = $this->getMockBuilder('Drupal\views\DisplayPluginCollection')
      ->disableOriginalConstructor()
      ->getMock();

    $display_collection->expects($this->atLeastOnce())
      ->method('get')
      ->with('page_1')
      ->willReturn($page_display);
    $view->displayHandlers = $display_collection;

    // Setup the expectations.
    $page_display->expects($this->once())
      ->method('attachTo')
      ->with($cloned_view, 'default', $view->element);

    $view->attachDisplays();
  }

  /**
   * Setups a view executable and default display.
   *
   * @return array
   *   Returns the view executable and default display.
   */
  protected function setupBaseViewAndDisplay() {
    $config = array(
      'id' => 'test_view',
      'tag' => 'OnE, TWO, and three',
      'display' => [
        'default' => [
          'id' => 'default',
          'display_plugin' => 'default',
          'display_title' => 'Default',
        ],
      ],
    );

    $storage = new View($config, 'view');
    $view = new ViewExecutable($storage, $this->user, $this->viewsData, $this->routeProvider);
    $display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $display->expects($this->any())
      ->method('getPlugin')
      ->with($this->equalTo('cache'))
      ->willReturn($this->successCache);

    $display->display = $config['display']['default'];

    $view->current_display = 'default';
    $view->display_handler = $display;
    $view->displayHandlers = $this->displayHandlers;
    $view->displayHandlers->expects($this->any())
      ->method('get')
      ->with('default')
      ->willReturn($display);
    $view->displayHandlers->expects($this->any())
      ->method('has')
      ->with('default')
      ->willReturn(TRUE);

    foreach (array_keys($view->getHandlerTypes()) as $type) {
      $view->$type = [];
    }

    return array($view, $display);
  }

  /**
   * @covers ::setItemsPerPage
   * @covers ::getItemsPerPage
   */
  public function testSetItemsPerPageBeforePreRender() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    $view->setItemsPerPage(12);
    $this->assertEquals(12, $view->getItemsPerPage());
    $this->assertContains('items_per_page:12', $view->element['#cache']['keys']);
  }

  /**
   * @covers ::setItemsPerPage
   * @covers ::getItemsPerPage
   */
  public function testSetItemsPerPageDuringPreRender() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    $elements = &$view->element;
    $elements['#cache'] += ['keys' => []];
    $elements['#pre_rendered'] = TRUE;

    $view->setItemsPerPage(12);
    $this->assertEquals(12, $view->getItemsPerPage());
    $this->assertNotContains('items_per_page:12', $view->element['#cache']['keys']);
  }

  /**
   * @covers ::setOffset
   * @covers ::getOffset
   */
  public function testSetOffsetBeforePreRender() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    $view->setOffset(12);
    $this->assertEquals(12, $view->getOffset());
    $this->assertContains('offset:12', $view->element['#cache']['keys']);
  }

  /**
   * @covers ::setOffset
   * @covers ::getOffset
   */
  public function testSetOffsetDuringPreRender() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    $elements = &$view->element;
    $elements['#cache'] += ['keys' => []];
    $elements['#pre_rendered'] = TRUE;

    $view->setOffset(12);
    $this->assertEquals(12, $view->getOffset());
    $this->assertNotContains('offset:12', $view->element['#cache']['keys']);
  }

  /**
   * @covers ::setCurrentPage
   * @covers ::getCurrentPage
   */
  public function testSetCurrentPageBeforePreRender() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    $view->setCurrentPage(12);
    $this->assertEquals(12, $view->getCurrentPage());
    $this->assertContains('page:12', $view->element['#cache']['keys']);
  }

  /**
   * @covers ::setCurrentPage
   * @covers ::getCurrentPage
   */
  public function testSetCurrentPageDuringPreRender() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    $elements = &$view->element;
    $elements['#cache'] += ['keys' => []];
    $elements['#pre_rendered'] = TRUE;

    $view->setCurrentPage(12);
    $this->assertEquals(12, $view->getCurrentPage());
    $this->assertNotContains('page:12', $view->element['#cache']['keys']);
  }

  /**
   * @covers ::execute
   */
  public function testCacheIsIgnoredDuringPreview() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    // Pager needs to be set to avoid false test failures.
    $view->pager = $this->getMockBuilder(NonePager::class)
      ->disableOriginalConstructor()
      ->getMock();

    $query = $this->getMockBuilder(QueryPluginBase::class)
      ->disableOriginalConstructor()
      ->getMock();

    $view->query = $query;
    $view->built = TRUE;
    $view->live_preview = TRUE;

    $this->noneCache->expects($this->once())->method('cacheGet');
    $query->expects($this->once())->method('execute');

    $view->execute();
  }

  /**
   * Tests the return values for the execute() method.
   *
   * @param bool $display_enabled
   *   Whether the display to test should be enabled.
   * @param bool $expected_result
   *   The expected result when calling execute().
   *
   * @covers ::execute
   * @dataProvider providerExecuteReturn
   */
  public function testExecuteReturn($display_enabled, $expected_result) {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    $display->expects($this->any())
      ->method('isEnabled')
      ->willReturn($display_enabled);

    // Pager needs to be set to avoid false test failures.
    $view->pager = $this->getMockBuilder(NonePager::class)
      ->disableOriginalConstructor()
      ->getMock();

    $query = $this->getMockBuilder(QueryPluginBase::class)
      ->disableOriginalConstructor()
      ->getMock();

    $view->query = $query;
    $view->built = TRUE;

    $this->assertEquals($expected_result, $view->execute());
  }

  /**
   * Provider for testExecuteReturn().
   *
   * @return array[]
   *   An array of arrays containing the display state and expected value.
   */
  public function providerExecuteReturn() {
    return [
      'enabled' => [static::DISPLAY_ENABLED, TRUE],
      'disabled' => [static::DISPLAY_DISABLED, FALSE],
    ];
  }

}
