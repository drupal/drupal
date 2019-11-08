<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\views\Views;

/**
 * Tests the Argument validator through the UI.
 *
 * @group views_ui
 */
class ArgumentValidatorTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_argument'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the 'Specify validation criteria' checkbox functionality.
   */
  public function testSpecifyValidation() {
    // Specify a validation based on Node for the 'id' argument on the default
    // display and assert that this works.
    $this->saveArgumentHandlerWithValidationOptions(TRUE);
    $view = Views::getView('test_argument');
    $handler = $view->getHandler('default', 'argument', 'id');
    $this->assertTrue($handler['specify_validation'], 'Validation for this argument has been turned on.');
    $this->assertEqual('entity:node', $handler['validate']['type'], 'Validation for the argument is based on the node.');

    // Uncheck the 'Specify validation criteria' checkbox and expect the
    // validation type to be reset back to 'none'.
    $this->saveArgumentHandlerWithValidationOptions(FALSE);
    $view = Views::getView('test_argument');
    $handler = $view->getHandler('default', 'argument', 'id');
    $this->assertFalse($handler['specify_validation'], 'Validation for this argument has been turned off.');
    $this->assertEqual('none', $handler['validate']['type'], 'Validation for the argument has been reverted to Basic Validation.');
  }

  /**
   * Saves the test_argument view with changes made to the argument handler
   * both with and without specify_validation turned on.
   *
   * @param bool $specify_validation
   */
  protected function saveArgumentHandlerWithValidationOptions($specify_validation) {
    $options = [
      'options[validate][type]' => 'entity---node',
      'options[specify_validation]' => $specify_validation,
    ];
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_argument/default/argument/id', $options, t('Apply'));
    $this->drupalPostForm('admin/structure/views/view/test_argument', [], t('Save'));
  }

}
