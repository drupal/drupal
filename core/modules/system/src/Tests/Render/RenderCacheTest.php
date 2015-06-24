<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Render\RenderCacheTest.
 */

namespace Drupal\system\Tests\Render;

use Drupal\simpletest\KernelTestBase;
use Drupal\simpletest\UserCreationTrait;

/**
 * Tests the caching of render items via functional tests.
 *
 * @group Render
 */
class RenderCacheTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
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
   * Tests that user 1 has a different permission context with the same roles.
   */
  public function testUser1PermissionContext() {
    $this->doTestUser1WithContexts(['user.permissions']);
  }

  /**
   * Tests that user 1 has a different roles context with the same roles.
   */
  public function testUser1RolesContext() {
    $this->doTestUser1WithContexts(['user.roles']);
  }

  /**
   * Ensures that user 1 has a unique render cache for the given context.
   *
   * @param string[] $contexts
   *   List of cache contexts to use.
   */
  protected function doTestUser1WithContexts($contexts) {
    // Test that user 1 does not share the cache with other users who have the
    // same roles, even when using a role-based cache context.
    $user1 = $this->createUser();
    $this->assertEqual($user1->id(), 1);
    $first_authenticated_user = $this->createUser();
    $second_authenticated_user = $this->createUser();
    $admin_user = $this->createUser([], NULL, TRUE);

    $this->assertEqual($user1->getRoles(), $first_authenticated_user->getRoles(), 'User 1 has the same roles as an authenticated user.');
    // Impersonate user 1 and render content that only user 1 should have
    // permission to see.
    \Drupal::service('account_switcher')->switchTo($user1);
    $test_element = [
      '#cache' => [
        'keys' => ['test'],
        'contexts' => $contexts,
      ],
    ];
    $element = $test_element;
    $element['#markup'] = 'content for user 1';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEqual($output, 'content for user 1');

    // Verify the cache is working by rendering the same element but with
    // different markup passed in; the result should be the same.
    $element = $test_element;
    $element['#markup'] = 'should not be used';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEqual($output, 'content for user 1');
    \Drupal::service('account_switcher')->switchBack();

    // Verify that the first authenticated user does not see the same content
    // as user 1.
    \Drupal::service('account_switcher')->switchTo($first_authenticated_user);
    $element = $test_element;
    $element['#markup'] = 'content for authenticated users';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEqual($output, 'content for authenticated users');
    \Drupal::service('account_switcher')->switchBack();

    // Verify that the second authenticated user shares the cache with the
    // first authenticated user.
    \Drupal::service('account_switcher')->switchTo($second_authenticated_user);
    $element = $test_element;
    $element['#markup'] = 'should not be used';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEqual($output, 'content for authenticated users');
    \Drupal::service('account_switcher')->switchBack();

    // Verify that the admin user (who has an admin role without explicit
    // permissions) does not share the same cache.
    \Drupal::service('account_switcher')->switchTo($admin_user);
    $element = $test_element;
    $element['#markup'] = 'content for admin user';
    $output = \Drupal::service('renderer')->renderRoot($element);
    $this->assertEqual($output, 'content for admin user');
    \Drupal::service('account_switcher')->switchBack();
  }

}
