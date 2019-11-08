<?php

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
  protected $defaultTheme = 'stark';

  /**
   * Tests the generated title of a user: roles argument.
   */
  public function testArgumentTitle() {
    $role_id = $this->createRole([], 'markup_role_name', '<em>Role name with markup</em>');
    $user = $this->createUser();
    $user->addRole($role_id);
    $user->save();

    $this->drupalGet('/user_roles_rid_test/markup_role_name');
    $this->assertEscaped('<em>Role name with markup</em>');
  }

}
