<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Routing;

use Drupal\Core\Routing\YamlRouteDiscovery;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests \Drupal\Core\Routing\YamlRouteDiscovery.
 */
#[CoversClass(YamlRouteDiscovery::class)]
#[Group('Routing')]
#[RunTestsInSeparateProcesses]
class YamlRouteDiscoveryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['router_test', 'system'];

  /**
   * Tests that string values for methods and schemes are supported.
   *
   * Symfony's route constructor accepts a single method or scheme as a
   * string, so routing.yml files may use strings as well as arrays.
   *
   * @see https://www.drupal.org/node/3608776
   */
  public function testStringMethodsAndSchemes(): void {
    $route = $this->container->get('router.route_provider')
      ->getRouteByName('router_test.string_method_scheme');
    $this->assertSame(['GET'], $route->getMethods());
    $this->assertSame(['https'], $route->getSchemes());
  }

}
