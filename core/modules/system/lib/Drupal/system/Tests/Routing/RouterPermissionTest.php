<?php

/**
 * @file
 * Contains Drupal\system\Tests\Routing\RouterPermissionTest.
 */

namespace Drupal\system\Tests\Routing;

use Drupal\Core\Routing\RequestHelper;
use Drupal\simpletest\WebTestBase;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Basic tests for access permissions in routes.
 */
class RouterPermissionTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('router_test');

  public static function getInfo() {
    return array(
      'name' => 'Router Permission tests',
      'description' => 'Function Tests for the routing permission system.',
      'group' => 'Routing',
    );
  }

  /**
   * Tests permission requirements on routes.
   */
  public function testPermissionAccess() {
    $path = 'router_test/test7';
    $this->drupalGet($path);
    $this->assertResponse(403, "Access denied for a route where we don't have a permission");

    $this->drupalGet('router_test/test8');
    $this->assertResponse(403, 'Access denied by default if no access specified');

    $user = $this->drupalCreateUser(array('access test7'));
    $this->drupalLogin($user);
    $this->drupalGet('router_test/test7');
    $this->assertResponse(200);
    $this->assertNoRaw('Access denied');
    $this->assertRaw('test7text', 'The correct string was returned because the route was successful.');

    $this->drupalGet('router_test/test9');
    $this->assertResponse(200);
    $this->assertNoRaw('Access denied');
    $this->assertRaw('test8', 'The correct string was returned because the route was successful.');
  }
}
