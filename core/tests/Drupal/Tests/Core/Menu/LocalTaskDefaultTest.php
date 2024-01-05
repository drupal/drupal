<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Menu\LocalTaskDefault
 * @group Menu
 */
class LocalTaskDefaultTest extends UnitTestCase {

  /**
   * The tested local task default plugin.
   *
   * @var \Drupal\Core\Menu\LocalTaskDefault
   */
  protected $localTaskBase;

  /**
   * The used plugin configuration.
   *
   * @var array
   */
  protected $config = [];

  /**
   * The used plugin ID.
   *
   * @var string
   */
  protected $pluginId = 'local_task_default';

  /**
   * The used plugin definition.
   *
   * @var array
   */
  protected $pluginDefinition = [
    'id' => 'local_task_default',
  ];

  /**
   * The mocked translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The mocked route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->stringTranslation = $this->createMock('Drupal\Core\StringTranslation\TranslationInterface');
    $this->routeProvider = $this->createMock('Drupal\Core\Routing\RouteProviderInterface');
  }

  /**
   * Setups the local task default.
   */
  protected function setupLocalTaskDefault() {
    $this->localTaskBase = new TestLocalTaskDefault($this->config, $this->pluginId, $this->pluginDefinition);
    $this->localTaskBase
      ->setRouteProvider($this->routeProvider);
  }

  /**
   * @covers ::getRouteParameters
   */
  public function testGetRouteParametersForStaticRoute() {
    $this->pluginDefinition = [
      'route_name' => 'test_route',
    ];

    $this->routeProvider->expects($this->once())
      ->method('getRouteByName')
      ->with('test_route')
      ->willReturn(new Route('/test-route'));

    $this->setupLocalTaskDefault();

    $route_match = new RouteMatch('', new Route('/'));
    $this->assertEquals([], $this->localTaskBase->getRouteParameters($route_match));
  }

  /**
   * @covers ::getRouteParameters
   */
  public function testGetRouteParametersInPluginDefinitions() {
    $this->pluginDefinition = [
      'route_name' => 'test_route',
      'route_parameters' => ['parameter' => 'example'],
    ];

    $this->routeProvider->expects($this->once())
      ->method('getRouteByName')
      ->with('test_route')
      ->willReturn(new Route('/test-route/{parameter}'));

    $this->setupLocalTaskDefault();

    $route_match = new RouteMatch('', new Route('/'));
    $this->assertEquals(['parameter' => 'example'], $this->localTaskBase->getRouteParameters($route_match));
  }

  /**
   * @covers ::getRouteParameters
   */
  public function testGetRouteParametersForDynamicRouteWithNonUpcastedParameters() {
    $this->pluginDefinition = [
      'route_name' => 'test_route',
    ];

    $route = new Route('/test-route/{parameter}');
    $this->routeProvider->expects($this->once())
      ->method('getRouteByName')
      ->with('test_route')
      ->willReturn($route);

    $this->setupLocalTaskDefault();

    $route_match = new RouteMatch('', $route, [], ['parameter' => 'example']);

    $this->assertEquals(['parameter' => 'example'], $this->localTaskBase->getRouteParameters($route_match));
  }

  /**
   * Tests the getRouteParameters method for a route with upcasted parameters.
   *
   * @covers ::getRouteParameters
   */
  public function testGetRouteParametersForDynamicRouteWithUpcastedParameters() {
    $this->pluginDefinition = [
      'route_name' => 'test_route',
    ];

    $route = new Route('/test-route/{parameter}');
    $this->routeProvider->expects($this->once())
      ->method('getRouteByName')
      ->with('test_route')
      ->willReturn($route);

    $this->setupLocalTaskDefault();

    $route_match = new RouteMatch('', $route, ['parameter' => (object) 'example2'], ['parameter' => 'example']);
    $this->assertEquals(['parameter' => 'example'], $this->localTaskBase->getRouteParameters($route_match));
  }

  /**
   * Tests the getRouteParameters method for a route with upcasted parameters.
   *
   * @covers ::getRouteParameters
   */
  public function testGetRouteParametersForDynamicRouteWithUpcastedParametersEmptyRawParameters() {
    $this->pluginDefinition = [
      'route_name' => 'test_route',
    ];

    $route = new Route('/test-route/{parameter}');
    $this->routeProvider->expects($this->once())
      ->method('getRouteByName')
      ->with('test_route')
      ->willReturn($route);

    $this->setupLocalTaskDefault();

    $route_match = new RouteMatch('', $route, ['parameter' => (object) 'example2']);
    $this->assertEquals(['parameter' => (object) 'example2'], $this->localTaskBase->getRouteParameters($route_match));
  }

