<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional\Views;

use Drupal\Component\Utility\Html;
use Drupal\user\Entity\User;

/**
 * Tests the handler of the user: role field.
 *
 * @group user
 * @see views_handler_field_user_name
 */
class HandlerFieldRoleTest extends UserTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_views_handler_field_role'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the rendering of user roles in a Views field handler.
   */
  public function testRole(): void {
    // Create a couple of roles for the view.
    $role_name_a = 'a' . $this->randomMachineName(8);
    $this->drupalCreateRole(['access content'], $role_name_a, '<em>' . $role_name_a . '</em>', 9);

    $role_name_b = 'b' . $this->randomMachineName(8);
    $this->drupalCreateRole(['access content'], $role_name_b, $role_name_b, 8);

    $role_name_not_assigned = $this->randomMachineName(8);
    $this->drupalCreateRole(['access content'], $role_name_not_assigned, $role_name_not_assigned);

    // Add roles to user 1.
    $user = User::load(1);
    $user->addRole($role_name_a)->addRole($role_name_b)->save();

    $this->drupalLogin($this->createUser(['access user profiles']));
    $this->drupalGet('/test-views-handler-field-role');
    // Verify that the view test_views_handler_field_role renders role assigned
    // to user in the correct order and markup in role names is escaped.
    $this->assertSession()->responseContains($role_name_b . Html::escape('<em>' . $role_name_a . '</em>'));
    // Verify that the view test_views_handler_field_role does not render a role
    // not assigned to a user.
    $this->assertSession()->pageTextNotContains($role_name_not_assigned);
  }

}
