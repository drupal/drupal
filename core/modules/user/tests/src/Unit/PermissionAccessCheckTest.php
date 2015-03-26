<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\PermissionAccessCheckTest.
 */

namespace Drupal\Tests\user\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Access\PermissionAccessCheck;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\user\Access\PermissionAccessCheck
 * @group Routing
 * @group AccessF
 */
class PermissionAccessCheckTest extends UnitTestCase {

  /**
   * The tested access checker.
   *
   * @var \Drupal\user\Access\PermissionAccessCheck
   */
  public $accessCheck;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->accessCheck = new PermissionAccessCheck();
  }

  /**
   * Provides data for the testAccess method.
   *
   * @return array
   */
  public function providerTestAccess() {
    $allowed = AccessResult::allowedIf(TRUE)->addCacheContexts(['user.permissions']);
    $neutral = AccessResult::allowedIf(FALSE)->addCacheContexts(['user.permissions']);
    return [
      [[], AccessResult::allowedIf(FALSE)],
      [['_permission' => 'allowed'], $allowed],
      [['_permission' => 'denied'], $neutral],
      [['_permission' => 'allowed+denied'], $allowed],
      [['_permission' => 'allowed+denied+other'], $allowed],
      [['_permission' => 'allowed,denied'], $neutral],
    ];
  }

  /**
   * Tests the access check method.
   *
   * @dataProvider providerTestAccess
   * @covers ::access
   */
  public function testAccess($requirements, $access) {
    $user = $this->getMock('Drupal\Core\Session\AccountInterface');
    $user->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValueMap([
          ['allowed', TRUE],
          ['denied', FALSE],
          ['other', FALSE]
        ]
      ));
    $route = new Route('', [], $requirements);

    $this->assertEquals($access, $this->accessCheck->access($route, $user));
  }

}