  /**
   * Defines a data provider for testGetWeight().
   *
   * @return array
   *   A list or test plugin definition and expected weight.
   */
  public function providerTestGetWeight() {
    return [
      // Manually specify a weight, so this is used.
      [['weight' => 314], 'test_id', 314],
      // Ensure that a default tab gets a lower weight.
      [
        [
          'base_route' => 'local_task_default',
          'route_name' => 'local_task_default',
          'id' => 'local_task_default',
        ],
        'local_task_default',
        -10,
      ],
      // If the base route is different from the route of the tab, ignore it.
      [
        [
          'base_route' => 'local_task_example',
          'route_name' => 'local_task_other',
          'id' => 'local_task_default',
        ],
        'local_task_default',
        0,
      ],
      // Ensure that a default tab of a derivative gets the default value.
      [
        [
          'base_route' => 'local_task_example',
          'id' => 'local_task_derivative_default:example_id',
          'route_name' => 'local_task_example',
        ],
        'local_task_derivative_default:example_id',
        -10,
      ],
    ];
  }

  /**
   * @dataProvider providerTestGetWeight
   * @covers ::getWeight
   */
  public function testGetWeight($plugin_definition, $plugin_id, $expected_weight) {
    $this->pluginDefinition = $plugin_definition;
    $this->pluginId = $plugin_id;
    $this->setupLocalTaskDefault();

    $this->assertEquals($expected_weight, $this->localTaskBase->getWeight());
  }

  /**
   * @covers ::getActive
   * @covers ::setActive
   */
  public function testActive() {
    $this->setupLocalTaskDefault();

    $this->assertFalse($this->localTaskBase->getActive());
    $this->localTaskBase->setActive();
    $this->assertTrue($this->localTaskBase->getActive());
  }

  /**
   * @covers ::getTitle
   */
  public function testGetTitle() {
    $this->pluginDefinition['title'] = (new TranslatableMarkup('Example', [], [], $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->willReturn('Example translated');

    $this->setupLocalTaskDefault();
    $this->assertEquals('Example translated', $this->localTaskBase->getTitle());
  }

  /**
   * @covers ::getTitle
   */
  public function testGetTitleWithContext() {
    $title = 'Example';
    $this->pluginDefinition['title'] = (new TranslatableMarkup($title, [], ['context' => 'context'], $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->willReturn('Example translated with context');

    $this->setupLocalTaskDefault();
    $this->assertEquals('Example translated with context', $this->localTaskBase->getTitle());
  }

  /**
   * @covers ::getTitle
   */
  public function testGetTitleWithTitleArguments() {
    $this->pluginDefinition['title'] = (new TranslatableMarkup('Example @test', ['@test' => 'value'], [], $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->willReturn('Example value');

    $this->setupLocalTaskDefault();
    $this->assertEquals('Example value', $this->localTaskBase->getTitle());
  }

  /**
   * @covers ::getOptions
   */
  public function testGetOptions() {
    $this->pluginDefinition['options'] = [
      'attributes' => ['class' => ['example']],
    ];

    $this->setupLocalTaskDefault();

    $route_match = new RouteMatch('', new Route('/'));
    $this->assertEquals($this->pluginDefinition['options'], $this->localTaskBase->getOptions($route_match));

    $this->localTaskBase->setActive(TRUE);

    $this->assertEquals([
      'attributes' => [
        'class' => [
          'example',
          'is-active',
        ],
      ],
    ], $this->localTaskBase->getOptions($route_match));
  }

  /**
   * @covers ::getCacheContexts
   * @covers ::getCacheTags
   * @covers ::getCacheMaxAge
   */
  public function testCacheabilityMetadata() {
    $this->pluginDefinition['cache_contexts'] = ['route'];
    $this->pluginDefinition['cache_tags'] = ['kitten'];
    $this->pluginDefinition['cache_max_age'] = 3600;

    $this->setupLocalTaskDefault();

    $this->assertEquals(['route'], $this->localTaskBase->getCacheContexts());
    $this->assertEquals(['kitten'], $this->localTaskBase->getCacheTags());
    $this->assertEquals(3600, $this->localTaskBase->getCacheMaxAge());
  }

}

class TestLocalTaskDefault extends LocalTaskDefault {

  public function setRouteProvider(RouteProviderInterface $route_provider) {
    $this->routeProvider = $route_provider;
    return $this;
  }

}
