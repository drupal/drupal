<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\ArgumentValidatorTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests Views argument validators.
 */
class ArgumentValidatorTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view_argument_validate_numeric');

  public static function getInfo() {
    return array(
      'name' => 'Argument validator',
      'group' => 'Views Plugins',
      'description' => 'Test argument validator tests.',
    );
  }

  function testArgumentValidateNumeric() {
    $view = Views::getView('test_view_argument_validate_numeric');
    $view->initHandlers();
    $this->assertFalse($view->argument['null']->validateArgument($this->randomString()));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    $this->assertTrue($view->argument['null']->validateArgument(12));
  }

}
