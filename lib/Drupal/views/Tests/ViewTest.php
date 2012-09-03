<?php

/**
 * @file
 * Definition of Drupal\views\Tests\ViewTest.
 */

namespace Drupal\views\Tests;

/**
 * Views class tests.
 */
class ViewTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment');

  public static function getInfo() {
    return array(
      'name' => 'Views object',
      'description' => 'Tests some functionality of the View class.',
      'group' => 'Views',
    );
  }

  /**
   * Tests the deconstructor to be sure that every kind of heavy objects are removed.
   */
  function testDestroy() {
    $view = $this->getView();

    $view->preview();
    $view->destroy();

    $this->assertViewDestroy($view);

    // Manipulate the display variable to test a previous bug.
    $view = $this->getView();
    $view->preview();

    $view->destroy();
    $this->assertViewDestroy($view);
  }

  function assertViewDestroy($view) {
    $this->assertFalse(isset($view->display['default']->handler), 'Make sure all displays are destroyed.');
    $this->assertFalse(isset($view->display['attachment_1']->handler), 'Make sure all displays are destroyed.');

    $this->assertFalse(isset($view->filter), 'Make sure all handlers are destroyed');
    $this->assertFalse(isset($view->field), 'Make sure all handlers are destroyed');
    $this->assertFalse(isset($view->argument), 'Make sure all handlers are destroyed');
    $this->assertFalse(isset($view->relationship), 'Make sure all handlers are destroyed');
    $this->assertFalse(isset($view->sort), 'Make sure all handlers are destroyed');
    $this->assertFalse(isset($view->area), 'Make sure all handlers are destroyed');

    $keys = array('current_display', 'display_handler', 'field', 'argument', 'filter', 'sort', 'relationship', 'header', 'footer', 'empty', 'query', 'result', 'inited', 'style_plugin', 'plugin_name', 'exposed_data', 'exposed_input', 'many_to_one_tables');
    foreach ($keys as $key) {
      $this->assertFalse(isset($view->{$key}), $key);
    }
    $this->assertEqual($view->built, FALSE);
    $this->assertEqual($view->executed, FALSE);
    $this->assertEqual($view->build_info, array());
    $this->assertEqual($view->attachment_before, '');
    $this->assertEqual($view->attachment_after, '');
  }

  function testValidate() {
    // Test a view with multiple displays.
    // Validating a view shouldn't change the active display.
    // @todo: Create an extra validation view.
    $this->view->setDisplay('page_1');

    $this->view->validate();

    $this->assertEqual('page_1', $this->view->current_display, "The display should be constant while validating");

    // @todo: Write real tests for the validation.
    // In general the following things could be tested:
    // - Deleted displays shouldn't be validated
    // - Multiple displays are validating and the errors are merged together.
  }

  /**
   * Overrides Drupal\views\Tests\ViewTestBase::getBasicView().
   */
  protected function getBasicView() {
    return $this->createViewFromConfig('test_destroy');
  }

}
