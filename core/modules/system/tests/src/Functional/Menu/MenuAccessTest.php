<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Menu;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the route access checks on menu links.
 *
 * @group Menu
 */
class MenuAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'filter', 'toolbar', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests menu link for route with access check.
   *
   * @see \Drupal\menu_test\Access\AccessCheck::access()
   */
  public function testMenuBlockLinksAccessCheck(): void {
    $this->container->get('module_installer')->install(['menu_test']);
    $this->drupalPlaceBlock('system_menu_block:account');
    // Test that there's link rendered on the route.
    $this->drupalGet('menu_test_access_check_session');
    $this->assertSession()->linkExists('Test custom route access check');
    // Page is still accessible but there should be no menu link.
    $this->drupalGet('menu_test_access_check_session');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkNotExists('Test custom route access check');
    // Test that page is no more accessible.
    $this->drupalGet('menu_test_access_check_session');
    $this->assertSession()->statusCodeEquals(403);

    // Check for access to a restricted local task from a default local task.
    $this->drupalGet('foo/asdf');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('foo/asdf');
    $this->assertSession()->linkByHrefExists('foo/asdf/b');
    $this->assertSession()->linkByHrefNotExists('foo/asdf/c');

    // Attempt to access a restricted local task.
    $this->drupalGet('foo/asdf/c');
    $this->assertSession()->statusCodeEquals(403);
    // No tab linking to foo/asdf should be found.
    $this->assertSession()->elementNotExists('xpath', $this->assertSession()->buildXPathQuery(
      '//ul[@class="tabs primary"]/li/a[@href=:href]', [
        ':href' => Url::fromRoute('menu_test.router_test1', ['bar' => 'asdf'])->toString(),
      ]
    ));
    $this->assertSession()->linkByHrefNotExists('foo/asdf/b');
    $this->assertSession()->linkByHrefNotExists('foo/asdf/c');
  }

  /**
   * Test routes implementing _access_admin_menu_block_page.
   *
   * @covers \Drupal\system\EventSubscriber\AccessRouteAlterSubscriber::accessAdminMenuBlockPage
   * @covers \Drupal\system\Access\SystemAdminMenuBlockAccessCheck::access
   */
  public function testSystemAdminMenuBlockAccessCheck(): void {
    // Create an admin user.
    $adminUser = $this->drupalCreateUser([], NULL, TRUE);

    // Create a user with 'administer menu' permission.
    $menuAdmin = $this->drupalCreateUser([
      'access administration pages',
      'administer menu',
    ]);

    // Create a user with 'administer filters' permission.
    $filterAdmin = $this->drupalCreateUser([
      'access administration pages',
      'administer filters',
    ]);

    // Create a user with 'access administration pages' permission.
    $webUser = $this->drupalCreateUser([
      'access administration pages',
    ]);

    // An admin user has access to all parent pages.
    $this->drupalLogin($adminUser);
    $this->assertMenuItemRoutesAccess(200, 'admin/structure', 'admin/people');

    // This user has access to administer menus so the structure parent page
    // should be accessible.
    $this->drupalLogin($menuAdmin);
    $this->assertMenuItemRoutesAccess(200, 'admin/structure');
    $this->assertMenuItemRoutesAccess(403, 'admin/people');

    // This user has access to administer filters so the config parent page
    // should be accessible.
    $this->drupalLogin($filterAdmin);
    $this->assertMenuItemRoutesAccess(200, 'admin/config');
    $this->assertMenuItemRoutesAccess(403, 'admin/people');

    // This user doesn't have access to any of the child pages, so the parent
    // pages should not be accessible.
    $this->drupalLogin($webUser);
    $this->assertMenuItemRoutesAccess(403, 'admin/structure', 'admin/people', 'admin/config');

    // The test cases below depend on routes, menu items and permissions added
    // by the menu_test module. It is not enabled before this to ensure that any
    // other configuration it provides that we don't need for these test cases
    // does not affect the assertions above.
    $this->container->get('module_installer')->install(['menu_test']);
    // Test access to routes in the admin menu. The routes are in a menu tree
    // of the hierarchy:
    // menu_test.parent_test
    // -menu_test.child1_test
    // --menu_test.grand_child1_test
    // -menu_test.child2_test
    // --menu_test.grand_child2_test
    // --menu_test.grand_child3_test
    // -menu_test.child3_test_block
    // -menu_test.child4_test_overview
    // -menu_test.child4_test
    // --menu_test.grand_child4_test
    // All routes in this tree except the "grand_child" and "child4_test" routes
    // should have the '_access_admin_menu_block_page' requirement which denies
    // access unless the user has access to a menu item under that route. Route
    // 'menu_test.child3_test_block' and 'menu_test.child4_test_overview' have
    // no menu items underneath it so no user should have access to these routes
    // even though they have the requirement:
    // `_access: 'TRUE'`.
    $tree_routes = [
      'menu_test.parent_test',
      'menu_test.child1_test',
      'menu_test.child2_test',
      'menu_test.child3_test_block',
      'menu_test.child4_test',
      'menu_test.child4_test_overview',
      'menu_test.grand_child1_test',
      'menu_test.grand_child2_test',
      'menu_test.grand_child3_test',
      'menu_test.great_grand_child1_test',
      'menu_test.grand_child4_test',
    ];

    // Create a user with access to only the top level parent.
    $parentUser = $this->drupalCreateUser([
      'access parent test page',
    ]);
    // Create a user with access to the parent and child routes but none of the
    // grand child routes.
    $childOnlyUser = $this->drupalCreateUser([
      'access parent test page',
      'access child1 test page',
      'access child2 test page',
    ]);
    // Create 3 users all with access the parent and child but only 1 grand
    // child route.
    $grandChild1User = $this->drupalCreateUser([
      'access parent test page',
      'access child1 test page',
      'access child2 test page',
      'access grand child1 test page',
    ]);
    $grandChild2User = $this->drupalCreateUser([
      'access parent test page',
      'access child1 test page',
      'access child2 test page',
      'access grand child2 test page',
    ]);
    $grandChild3User = $this->drupalCreateUser([
      'access parent test page',
      'access child1 test page',
      'access child2 test page',
      'access grand child3 test page',
    ]);
    $greatGrandChild1User = $this->drupalCreateUser([
      'access parent test page',
      'access child1 test page',
      'access grand child1 test page',
      'access great grand child1 test page',
    ]);

    // Create a user with access only to a single child, non overview page along
    // with its child (a grand child).
    $grandChild4User = $this->drupalCreateUser([
      'access parent test page',
      'access child4 test page',
      'access grand child4 test page',
    ]);
    // Create a user with access only to a non overview child page.
    $child4NoGrandChild4User = $this->drupalCreateUser([
      'access parent test page',
      'access child4 test page',
    ]);

    $noParentAccessUser = $this->drupalCreateUser([
      'access child1 test page',
      'access child2 test page',
      'access child4 test page',
      'access grand child1 test page',
      'access grand child2 test page',
      'access grand child3 test page',
      'access great grand child1 test page',
      'access grand child4 test page',
    ]);

    // Users that do not have access to any of the 'grand_child' routes where
    // the 'child' routes have the '_access_admin_menu_block_page' requirement
    // will not have access to any of the routes in the tree.
    $this->assertUserRoutesAccess($parentUser, [], $tree_routes);
    $this->assertUserRoutesAccess($childOnlyUser, [], $tree_routes);
    // A user that does not have access to the top level parent but has access
    // to all the other routes will have access to all routes except the parent
    // and 'menu_test.child3_test_block', because it has no items underneath in
    // the menu.
    $this->assertUserRoutesAccess(
      $noParentAccessUser,
      array_diff($tree_routes, [
        'menu_test.parent_test',
        'menu_test.child3_test_block',
      ]),
      $tree_routes
    );

    // Route using overview should have access to the grand child to access the
    // current route.
    $this->assertUserRoutesAccess(
      $grandChild1User,
      [],
      $tree_routes);
    $this->assertUserRoutesAccess(
      $greatGrandChild1User, [
        'menu_test.parent_test',
        'menu_test.child1_test',
        'menu_test.grand_child1_test',
        'menu_test.great_grand_child1_test',
      ],
      $tree_routes);
    // Users who have only access to one grand child route should have access
    // only to that route and its parents.
    $this->assertUserRoutesAccess(
      $grandChild2User,
      ['menu_test.parent_test', 'menu_test.child2_test', 'menu_test.grand_child2_test'],
      $tree_routes);
    $this->assertUserRoutesAccess(
      $grandChild3User,
      // The 'menu_test.grand_child3_test' menu item is nested under
      // 'menu_test.child2_test' to ensure access is correct when there are
      // multiple items nested at the same level.
      ['menu_test.parent_test', 'menu_test.child2_test', 'menu_test.grand_child3_test'],
      $tree_routes);

    // Users who have only access to one grand child route should have access
    // only to that route and its parents.
    $this->assertUserRoutesAccess(
      $grandChild4User, [
        'menu_test.parent_test',
        'menu_test.child4_test',
        'menu_test.child4_test_overview',
        'menu_test.grand_child4_test',
      ],
      $tree_routes);
    // Users who don't have access to a grand child route, but where the child
    // route does not have the '_access_admin_menu_block_page' requirement,
    // should have access to that child route, but not the grand child.
    $this->assertUserRoutesAccess(
      $child4NoGrandChild4User, [
        'menu_test.parent_test',
        'menu_test.child4_test',
        'menu_test.child4_test_overview',
      ],
      $tree_routes);

    // Test a route that has parameter defined in the menu item.
    $this->drupalLogin($parentUser);
    $this->assertMenuItemRoutesAccess(403, Url::fromRoute('menu_test.parent_test_param', ['param' => 'param-in-menu']));
    $this->drupalLogin($childOnlyUser);
    $this->assertMenuItemRoutesAccess(200, Url::fromRoute('menu_test.parent_test_param', ['param' => 'param-in-menu']));

    // Test a route that does not have a parameter defined in the menu item but
    // uses the route default parameter.
    // @todo Change the following test case to use a parent menu item that also
    //   uses the routes default parameter in https://drupal.org/i/3359511.
    $this->drupalLogin($parentUser);
    $this->assertMenuItemRoutesAccess(
      403,
      Url::fromRoute('menu_test.parent_test_param', ['param' => 'child_uses_default']),
      Url::fromRoute('menu_test.child_test_param', ['param' => 'child_uses_default']),

    );
    $this->drupalLogin($childOnlyUser);
    $this->assertMenuItemRoutesAccess(
      200,
      Url::fromRoute('menu_test.parent_test_param', ['param' => 'child_uses_default']),
      Url::fromRoute('menu_test.child_test_param', ['param' => 'child_uses_default']),
    );

    // Test a route that does have a parameter defined in the menu item and that
    // parameter value is equal to the default value specific in the route.
    $this->drupalLogin($parentUser);
    $this->assertMenuItemRoutesAccess(
      403,
      Url::fromRoute('menu_test.parent_test_param_explicit', ['param' => 'my_default']),
      Url::fromRoute('menu_test.child_test_param_explicit', ['param' => 'my_default'])
    );
    $this->drupalLogin($childOnlyUser);
    $this->assertMenuItemRoutesAccess(
      200,
      Url::fromRoute('menu_test.parent_test_param_explicit', ['param' => 'my_default']),
      Url::fromRoute('menu_test.child_test_param_explicit', ['param' => 'my_default'])
    );

    // If we try to access a route that takes a parameter but route is not in the
    // with that parameter we should always be denied access because the sole
    // purpose of \Drupal\system\Controller\SystemController::systemAdminMenuBlockPage
    // is to display items in the menu.
    $this->drupalLogin($parentUser);
    $this->assertMenuItemRoutesAccess(
      403,
      Url::fromRoute('menu_test.parent_test_param', ['param' => 'any-other']),
      // $parentUser does not have the 'access child1 test page' permission.
      Url::fromRoute('menu_test.child_test_param', ['param' => 'any-other'])
    );
    $this->drupalLogin($childOnlyUser);
    $this->assertMenuItemRoutesAccess(403, Url::fromRoute('menu_test.parent_test_param', ['param' => 'any-other']));
    // $childOnlyUser has the 'access child1 test page' permission.
    $this->assertMenuItemRoutesAccess(200, Url::fromRoute('menu_test.child_test_param', ['param' => 'any-other']));
  }

  /**
   * Asserts route requests connected to menu items have the expected access.
   *
   * @param int $expected_status
   *   The expected request status.
   * @param string|\Drupal\Core\Url ...$paths
   *   The paths as passed to \Drupal\Tests\UiHelperTrait::drupalGet().
   */
  private function assertMenuItemRoutesAccess(int $expected_status, string|Url ...$paths): void {
    foreach ($paths as $path) {
      $this->drupalGet($path);
      if (!is_string($path)) {
        $path = $path->toString();
      }
      // We don't use \Behat\Mink\WebAssert::statusCodeEquals() here because it
      // would not allow us to know which path failed.
      $this->assertSame($expected_status, $this->getSession()->getStatusCode(), "Route $path has expected status code");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalGet($path, array $options = [], array $headers = []) {
    $return = parent::drupalGet($path, $options, $headers);
    $this->assertSession()->pageTextNotContains('You do not have any administrative items.');
    return $return;
  }

  /**
   * Asserts which routes a user has access to.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user account for which to check access.
   * @param array $expectedAccessibleRoutes
   *   The routes the user should have access to.
   * @param array $allRoutes
   *   The routes to check.
   */
  private function assertUserRoutesAccess(AccountInterface $user, array $expectedAccessibleRoutes, array $allRoutes): void {
    $this->drupalLogin($user);
    $expectedInaccessibleRoutes = array_diff($allRoutes, $expectedAccessibleRoutes);
    $this->assertEmpty(array_diff($expectedAccessibleRoutes, $allRoutes));
    $actualAccessibleRoutes = [];
    $actualInaccessibleRoutes = [];
    foreach ($allRoutes as $route) {
      $this->drupalGet(Url::fromRoute($route));
      $requestStatus = $this->getSession()->getStatusCode();
      switch ($requestStatus) {
        case 200:
          $actualAccessibleRoutes[] = $route;
          break;

        case 403:
          $actualInaccessibleRoutes[] = $route;
          break;

        default:
          throw new \UnexpectedValueException("Unexpected status code {$requestStatus} for route {$route}");

      }
    }
    $debug = fn($accessibleRoutes, $inaccessibleRoutes) => "\nAccessible routes: " . implode(', ', $accessibleRoutes) . "\nInaccessible routes: " . implode(', ', $inaccessibleRoutes);
    $expected = $debug($expectedAccessibleRoutes, $expectedInaccessibleRoutes);
    $actual = $debug($actualAccessibleRoutes, $actualInaccessibleRoutes);
    $this->assertSession()->assert($expected === $actual, "Routes do not match. \nExpected routes:$expected\nActual routes: $actual");
  }

}
