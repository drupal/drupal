<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\ContextualLinkDefault;
use Drupal\Core\Menu\ContextualLinkManager;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Constraint\Count;
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
   * The mocked plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pluginDiscovery;

  /**
   * The cache backend used in the test.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheBackend;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $accessManager;

  /**
   * The mocked account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->moduleHandler->expects($this->any())
      ->method('getModuleDirectories')
      ->willReturn([]);

    $this->pluginDiscovery = $this->createMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $this->cacheBackend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->accessManager = $this->createMock('Drupal\Core\Access\AccessManagerInterface');
    $this->account = $this->createMock('Drupal\Core\Session\AccountInterface');

    $this->contextualLinkManager = new ContextualLinkManager(
      $this->createMock(ControllerResolverInterface::class),
      $this->moduleHandler,
      $this->cacheBackend,
      $language_manager,
      $this->accessManager,
      $this->account,
      new RequestStack()
    );

    $property = new \ReflectionProperty('Drupal\Core\Menu\ContextualLinkManager', 'discovery');
    $property->setValue($this->contextualLinkManager, $this->pluginDiscovery);
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
      ->willReturn($definitions);

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
      ->willReturn((object) ['data' => $definitions]);

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
    $this->expectException(PluginException::class);
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
    $this->expectException(PluginException::class);
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
      ->willReturn($definitions);

    $this->accessManager->expects($this->any())
      ->method('checkNamedRoute')
      ->willReturn(AccessResult::allowed());

    $this->moduleHandler->expects($this->exactly(2))
      ->method('alter')
      ->withConsecutive(
        ['contextual_links_plugins'],
        ['contextual_links', new Count(2), 'group1', ['key' => 'value']],
      );

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
      ->willReturn($definitions);

    $this->accessManager->expects($this->any())
      ->method('checkNamedRoute')
      ->willReturnMap([
        ['test_route', ['key' => 'value'], $this->account, FALSE, TRUE],
        ['test_route2', ['key' => 'value'], $this->account, FALSE, FALSE],
      ]);

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
      ->willReturn($definitions);

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('contextual_links_plugins', $definitions);

    $this->contextualLinkManager->getDefinition('test_plugin');
  }

}
