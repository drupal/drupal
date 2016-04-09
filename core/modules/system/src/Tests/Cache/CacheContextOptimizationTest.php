<?php

namespace Drupal\system\Tests\Cache;

use Drupal\simpletest\KernelTestBase;
use Drupal\simpletest\UserCreationTrait;
use Drupal\user\Entity\Role;

/**
 * Tests the cache context optimization.
 *
 * @group Render
 */
class CacheContextOptimizationTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['user']);
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Ensures that 'user.permissions' cache context is able to define cache tags.
   */
  public function testUserPermissionCacheContextOptimization() {
    $user1 = $this->createUser();
    $this->assertEqual($user1->id(), 1);

    $authenticated_user = $this->createUser(['administer permissions']);
    $role = $authenticated_user->getRoles()[1];

    $test_element = [
      '#cache' => [
        'keys' => ['test'],
        'contexts' => ['user', 'user.permissions'],
      ],
    ];
    \Drupal::service('account_switcher')->switchTo($authenticated_user);
    $element = $test_element;
    $element['#markup'] = 'content for authenticated users';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEqual($output, 'content for authenticated users');

    // Verify that the render caching is working so that other tests can be
    // trusted.
    $element = $test_element;
    $element['#markup'] = 'this should not be visible';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEqual($output, 'content for authenticated users');

    // Even though the cache contexts have been optimized to only include 'user'
    // cache context, the element should have been changed because
    // 'user.permissions' cache context defined a cache tags for permission
    // changes, which should have bubbled up for the element when it was
    // optimized away.
    Role::load($role)
      ->revokePermission('administer permissions')
      ->save();
    $element = $test_element;
    $element['#markup'] = 'this should be visible';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEqual($output, 'this should be visible');
  }

  /**
   * Ensures that 'user.roles' still works when it is optimized away.
   */
  public function testUserRolesCacheContextOptimization() {
    $root_user = $this->createUser();
    $this->assertEqual($root_user->id(), 1);

    $authenticated_user = $this->createUser(['administer permissions']);
    $role = $authenticated_user->getRoles()[1];

    $test_element = [
      '#cache' => [
        'keys' => ['test'],
        'contexts' => ['user', 'user.roles'],
      ],
    ];
    \Drupal::service('account_switcher')->switchTo($authenticated_user);
    $element = $test_element;
    $element['#markup'] = 'content for authenticated users';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEqual($output, 'content for authenticated users');

    // Verify that the render caching is working so that other tests can be
    // trusted.
    $element = $test_element;
    $element['#markup'] = 'this should not be visible';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEqual($output, 'content for authenticated users');

    // Even though the cache contexts have been optimized to only include 'user'
    // cache context, the element should have been changed because 'user.roles'
    // cache context defined a cache tag for user entity changes, which should
    // have bubbled up for the element when it was optimized away.
    $authenticated_user->removeRole($role);
    $authenticated_user->save();
    $element = $test_element;
    $element['#markup'] = 'this should be visible';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEqual($output, 'this should be visible');
  }

}
