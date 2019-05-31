<?php

namespace Drupal\Tests\Core\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Language\Language;
use Drupal\Core\Menu\ContextualLinkDefault;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Menu\ContextualLinkManager
 * @group Menu
 */
class ContextualLinkManagerTest extends UnitTestCase {

  /**
   * The tested contextual link manager.
   *
   * @var \Drupal\Core\Menu\ContextualLinkManager
   */
  protected $contextualLinkManager;

  /**
   * The mocked controller resolver.
   *
   * @var \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface|\Drupal\Core\\PHPUnit_Framework_MockObject_MockObject
   */
  protected $controllerResolver;

  /**
   * The mocked plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginDiscovery;

  /**
   * The plugin factory used in the test.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $factory;

  /**
   * The cache backend used in the test.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheBackend;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $accessManager;

  /**
   * The mocked account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  protected function setUp() {
    $this->contextualLinkManager = $this
      ->getMockBuilder('Drupal\Core\Menu\ContextualLinkManager')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $this->controllerResolver = $this->createMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');
    $this->pluginDiscovery = $this->createMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $this->factory = $this->createMock('Drupal\Component\Plugin\Factory\FactoryInterface');
    $this->cacheBackend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->moduleHandler = $this->createMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $this->accessManager = $this->createMock('Drupal\Core\Access\AccessManagerInterface');
    $this->account = $this->createMock('Drupal\Core\Session\AccountInterface');

    $property = new \ReflectionProperty('Drupal\Core\Menu\ContextualLinkManager', 'controllerResolver');
    $property->setAccessible(TRUE);
    $property->setValue($this->contextualLinkManager, $this->controllerResolver);

    $property = new \ReflectionProperty('Drupal\Core\Menu\ContextualLinkManager', 'discovery');
    $property->setAccessible(TRUE);
    $property->setValue($this->contextualLinkManager, $this->pluginDiscovery);

    $property = new \ReflectionProperty('Drupal\Core\Menu\ContextualLinkManager', 'factory');
    $property->setAccessible(TRUE);
    $property->setValue($this->contextualLinkManager, $this->factory);

    $property = new \ReflectionProperty('Drupal\Core\Menu\ContextualLinkManager', 'account');
    $property->setAccessible(TRUE);
    $property->setValue($this->contextualLinkManager, $this->account);

    $property = new \ReflectionProperty('Drupal\Core\Menu\ContextualLinkManager', 'accessManager');
    $property->setAccessible(TRUE);
    $property->setValue($this->contextualLinkManager, $this->accessManager);

    $property = new \ReflectionProperty('Drupal\Core\Menu\ContextualLinkManager', 'moduleHandler');
    $property->setAccessible(TRUE);
    $property->setValue($this->contextualLinkManager, $this->moduleHandler);

    $language_manager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue(new Language(['id' => 'en'])));

    $request_stack = new RequestStack();
    $property = new \ReflectionProperty('Drupal\Core\Menu\ContextualLinkManager', 'requestStack');
    $property->setAccessible(TRUE);
    $property->setValue($this->contextualLinkManager, $request_stack);

    $method = new \ReflectionMethod('Drupal\Core\Menu\ContextualLinkManager', 'alterInfo');
    $method->setAccessible(TRUE);
    $method->invoke($this->contextualLinkManager, 'contextual_links_plugins');

    $this->contextualLinkManager->setCacheBackend($this->cacheBackend, 'contextual_links_plugins:en');
  }

  /**
   * Tests the getContextualLinkPluginsByGroup method.
   *
   * @see \Drupal\Core\Menu\ContextualLinkManager::getContextualLinkPluginsByGroup()
   */
  public function testGetContextualLinkPluginsByGroup() {
    $definitions = [
      'test_plugin1' => [
        'id' => 'test_plugin1',
        'class' => '\Drupal\Core\Menu\ContextualLinkDefault',
        'group' => 'group1',
        'route_name' => 'test_route',
      ],
      'test_plugin2' => [
        'id' => 'test_plugin2',
        'class' => '\Drupal\Core\Menu\ContextualLinkDefault',
        'group' => 'group1',
        'route_name' => 'test_route2',
      ],
      'test_plugin3' => [
        'id' => 'test_plugin3',
        'class' => '\Drupal\Core\Menu\ContextualLinkDefault',
        'group' => 'group2',
        'route_name' => 'test_router3',
      ],
    ];
    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    // Test with a non existing group.
    $result = $this->contextualLinkManager->getContextualLinkPluginsByGroup('group_non_existing');
    $this->assertEmpty($result);

    $result = $this->contextualLinkManager->getContextualLinkPluginsByGroup('group1');
    $this->assertEquals(['test_plugin1', 'test_plugin2'], array_keys($result));

    $result = $this->contextualLinkManager->getContextualLinkPluginsByGroup('group2');
    $this->assertEquals(['test_plugin3'], array_keys($result));
  }

