<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\argument\NullArgument handler.
 *
 * @group views
 */
class ArgumentNullTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['id']['argument']['id'] = 'null';

    return $data;
  }

  public function testAreaText() {
    // Test validation
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Add a null argument.
    $view->displayHandlers->get('default')->overrideOption('arguments', array(
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
    $view->destroy();
    $view->setDisplay();

    // Add a argument, which has null as handler.
    $view->displayHandlers->get('default')->overrideOption('arguments', array(
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
