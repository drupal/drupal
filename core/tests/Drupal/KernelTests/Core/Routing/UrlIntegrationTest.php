<?php

namespace Drupal\KernelTests\Core\Routing;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the URL object integration into the access system.
 *
 * @group Url
 */
class UrlIntegrationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user', 'router_test', 'system'];

  /**
   * Ensures that the access() method on \Drupal\Core\Url objects works.
   */
  public function testAccess() {
    /** @var \Drupal\user\RoleInterface $role_with_access */
    $role_with_access = Role::create(['id' => 'role_with_access']);
    $role_with_access->grantPermission('administer users');
    $role_with_access->save();

    /** @var \Drupal\user\RoleInterface $role_without_access */
    $role_without_access = Role::create(['id' => 'role_without_access']);
    $role_without_access->save();

    $user_with_access = User::create(['roles' => ['role_with_access']]);
    $user_without_access = User::create(['roles' => ['role_without_access']]);

    $url_always_access = new Url('router_test.1');
    $this->assertTrue($url_always_access->access($user_with_access));
    $this->assertTrue($url_always_access->access($user_without_access));

    $url_none_access = new Url('router_test.15');
    $this->assertFalse($url_none_access->access($user_with_access));
    $this->assertFalse($url_none_access->access($user_without_access));

    $url_access = new Url('router_test.16');
    $this->assertTrue($url_access->access($user_with_access));
    $this->assertFalse($url_access->access($user_without_access));
  }

}
