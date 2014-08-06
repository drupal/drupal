<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\ArgumentValidateTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\Core\Form\FormState;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Views;

/**
 * Tests user argument validators for ID and name.
 *
 * @group user
 */
class ArgumentValidateTest extends UserTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view_argument_validate_user', 'test_view_argument_validate_username');

  protected function setUp() {
    parent::setUp();

    $this->account = $this->drupalCreateUser();
  }

  /**
   * Tests the User (ID) argument validator.
   */
  function testArgumentValidateUserUid() {
    $account = $this->account;

    $view = Views::getView('test_view_argument_validate_user');
    $this->executeView($view);

    $this->assertTrue($view->argument['null']->validateArgument($account->id()));
    // Reset argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a valid numeric, but for a user that doesn't exist
    $this->assertFalse($view->argument['null']->validateArgument(32));

    $form = array();
    $form_state = new FormState();
    $view->argument['null']->buildOptionsForm($form, $form_state);
    $sanitized_id = ArgumentPluginBase::encodeValidatorId('entity:user');
    $this->assertTrue($form['validate']['options'][$sanitized_id]['roles']['#states']['visible'][':input[name="options[validate][options][' . $sanitized_id . '][restrict_roles]"]']['checked']);
  }

  /**
   * Tests the UserName argument validator.
   */
  public function testArgumentValidateUserName() {
    $account = $this->account;

    $view = Views::getView('test_view_argument_validate_username');
    $this->executeView($view);

    $this->assertTrue($view->argument['null']->validateArgument($account->getUsername()));
    // Reset argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a valid string, but for a user that doesn't exist
    $this->assertFalse($view->argument['null']->validateArgument($this->randomMachineName()));
  }

}
