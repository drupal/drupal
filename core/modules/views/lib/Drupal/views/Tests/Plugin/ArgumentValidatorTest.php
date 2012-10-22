<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\ArgumentValidatorTest.
 */

namespace Drupal\views\Tests\Plugin;

/**
 * Tests Views argument validators.
 */
class ArgumentValidatorTest extends PluginTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Argument validator',
      'group' => 'Views Plugins',
      'description' => 'Test argument validator tests.',
    );
  }

  function testArgumentValidatePhp() {
    $string = $this->randomName();
    $view = $this->createViewFromConfig('test_view_argument_validate_php');
    $view->displayHandlers['default']->options['arguments']['null']['validate_options']['code'] = 'return $argument == \''. $string .'\';';

    $view->preExecute();
    $view->initHandlers();
    $this->assertTrue($view->argument['null']->validateArgument($string));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    $this->assertFalse($view->argument['null']->validateArgument($this->randomName()));
  }

  function testArgumentValidateNumeric() {
    $view = $this->createViewFromConfig('test_view_argument_validate_numeric');
    $view->preExecute();
    $view->initHandlers();
    $this->assertFalse($view->argument['null']->validateArgument($this->randomString()));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    $this->assertTrue($view->argument['null']->validateArgument(12));
  }

}
