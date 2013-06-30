<?php

/**
 * @file
 * Contains \Drupal\php\Tests\Plugin\views\ArgumentValidatorTest.
 */

namespace Drupal\php\Tests\Plugin\views;

use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Tests Views argument validators.
 *
 * @see \Drupal\php\Plugin\views\argument_validator\Php
 */
class PhpArgumentValidatorTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view_argument_validate_php');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('php');

  public static function getInfo() {
    return array(
      'name' => 'PHP argument validator',
      'group' => 'Views Plugins',
      'description' => 'Test PHP argument validator.',
    );
  }

  /**
   * Tests the validateArgument question.
   */
  public function testArgumentValidatePhp() {
    $string = $this->randomName();
    $view = views_get_view('test_view_argument_validate_php');
    $view->setDisplay();
    $view->displayHandlers->get('default')->options['arguments']['null']['validate_options']['code'] = 'return $argument == \''. $string .'\';';

    $view->initHandlers();
    $this->assertTrue($view->argument['null']->validateArgument($string));
    // Reset saved argument validation.
    $view->argument['null']->argument_validated = NULL;
    $this->assertFalse($view->argument['null']->validateArgument($this->randomName()));
  }

}
