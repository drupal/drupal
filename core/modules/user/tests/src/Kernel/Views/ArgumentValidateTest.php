<?php

namespace Drupal\Tests\user\Kernel\Views;

use Drupal\Core\Form\FormState;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests user argument validators for ID and name.
 *
 * @group user
 */
class ArgumentValidateTest extends ViewsKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'user_test_views',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_view_argument_validate_user', 'test_view_argument_validate_username'];

  /**
   * A user for this test.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->account = $this->createUser();
    ViewTestData::createTestViews(get_class($this), ['user_test_views']);
  }

  /**
   * Tests the User (ID) argument validator.
   */
  public function testArgumentValidateUserUid() {
    $view = Views::getView('test_view_argument_validate_user');
    $this->executeView($view);

    $this->assertTrue($view->argument['null']->validateArgument($this->account->id()));
    // Reset argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a valid numeric, but for a user that doesn't exist
    $this->assertFalse($view->argument['null']->validateArgument(32));

    $form = [];
    $form_state = new FormState();
    $view->argument['null']->buildOptionsForm($form, $form_state);
    $sanitized_id = ArgumentPluginBase::encodeValidatorId('entity:user');
    $this->assertTrue($form['validate']['options'][$sanitized_id]['roles']['#states']['visible'][':input[name="options[validate][options][' . $sanitized_id . '][restrict_roles]"]']['checked']);
  }

  /**
   * Tests the UserName argument validator.
   */
  public function testArgumentValidateUserName() {
    $view = Views::getView('test_view_argument_validate_username');
    $this->executeView($view);

    $this->assertTrue($view->argument['null']->validateArgument($this->account->getAccountName()));
    // Reset argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a valid string, but for a user that doesn't exist
    $this->assertFalse($view->argument['null']->validateArgument($this->randomMachineName()));
  }

}
