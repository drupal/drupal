<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit\Theme;

use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Theme\AdminNegotiator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests AdminNegotiator class.
 */
#[CoversClass(AdminNegotiator::class)]
#[Group('user')]
class AdminNegotiatorTest extends UnitTestCase {

  /**
   * Tests determining the active theme.
   */
  #[DataProvider('getThemes')]
  public function testDetermineActiveTheme($admin_theme, $expected): void {
    $user = $this->prophesize(AccountInterface::class);
    $config_factory = $this->getConfigFactoryStub(['system.theme' => ['admin' => $admin_theme]]);
    $admin_context = $this->prophesize(AdminContext::class);
    $negotiator = new AdminNegotiator($user->reveal(), $config_factory, $admin_context->reveal());
    $route_match = $this->prophesize(RouteMatch::class);
    $this->assertSame($expected, $negotiator->determineActiveTheme($route_match->reveal()));
  }

  /**
   * Provides a list of theme names to test.
   */
  public static function getThemes() {
    return [
      ['claro', 'claro'],
      [NULL, NULL],
      ['', NULL],
    ];
  }

}
