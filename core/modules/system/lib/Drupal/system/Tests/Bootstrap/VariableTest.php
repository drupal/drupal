<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Bootstrap\VariableTest.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\simpletest\WebTestBase;

/**
 * Tests variable system functions.
 */
class VariableTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system_test');

  public static function getInfo() {
    return array(
      'name' => 'Variable test',
      'description' => 'Make sure the variable system functions correctly.',
      'group' => 'Bootstrap'
    );
  }

  /**
   * Tests variables then deletes them.
   */
  function testVariable() {
    // Setting and retrieving values.
    $variable = $this->randomName();
    variable_set('simpletest_bootstrap_variable_test', $variable);
    $this->assertIdentical($variable, variable_get('simpletest_bootstrap_variable_test'), 'Setting and retrieving values');

    // Make sure the variable persists across multiple requests.
    $this->drupalGet('system-test/variable-get');
    $this->assertText($variable, 'Variable persists across multiple requests');

    // Deleting variables.
    $default_value = $this->randomName();
    variable_del('simpletest_bootstrap_variable_test');
    $variable = variable_get('simpletest_bootstrap_variable_test', $default_value);
    $this->assertIdentical($variable, $default_value, 'Deleting variables');
  }

  /**
   * Makes sure that the default variable parameter is passed through okay.
   */
  function testVariableDefaults() {
    // Tests passing nothing through to the default.
    $this->assertIdentical(NULL, variable_get('simpletest_bootstrap_variable_test'), 'Variables are correctly defaulting to NULL.');

    // Tests passing 5 to the default parameter.
    $this->assertIdentical(5, variable_get('simpletest_bootstrap_variable_test', 5), 'The default variable parameter is passed through correctly.');
  }

}
