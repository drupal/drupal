<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\ArgumentNullTest.
 */

namespace Drupal\views\Tests\Handler;

/**
 * Tests the core Drupal\views\Plugin\views\argument\Null handler.
 */
class ArgumentNullTest extends HandlerTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Argument: Null',
      'description' => 'Test the core Drupal\views\Plugin\views\argument\Null handler.',
      'group' => 'Views Handlers',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['id']['argument']['id'] = 'null';

    return $data;
  }

  public function testAreaText() {
    // Test validation
    $view = $this->getView();

    // Add a null argument.
    $string = $this->randomString();
    $view->displayHandlers['default']->overrideOption('arguments', array(
      'null' => array(
        'id' => 'null',
        'table' => 'views',
        'field' => 'null',
      ),
    ));

    $this->executeView($view);

    // Make sure that the argument is not validated yet.
    unset($view->argument['null']->argument_validated);
    $this->assertTrue($view->argument['null']->validateArgument(26));
    // test must_not_be option.
    unset($view->argument['null']->argument_validated);
    $view->argument['null']->options['must_not_be'] = TRUE;
    $this->assertFalse($view->argument['null']->validateArgument(26), 'must_not_be returns FALSE, if there is an argument');
    unset($view->argument['null']->argument_validated);
    $this->assertTrue($view->argument['null']->validateArgument(NULL), 'must_not_be returns TRUE, if there is no argument');

    // Test execution.
    $view = $this->getView();

    // Add a argument, which has null as handler.
    $string = $this->randomString();
    $view->displayHandlers['default']->overrideOption('arguments', array(
      'id' => array(
        'id' => 'id',
        'table' => 'views_test_data',
        'field' => 'id',
      ),
    ));

    $this->executeView($view, array(26));

    // The argument should be ignored, so every result should return.
    $this->assertEqual(5, count($view->result));
  }

}
