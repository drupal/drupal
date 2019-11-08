<?php

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

  public function testRole() {
    // Create a couple of roles for the view.
    $rolename_a = 'a' . $this->randomMachineName(8);
    $this->drupalCreateRole(['access content'], $rolename_a, '<em>' . $rolename_a . '</em>', 9);

    $rolename_b = 'b' . $this->randomMachineName(8);
    $this->drupalCreateRole(['access content'], $rolename_b, $rolename_b, 8);

    $rolename_not_assigned = $this->randomMachineName(8);
    $this->drupalCreateRole(['access content'], $rolename_not_assigned, $rolename_not_assigned);

    // Add roles to user 1.
    $user = User::load(1);
    $user->addRole($rolename_a);
    $user->addRole($rolename_b);
    $user->save();

    $this->drupalLogin($this->createUser(['access user profiles']));
    $this->drupalGet('/test-views-handler-field-role');
    $this->assertText($rolename_b . Html::escape('<em>' . $rolename_a . '</em>'), 'View test_views_handler_field_role renders role assigned to user in the correct order and markup in role names is escaped.');
    $this->assertNoText($rolename_not_assigned, 'View test_views_handler_field_role does not render a role not assigned to a user.');
  }

}
