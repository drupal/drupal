<?php

namespace Drupal\Tests\user\Kernel\Views;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\views\Views;

/**
 * Tests rendering when the role is numeric.
 *
 * @group user
 */
class UserRoleTest extends ViewsKernelTestBase {

  /**
   * Tests numeric role.
   */
  public function testNumericRole() {
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);

    Role::create(['id' => 123, 'label' => 'Numeric'])
      ->save();

    $user = User::create([
      'uid' => 2,
      'name' => 'foo',
      'roles' => 123,
    ]);
    $user->save();

    $view = Views::getView('user_admin_people');
    $this->executeView($view);
    $view->render('user_admin_people');
    $output = $view->field['roles_target_id']->render($view->result[0]);
    $this->assertEquals(2, $output);
  }

}
