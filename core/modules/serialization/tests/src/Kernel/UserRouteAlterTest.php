<?php

declare(strict_types=1);

namespace Drupal\Tests\serialization\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the user routes can be altered.
 *
 * @group serialization
 */
class UserRouteAlterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'serialization',
    'user',
    'user_route_alter_test',
  ];

  /**
   * Tests the altered 'user.login.http' route.
   */
  public function testUserAlteredRoute(): void {
    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = $this->container->get('router.route_provider');

    // Ensure '_format' is set for the 'user.login.http' route.
    $requirements = $route_provider->getRouteByName('user.login.http')->getRequirements();
    $this->assertArrayHasKey('_format', $requirements, 'user.login.http route has "_format" requirement');
    $this->assertEquals('json|xml', $requirements['_format'], 'user.login.http route "_format" requirement is "json|xml"');

    // Ensure the '_access' requirement is set to FALSE for the 'user.pass.http'
    // route.
    $requirements = $route_provider->getRouteByName('user.pass.http')->getRequirements();
    $this->assertArrayHasKey('_access', $requirements, 'user.pass.http route has "_access" requirement');
    $this->assertEquals('FALSE', $requirements['_access'], 'user.pass.http route "_access" requirement is "FALSE"');
    // Ensure '_format' is not set for the 'user.pass.http' route.
    $this->assertArrayNotHasKey('_format', $requirements, 'user.pass.http route does not have "_format" requirement');
  }

}
