<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;
use Drupal\views_test_data\Plugin\views\argument_validator\ArgumentValidatorTest as ArgumentValidatorTestPlugin;

/**
 * Tests Views argument validators.
 *
 * @group views
 */
class ArgumentValidatorTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view_argument_validate_numeric', 'test_view'];

  function testArgumentValidateNumeric() {
    $view = Views::getView('test_view_argument_validate_numeric');
    $view->initHandlers();
    $this->assertFalse($view->argument['null']->validateArgument($this->randomString()));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    $this->assertTrue($view->argument['null']->validateArgument(12));
  }

  /**
   * Tests the argument validator test plugin.
   *
   * @see Drupal\views_test_data\Plugin\views\argument_validator\ArgumentValidatorTest
   */
  public function testArgumentValidatorPlugin() {
    $view = Views::getView('test_view');

    // Add a new argument and set the test plugin for the argument_validator.
    $options = [
      'specify_validation' => TRUE,
      'validate' => [
        'type' => 'argument_validator_test'
      ]
    ];
    $id = $view->addHandler('default', 'argument', 'views_test_data', 'name', $options);
    $view->initHandlers();

    $test_value = $this->randomMachineName();

    $argument = $view->argument[$id];
    $argument->options['validate_options']['test_value'] = $test_value;
    $this->assertFalse($argument->validateArgument($this->randomMachineName()), 'A random value does not validate.');
    // Reset internal flag.
    $argument->argument_validated = NULL;
    $this->assertTrue($argument->validateArgument($test_value), 'The right argument validates.');

    $plugin = $argument->getPlugin('argument_validator');
    $this->assertTrue($plugin instanceof ArgumentValidatorTestPlugin, 'The correct argument validator plugin is used.');
    $this->assertFalse($plugin->validateArgument($this->randomMachineName()), 'A random value does not validate.');
    $this->assertTrue($plugin->validateArgument($test_value), 'The right argument validates.');
  }

}