  /**
   * Tests the getContextualLinkPluginsByGroup method with a prefilled cache.
   */
  public function testGetContextualLinkPluginsByGroupWithCache() {
    $definitions = [
      'test_plugin1' => [
        'id' => 'test_plugin1',
        'class' => '\Drupal\Core\Menu\ContextualLinkDefault',
        'group' => 'group1',
        'route_name' => 'test_route',
      ],
      'test_plugin2' => [
        'id' => 'test_plugin2',
        'class' => '\Drupal\Core\Menu\ContextualLinkDefault',
        'group' => 'group1',
        'route_name' => 'test_route2',
      ],
    ];

    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with('contextual_links_plugins:en:group1')
      ->will($this->returnValue((object) ['data' => $definitions]));

    $result = $this->contextualLinkManager->getContextualLinkPluginsByGroup('group1');
    $this->assertEquals($definitions, $result);

    // Ensure that the static cache works, so no second cache get is executed.

    $result = $this->contextualLinkManager->getContextualLinkPluginsByGroup('group1');
    $this->assertEquals($definitions, $result);
  }

  /**
   * Tests processDefinition() by passing a plugin definition without a route.
   *
   * @see \Drupal\Core\Menu\ContextualLinkManager::processDefinition()
   */
  public function testProcessDefinitionWithoutRoute() {
    $definition = [
      'class' => '\Drupal\Core\Menu\ContextualLinkDefault',
      'group' => 'example',
      'id' => 'test_plugin',
    ];
    $this->setExpectedException(PluginException::class);
    $this->contextualLinkManager->processDefinition($definition, 'test_plugin');
  }

  /**
   * Tests processDefinition() by passing a plugin definition without a group.
   *
   * @see \Drupal\Core\Menu\ContextualLinkManager::processDefinition()
   */
  public function testProcessDefinitionWithoutGroup() {
    $definition = [
      'class' => '\Drupal\Core\Menu\ContextualLinkDefault',
      'route_name' => 'example',
      'id' => 'test_plugin',
    ];
    $this->setExpectedException(PluginException::class);
    $this->contextualLinkManager->processDefinition($definition, 'test_plugin');
  }

  /**
   * Tests the getContextualLinksArrayByGroup method.
   *
   * @see \Drupal\Core\Menu\ContextualLinkManager::getContextualLinksArrayByGroup()
   */
  public function testGetContextualLinksArrayByGroup() {
    $definitions = [
      'test_plugin1' => [
        'id' => 'test_plugin1',
        'class' => '\Drupal\Core\Menu\ContextualLinkDefault',
        'title' => 'Plugin 1',
        'weight' => 0,
        'group' => 'group1',
        'route_name' => 'test_route',
        'options' => [],
      ],
      'test_plugin2' => [
        'id' => 'test_plugin2',
        'class' => '\Drupal\Core\Menu\ContextualLinkDefault',
        'title' => 'Plugin 2',
        'weight' => 2,
        'group' => 'group1',
        'route_name' => 'test_route2',
        'options' => ['key' => 'value'],
      ],
      'test_plugin3' => [
        'id' => 'test_plugin3',
        'class' => '\Drupal\Core\Menu\ContextualLinkDefault',
        'title' => 'Plugin 3',
        'weight' => 5,
        'group' => 'group2',
        'route_name' => 'test_router3',
        'options' => [],
      ],
    ];

    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $this->accessManager->expects($this->any())
      ->method('checkNamedRoute')
      ->will($this->returnValue(AccessResult::allowed()));

    // Set up mocking of the plugin factory.
    $map = [];
    foreach ($definitions as $plugin_id => $definition) {
      $map[] = [$plugin_id, [], new ContextualLinkDefault([], $plugin_id, $definition)];
    }
    $this->factory->expects($this->any())
      ->method('createInstance')
      ->will($this->returnValueMap($map));

    $this->moduleHandler->expects($this->at(1))
      ->method('alter')
      ->with($this->equalTo('contextual_links'), new \PHPUnit_Framework_Constraint_Count(2), $this->equalTo('group1'), $this->equalTo(['key' => 'value']));

    $result = $this->contextualLinkManager->getContextualLinksArrayByGroup('group1', ['key' => 'value']);
    $this->assertCount(2, $result);
    foreach (['test_plugin1', 'test_plugin2'] as $plugin_id) {
      $definition = $definitions[$plugin_id];
      $this->assertEquals($definition['weight'], $result[$plugin_id]['weight']);
      $this->assertEquals($definition['title'], $result[$plugin_id]['title']);
      $this->assertEquals($definition['route_name'], $result[$plugin_id]['route_name']);
      $this->assertEquals($definition['options'], $result[$plugin_id]['localized_options']);
    }
  }

