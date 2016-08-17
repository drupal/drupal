<?php

namespace Drupal\Tests\outside_in\Unit;

use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\outside_in\OutsideInManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\outside_in\OutsideInManager
 * @group outside_in
 */
class OutsideInManagerTest extends UnitTestCase {

  /**
   * @covers ::isApplicable
   * @dataProvider providerTestIsApplicable
   */
  public function testIsApplicable($is_admin_route, $route_name, $has_permission, $expected) {
    $admin_context = $this->prophesize(AdminContext::class);
    $admin_context->isAdminRoute()->willReturn($is_admin_route);

    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getRouteName()->willReturn($route_name);

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('administer blocks')->willReturn($has_permission);

    $outside_in_manager = new OutsideInManager($admin_context->reveal(), $route_match->reveal(), $account->reveal());

    $this->assertSame($expected, $outside_in_manager->isApplicable());
  }

  /**
   * Data provider for ::testIsApplicable().
   */
  public function providerTestIsApplicable() {
    $data = [];

    // Passing combination.
    $data[] = [FALSE, 'the_route_name', TRUE, TRUE];

    // Failing combinations.
    $data[] = [TRUE, 'the_route_name', TRUE, FALSE];
    $data[] = [TRUE, 'the_route_name', FALSE, FALSE];
    $data[] = [TRUE, 'block.admin_demo', TRUE, FALSE];
    $data[] = [TRUE, 'block.admin_demo', FALSE, FALSE];
    $data[] = [FALSE, 'the_route_name', FALSE, FALSE];
    $data[] = [FALSE, 'block.admin_demo', TRUE, FALSE];
    $data[] = [FALSE, 'block.admin_demo', FALSE, FALSE];

    return $data;
  }

}
