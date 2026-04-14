<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\AttributeRouteDiscovery;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RouteCompiler;
use Drupal\router_test\Controller\TestAttributes;
use Drupal\router_test\Controller\TestClassAttribute;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests \Drupal\Core\Routing\AttributeRouteDiscovery.
 */
#[CoversClass(AttributeRouteDiscovery::class)]
#[Group('Routing')]
class AttributeRouteDiscoveryTest extends UnitTestCase {

  /**
   * The discovered route collection.
   */
  protected RouteCollection $routeCollection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $event = new RouteBuildEvent(new RouteCollection());
    $namespaces = new \ArrayObject([
      'Drupal\router_test' => $this->root . '/core/modules/system/tests/modules/router_test_directory/src',
    ]);
    $discovery = new AttributeRouteDiscovery($namespaces);
    $discovery->onRouteBuild($event);
    $this->routeCollection = $event->getRouteCollection();
  }

  /**
   * @legacy-covers ::onRouteBuild
   */
  public function testOnRouteBuild(): void {
    $this->assertNotEmpty($this->routeCollection);

    $route1 = $this->routeCollection->get('router_test.method_attribute');
    $this->assertNotNull($route1);
    $this->assertSame('/test_method_attribute', $route1->getPath());
    $this->assertSame(TestAttributes::class . '::attributeMethod', $route1->getDefault('_controller'));
    $this->assertSame("TRUE", $route1->getRequirement('_access'));
    $this->assertSame($route1, $this->routeCollection->get('router_test.alias_test'));

    $route2 = $this->routeCollection->get('router_test.class_invoke');
    $this->assertNotNull($route2);
    $this->assertSame('/test_class_attribute', $route2->getPath());
    $this->assertSame(TestClassAttribute::class, $route2->getDefault('_controller'));
    $this->assertSame("TRUE", $route2->getRequirement('_access'));
    $this->assertSame($route2, $this->routeCollection->get(TestClassAttribute::class . '::__invoke'));

    $route3 = $this->routeCollection->get('router_test.method_attribute_other');
    $this->assertNotNull($route3);
    $this->assertSame('/test_method_attribute-other-path', $route3->getPath());
    $this->assertSame(TestAttributes::class . '::attributeMethod', $route3->getDefault('_controller'));
    $this->assertSame("TRUE", $route3->getRequirement('_access'));
  }

  /**
   * Tests all supported route properties.
   *
   * @legacy-covers ::onRouteBuild
   * @legacy-covers ::addRoute
   */
  #[IgnoreDeprecations]
  public function testAllRouteProperties(): void {
    $route = $this->routeCollection->get('router_test.all_properties');
    $this->assertNotNull($route);

    $this->assertSame('/test_all_properties/{parameter}', $route->getPath());
    $this->assertSame(
      TestAttributes::class . '::allProperties',
      $route->getDefault('_controller'),
    );
    $this->assertSame('Test all properties', $route->getDefault('_title'));
    $this->assertSame('1', $route->getDefault('parameter'));
    $this->assertSame('TRUE', $route->getRequirement('_access'));
    $this->assertSame('\d+', $route->getRequirement('parameter'));
    $options = $route->getOptions();
    $this->assertTrue($options['_admin_route']);
    $this->assertTrue($options['utf8']);
    $this->assertSame(RouteCompiler::class, $options['compiler_class']);
    $this->assertSame('{subdomain}.example.com', $route->getHost());
    $this->assertSame(['GET', 'POST'], $route->getMethods());
    $this->assertSame(['https'], $route->getSchemes());
    $this->assertSame($route, $this->routeCollection->get('router_test.all_properties_alias'));

    $this->expectDeprecation('Since drupal/core X.0.0: The "router_test.all_properties_deprecated" route is deprecated.');
    $this->assertSame($route, $this->routeCollection->get('router_test.all_properties_deprecated'));

    // Auto-generated class::method alias.
    $this->assertSame($route, $this->routeCollection->get(TestAttributes::class . '::allProperties'));
  }

  /**
   * Tests that a method inherits class-level globals.
   */
  public function testClassGlobalsInheritance(): void {
    // The route name is the class prefix + method name.
    $route = $this->routeCollection->get('router_test.class_inherits');
    $this->assertNotNull($route);

    // Path is prefixed with the class path.
    $this->assertSame('/test_class_attribute/inherits', $route->getPath());

    // Controller is automatically configured.
    $this->assertSame(TestClassAttribute::class . '::inherits', $route->getDefault('_controller'));

    // Everything else is inherited.
    $this->assertSame('from_class', $route->getDefault('default_a'));
    $this->assertSame('Class title', $route->getDefault('_title'));
    $this->assertSame('TRUE', $route->getRequirement('_access'));
    $this->assertSame('from_class', $route->getOption('option_a'));
    $this->assertSame(RouteCompiler::class, $route->getOption('compiler_class'));
    $this->assertSame(['GET'], $route->getMethods());
    $this->assertSame(['http'], $route->getSchemes());
  }

  /**
   * Tests that method-level properties correctly merge with class globals.
   */
  public function testClassGlobalsMerging(): void {
    // The route name is the class prefix + method name.
    $route = $this->routeCollection->get('router_test.class_overrides');
    $this->assertNotNull($route);

    // Path is prefixed with the class path.
    $this->assertSame('/test_class_attribute/overrides/{id}', $route->getPath());

    // Controller is automatically configured.
    $this->assertSame(TestClassAttribute::class . '::overrides', $route->getDefault('_controller'));

    // Defaults: method key overrides class key, class-only key is inherited.
    $this->assertSame('from_method', $route->getDefault('default_a'));
    $this->assertSame('from_method', $route->getDefault('default_b'));
    $this->assertSame('Class title', $route->getDefault('_title'));

    // Requirements: access is inherited, id key is added.
    $this->assertSame('TRUE', $route->getRequirement('_access'));
    $this->assertSame('\d+', $route->getRequirement('id'));

    // Options: method key overrides class key, method-only key is added.
    $this->assertSame('from_method', $route->getOption('option_a'));
    $this->assertSame('from_method', $route->getOption('option_b'));

    // Host: method overrides class (class had no host set).
    $this->assertSame('method.example.com', $route->getHost());

    // Methods: union of class ['GET'] and method ['POST'].
    $methods = $route->getMethods();
    $this->assertContains('GET', $methods);
    $this->assertContains('POST', $methods);
    $this->assertCount(2, $methods);

    // Schemes: union of class ['http'] and method ['https'].
    $schemes = $route->getSchemes();
    $this->assertContains('http', $schemes);
    $this->assertContains('https', $schemes);
    $this->assertCount(2, $schemes);
  }

}
