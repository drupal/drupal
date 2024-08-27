<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Cache;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;

/**
 * Tests the cache context optimization.
 *
 * @group Render
 */
class CacheContextOptimizationTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['user']);
  }

  /**
   * Ensures that 'user.permissions' cache context is able to define cache tags.
   */
  public function testUserPermissionCacheContextOptimization(): void {
    $user1 = $this->createUser();
    $this->assertEquals(1, $user1->id());

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
    $this->assertEquals('content for authenticated users', $output);

    // Verify that the render caching is working so that other tests can be
    // trusted.
    $element = $test_element;
    $element['#markup'] = 'this should not be visible';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEquals('content for authenticated users', $output);

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
    $this->assertEquals('this should be visible', $output);
  }

  /**
   * Ensures that 'user.roles' still works when it is optimized away.
   */
  public function testUserRolesCacheContextOptimization(): void {
    $root_user = $this->createUser();
    $this->assertEquals(1, $root_user->id());

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
    $this->assertEquals('content for authenticated users', $output);

    // Verify that the render caching is working so that other tests can be
    // trusted.
    $element = $test_element;
    $element['#markup'] = 'this should not be visible';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEquals('content for authenticated users', $output);

    // Even though the cache contexts have been optimized to only include 'user'
    // cache context, the element should have been changed because 'user.roles'
    // cache context defined a cache tag for user entity changes, which should
    // have bubbled up for the element when it was optimized away.
    $authenticated_user->removeRole($role)->save();
    $element = $test_element;
    $element['#markup'] = 'this should be visible';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEquals('this should be visible', $output);
  }

}
