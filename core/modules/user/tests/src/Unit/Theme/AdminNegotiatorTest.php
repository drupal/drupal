<?php

namespace Drupal\Tests\user\Unit\Theme;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Theme\AdminNegotiator;

/**
 * Tests AdminNegotiator class.
 *
 * @group user
 * @coversDefaultClass \Drupal\user\Theme\AdminNegotiator
 */
class AdminNegotiatorTest extends UnitTestCase {

  /**
   * @dataProvider getThemes
   */
  public function testDetermineActiveTheme($admin_theme, $expected) {
    $user = $this->prophesize(AccountInterface::class);
    $config_factory = $this->getConfigFactoryStub(['system.theme' => ['admin' => $admin_theme]]);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $admin_context = $this->prophesize(AdminContext::class);
    $negotiator = new AdminNegotiator($user->reveal(), $config_factory, $entity_type_manager->reveal(), $admin_context->reveal());
    $route_match = $this->prophesize(RouteMatch::class);
    $this->assertSame($expected, $negotiator->determineActiveTheme($route_match->reveal()));
  }

  /**
   * Provides a list of theme names to test.
   */
  public function getThemes() {
    return [
      ['seven', 'seven'],
      [NULL, NULL],
      ['', NULL],
    ];
  }

}
