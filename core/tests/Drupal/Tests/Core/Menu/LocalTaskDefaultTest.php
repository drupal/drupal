<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;

/**
 * Tests Drupal\Core\Menu\LocalTaskDefault.
 */
#[CoversClass(LocalTaskDefault::class)]
#[Group('Menu')]
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
   * Setups the local task default.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface|null $routeProvider
   *   A route provider.
   */
  protected function setupLocalTaskDefault(?RouteProviderInterface $routeProvider = NULL): void {
    $this->localTaskBase = new TestLocalTaskDefault($this->config, $this->pluginId, $this->pluginDefinition);
    $this->localTaskBase
      ->setRouteProvider($routeProvider ?? $this->createStub(RouteProviderInterface::class));
  }

  /**
   * Tests get route parameters for static route.
   */
  public function testGetRouteParametersForStaticRoute(): void {
    $this->pluginDefinition = [
      'route_name' => 'test_route',
    ];

    $routeProvider = $this->createMock(RouteProviderInterface::class);
    $routeProvider->expects($this->once())
      ->method('getRouteByName')
      ->with('test_route')
      ->willReturn(new Route('/test-route'));

    $this->setupLocalTaskDefault($routeProvider);

    $route_match = new RouteMatch('', new Route('/'));
    $this->assertEquals([], $this->localTaskBase->getRouteParameters($route_match));
  }

  /**
   * Tests get route parameters in plugin definitions.
   */
  public function testGetRouteParametersInPluginDefinitions(): void {
    $this->pluginDefinition = [
      'route_name' => 'test_route',
      'route_parameters' => ['parameter' => 'example'],
    ];

    $routeProvider = $this->createMock(RouteProviderInterface::class);
    $routeProvider->expects($this->once())
      ->method('getRouteByName')
      ->with('test_route')
      ->willReturn(new Route('/test-route/{parameter}'));

    $this->setupLocalTaskDefault($routeProvider);

    $route_match = new RouteMatch('', new Route('/'));
    $this->assertEquals(['parameter' => 'example'], $this->localTaskBase->getRouteParameters($route_match));
  }

  /**
   * Tests get route parameters for dynamic route with non upcasted parameters.
   */
  public function testGetRouteParametersForDynamicRouteWithNonUpcastedParameters(): void {
    $this->pluginDefinition = [
      'route_name' => 'test_route',
    ];

    $route = new Route('/test-route/{parameter}');
    $routeProvider = $this->createMock(RouteProviderInterface::class);
    $routeProvider->expects($this->once())
      ->method('getRouteByName')
      ->with('test_route')
      ->willReturn($route);

    $this->setupLocalTaskDefault($routeProvider);

    $route_match = new RouteMatch('', $route, [], ['parameter' => 'example']);

    $this->assertEquals(['parameter' => 'example'], $this->localTaskBase->getRouteParameters($route_match));
  }

  /**
   * Tests the getRouteParameters method for a route with upcasted parameters.
   */
  public function testGetRouteParametersForDynamicRouteWithUpcastedParameters(): void {
    $this->pluginDefinition = [
      'route_name' => 'test_route',
    ];

    $route = new Route('/test-route/{parameter}');
    $routeProvider = $this->createMock(RouteProviderInterface::class);
    $routeProvider->expects($this->once())
      ->method('getRouteByName')
      ->with('test_route')
      ->willReturn($route);

    $this->setupLocalTaskDefault($routeProvider);

    $route_match = new RouteMatch('', $route, ['parameter' => (object) 'example2'], ['parameter' => 'example']);
    $this->assertEquals(['parameter' => 'example'], $this->localTaskBase->getRouteParameters($route_match));
  }

  /**
   * Tests the getRouteParameters method for a route with upcasted parameters.
   */
  public function testGetRouteParametersForDynamicRouteWithUpcastedParametersEmptyRawParameters(): void {
    $this->pluginDefinition = [
      'route_name' => 'test_route',
    ];

    $route = new Route('/test-route/{parameter}');
    $routeProvider = $this->createMock(RouteProviderInterface::class);
    $routeProvider->expects($this->once())
      ->method('getRouteByName')
      ->with('test_route')
      ->willReturn($route);

    $this->setupLocalTaskDefault($routeProvider);

    $route_match = new RouteMatch('', $route, ['parameter' => (object) 'example2']);
    $this->assertEquals(['parameter' => (object) 'example2'], $this->localTaskBase->getRouteParameters($route_match));
  }

  /**
   * Defines a data provider for testGetWeight().
   *
   * @return array
   *   A list or test plugin definition and expected weight.
   */
  public static function providerTestGetWeight(): array {
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
   * Tests get weight.
   */
  #[DataProvider('providerTestGetWeight')]
  public function testGetWeight(array $plugin_definition, string $plugin_id, int $expected_weight): void {
    $this->pluginDefinition = $plugin_definition;
    $this->pluginId = $plugin_id;
    $this->setupLocalTaskDefault();

    $this->assertEquals($expected_weight, $this->localTaskBase->getWeight());
  }

  /**
   * Tests active.
   *
   * @see ::getActive
   * @see ::setActive
   */
  public function testActive(): void {
    $this->setupLocalTaskDefault();

    $this->assertFalse($this->localTaskBase->getActive());
    $this->localTaskBase->setActive();
    $this->assertTrue($this->localTaskBase->getActive());
  }

  /**
   * Tests get title.
   */
  public function testGetTitle(): void {
    $stringTranslation = $this->createMock(TranslationInterface::class);
    $this->pluginDefinition['title'] = (new TranslatableMarkup('Example', [], [], $stringTranslation));
    $stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->willReturn('Example translated');

    $this->setupLocalTaskDefault();
    $this->assertEquals('Example translated', $this->localTaskBase->getTitle());
  }

  /**
   * Tests get title with context.
   */
  public function testGetTitleWithContext(): void {
    $title = 'Example';
    $stringTranslation = $this->createMock(TranslationInterface::class);
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    $this->pluginDefinition['title'] = (new TranslatableMarkup($title, [], ['context' => 'context'], $stringTranslation));
    $stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->willReturn('Example translated with context');

    $this->setupLocalTaskDefault();
    $this->assertEquals('Example translated with context', $this->localTaskBase->getTitle());
  }

  /**
   * Tests get title with title arguments.
   */
  public function testGetTitleWithTitleArguments(): void {
    $stringTranslation = $this->createMock(TranslationInterface::class);
    $this->pluginDefinition['title'] = (new TranslatableMarkup('Example @test', ['@test' => 'value'], [], $stringTranslation));
    $stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->willReturn('Example value');

    $this->setupLocalTaskDefault();
    $this->assertEquals('Example value', $this->localTaskBase->getTitle());
  }

  /**
   * Tests get options.
   */
  public function testGetOptions(): void {
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
   * Tests cacheability metadata.
   *
   * @see ::getCacheContexts
   * @see ::getCacheTags
   * @see ::getCacheMaxAge
   */
  public function testCacheabilityMetadata(): void {
    $this->pluginDefinition['cache_contexts'] = ['route'];
    $this->pluginDefinition['cache_tags'] = ['kitten'];
    $this->pluginDefinition['cache_max_age'] = 3600;

    $this->setupLocalTaskDefault();

    $this->assertEquals(['route'], $this->localTaskBase->getCacheContexts());
    $this->assertEquals(['kitten'], $this->localTaskBase->getCacheTags());
    $this->assertEquals(3600, $this->localTaskBase->getCacheMaxAge());
  }

}

/**
 * Stub class for testing LocalTaskDefault.
 */
class TestLocalTaskDefault extends LocalTaskDefault {

  public function setRouteProvider(RouteProviderInterface $route_provider): static {
    $this->routeProvider = $route_provider;
    return $this;
  }

}
