<?php

  /**
   * @file
   * Definition of Drupal\views\Tests\User\HandlerArgumentUserUidTest.
   */

namespace Drupal\views\Tests\User;

/**
 * Tests views user uid argument handler.
 */
class HandlerArgumentUserUidTest extends UserTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User: Uid Argument',
      'description' => 'Tests the handler of the user: uid Argument.',
      'group' => 'Views Modules',
    );
  }

  /**
   * Tests the generated title of an user: uid argument.
   */
  public function testArgumentTitle() {
    $view = views_get_view('test_user_uid_argument');

    // Tests an invalid user uid.
    $this->executeView($view, array(rand(1000, 10000)));
    $this->assertFalse($view->getTitle());
    $view->destroy();

    // Tests a valid user.
    $account = $this->drupalCreateUser();
    $this->executeView($view, array($account->uid));
    $this->assertEqual($view->getTitle(), $account->label());
    $view->destroy();
  }

}
