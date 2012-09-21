<?php

/**
 * @file
 * Definition of Drupal\views\Tests\User\ArgumentValidateTest.
 */

namespace Drupal\views\Tests\User;

/**
 * Tests views user argument argument handler.
 */
class ArgumentValidateTest extends UserTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User: Argument validator',
      'description' => 'Tests user argument validator.',
      'group' => 'Views Modules',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->account = $this->drupalCreateUser();
  }

  function testArgumentValidateUserUid() {
    $account = $this->account;
    // test 'uid' case
    $view = $this->view_argument_validate_user('uid');
    $this->assertTrue($view->argument['null']->validateArgument($account->uid));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a string variable since type is 'uid'
    $this->assertFalse($view->argument['null']->validateArgument($account->name));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a valid numeric, but for a user that doesn't exist
    $this->assertFalse($view->argument['null']->validateArgument(32));
  }

  function testArgumentValidateUserName() {
    $account = $this->account;
    // test 'name' case
    $view = $this->view_argument_validate_user('name');
    $this->assertTrue($view->argument['null']->validateArgument($account->name));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a uid variable since type is 'name'
    $this->assertFalse($view->argument['null']->validateArgument($account->uid));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a valid string, but for a user that doesn't exist
    $this->assertFalse($view->argument['null']->validateArgument($this->randomName()));
  }

  function testArgumentValidateUserEither() {
    $account = $this->account;
    // test 'either' case
    $view = $this->view_argument_validate_user('either');
    $this->assertTrue($view->argument['null']->validateArgument($account->name));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a uid variable since type is 'name'
    $this->assertTrue($view->argument['null']->validateArgument($account->uid));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a valid string, but for a user that doesn't exist
    $this->assertFalse($view->argument['null']->validateArgument($this->randomName()));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a valid uid, but for a user that doesn't exist
    $this->assertFalse($view->argument['null']->validateArgument(32));
  }

  function view_argument_validate_user($argtype) {
    $view = $this->createViewFromConfig('test_view_argument_validate_user');
    $view->displayHandlers['default']->options['arguments']['null']['validate_options']['type'] = $argtype;
    $view->preExecute();
    $view->initHandlers();

    return $view;
  }

}