  /**
   * Tests the access checking of the getContextualLinksArrayByGroup method.
   *
   * @see \Drupal\Core\Menu\ContextualLinkManager::getContextualLinksArrayByGroup()
   */
  public function testGetContextualLinksArrayByGroupAccessCheck() {
    $definitions = [
      'test_plugin1' => [
        'id' => 'test_plugin1',
        'class' => '\Drupal\Core\Menu\ContextualLinkDefault',
        'title' => 'Plugin 1',
        'weight' => 0,
        'group' => 'group1',
        'route_name' => 'test_route',
        'options' => [],
      ],
      'test_plugin2' => [
        'id' => 'test_plugin2',
        'class' => '\Drupal\Core\Menu\ContextualLinkDefault',
        'title' => 'Plugin 2',
        'weight' => 2,
        'group' => 'group1',
        'route_name' => 'test_route2',
        'options' => ['key' => 'value'],
      ],
    ];

    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $this->accessManager->expects($this->any())
      ->method('checkNamedRoute')
      ->will($this->returnValueMap([
        ['test_route', ['key' => 'value'], $this->account, FALSE, TRUE],
        ['test_route2', ['key' => 'value'], $this->account, FALSE, FALSE],
      ]));

    // Set up mocking of the plugin factory.
    $map = [];
    foreach ($definitions as $plugin_id => $definition) {
      $plugin = $this->createMock('Drupal\Core\Menu\ContextualLinkInterface');
      $plugin->expects($this->any())
        ->method('getRouteName')
        ->will($this->returnValue($definition['route_name']));
      $plugin->expects($this->any())
        ->method('getTitle')
        ->will($this->returnValue($definition['title']));
      $plugin->expects($this->any())
        ->method('getWeight')
        ->will($this->returnValue($definition['weight']));
      $plugin->expects($this->any())
        ->method('getOptions')
        ->will($this->returnValue($definition['options']));
      $map[] = [$plugin_id, [], $plugin];
    }
    $this->factory->expects($this->any())
      ->method('createInstance')
      ->will($this->returnValueMap($map));

    $result = $this->contextualLinkManager->getContextualLinksArrayByGroup('group1', ['key' => 'value']);

    // Ensure that access checking was respected.
    $this->assertTrue(isset($result['test_plugin1']));
    $this->assertFalse(isset($result['test_plugin2']));
  }

  /**
   * Tests the plugins alter hook.
   */
  public function testPluginDefinitionAlter() {
    $definitions['test_plugin'] = [
      'id' => 'test_plugin',
      'class' => ContextualLinkDefault::class,
      'title' => 'Plugin',
      'weight' => 2,
      'group' => 'group1',
      'route_name' => 'test_route',
      'options' => ['key' => 'value'],
    ];

    $this->pluginDiscovery->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('contextual_links_plugins', $definitions);

    $this->contextualLinkManager->getDefinition('test_plugin');
  }

}
