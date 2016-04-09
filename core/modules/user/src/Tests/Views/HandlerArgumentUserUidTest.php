<?php

namespace Drupal\user\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the handler of the user: uid Argument.
 *
 * @group user
 */
class HandlerArgumentUserUidTest extends UserTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_user_uid_argument');

  /**
   * Tests the generated title of an user: uid argument.
   */
  public function testArgumentTitle() {
    $view = Views::getView('test_user_uid_argument');

    // Tests an invalid user uid.
    $this->executeView($view, array(rand(1000, 10000)));
    $this->assertFalse($view->getTitle());
    $view->destroy();

    // Tests a valid user.
    $account = $this->drupalCreateUser();
    $this->executeView($view, array($account->id()));
    $this->assertEqual($view->getTitle(), $account->label());
    $view->destroy();

    // Tests the anonymous user.
    $anonymous = $this->config('user.settings')->get('anonymous');
    $this->executeView($view, array(0));
    $this->assertEqual($view->getTitle(), $anonymous);
    $view->destroy();

    $view->getDisplay()->getHandler('argument', 'uid')->options['break_phrase'] = TRUE;
    $this->executeView($view, array($account->id() . ',0'));
    $this->assertEqual($view->getTitle(), $account->label() . ', ' . $anonymous);
    $view->destroy();

    $view->getDisplay()->getHandler('argument', 'uid')->options['break_phrase'] = TRUE;
    $this->executeView($view, array('0,' . $account->id()));
    $this->assertEqual($view->getTitle(), $anonymous . ', ' . $account->label());
    $view->destroy();
  }

}
