<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional\Views;

/**
 * Tests the handler of the user: roles argument.
 *
 * @group user
 * @see \Drupal\user\Plugin\views\argument\RolesRid
 */
class RolesRidArgumentTest extends UserTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_user_roles_rid'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the generated title of a user: roles argument.
   */
  public function testArgumentTitle(): void {
    $role_id = $this->createRole([], 'markup_role_name', '<em>Role name with markup</em>');
    $this->createRole([], 'second_role_name', 'Second role name');
    $user = $this->createUser([], 'User with role one');
    $user->addRole($role_id)->save();
    $second_user = $this->createUser([], 'User with role two');
    $second_user->addRole('second_role_name')->save();

    $this->drupalGet('/user_roles_rid_test/markup_role_name');
    $this->assertSession()->assertEscaped('<em>Role name with markup</em>');

    $views_user = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($views_user);

    // Change the View to allow multiple values for the roles.
    $edit = [
      'options[break_phrase]' => TRUE,
    ];
    $this->drupalGet('admin/structure/views/nojs/handler/test_user_roles_rid/page_1/argument/roles_target_id');
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');

    $this->drupalGet('/user_roles_rid_test/markup_role_name+second_role_name');
    $this->assertSession()->pageTextContains('User with role one');
    $this->assertSession()->pageTextContains('User with role two');
  }

}
