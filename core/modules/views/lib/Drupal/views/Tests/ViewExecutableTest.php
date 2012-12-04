<?php

/**
 * @file
 * Definition of Drupal\views\Tests\ViewExecutableTest.
 */

namespace Drupal\views\Tests;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DefaultDisplay;
use Drupal\views\Plugin\views\display\Page;

/**
 * Tests the ViewExecutable class.
 *
 * @see Drupal\views\ViewExecutable
 */
class ViewExecutableTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_destroy', 'test_executable_displays');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment');

  /**
   * Properties that should be stored in the configuration.
   *
   * @var array
   */
  protected $configProperties = array(
    'disabled',
    'api_version',
    'name',
    'description',
    'tag',
    'base_table',
    'human_name',
    'core',
    'display',
  );

  /**
   * Properties that should be stored in the executable.
   *
   * @var array
   */
  protected $executableProperties = array(
    'build_info'
  );

  public static function getInfo() {
    return array(
      'name' => 'View executable tests',
      'description' => 'Tests the ViewExecutable class.',
      'group' => 'Views'
    );
  }

  /**
   * Tests the initDisplay() and initHandlers() methods.
   */
  public function testInitMethods() {
    $view = views_get_view('test_destroy');
    $view->initDisplay();

    $this->assertTrue($view->display_handler instanceof DefaultDisplay, 'Make sure a reference to the current display handler is set.');
    $this->assertTrue($view->displayHandlers['default'] instanceof DefaultDisplay, 'Make sure a display handler is created for each display.');

    $view->destroy();
    $view->initHandlers();

    // Check for all handler types.
    $handler_types = array_keys(ViewExecutable::viewsHandlerTypes());
    foreach ($handler_types as $type) {
      // The views_test integration doesn't have relationships.
      if ($type == 'relationship') {
        continue;
      }
      $this->assertTrue(count($view->$type), format_string('Make sure a %type instance got instantiated.', array('%type' => $type)));
    }

    // initHandlers() should create display handlers automatically as well.
    $this->assertTrue($view->display_handler instanceof DefaultDisplay, 'Make sure a reference to the current display handler is set.');
    $this->assertTrue($view->displayHandlers['default'] instanceof DefaultDisplay, 'Make sure a display handler is created for each display.');
  }

  /**
   * Tests the generation of the executable object.
   */
  public function testConstructing() {
    views_get_view('test_destroy');
  }

  /**
   * Tests the accessing of values on the object.
   */
  public function testProperties() {
    $view = views_get_view('test_destroy');
    foreach ($this->executableProperties as $property) {
      $this->assertTrue(isset($view->{$property}));
    }
  }

  /**
   * Tests the display related methods and properties.
   */
  public function testDisplays() {
    $view = views_get_view('test_executable_displays');

    // Tests Drupal\views\ViewExecutable::initDisplay().
    $view->initDisplay();
    $count = count($view->displayHandlers);
    $this->assertEqual($count, 3, format_string('Make sure all display handlers got instantiated (@count of @count_expected)', array('@count' => $count, '@count_expected' => 3)));
    // Tests the classes of the instances.
    $this->assertTrue($view->displayHandlers['default'] instanceof DefaultDisplay);
    $this->assertTrue($view->displayHandlers['page'] instanceof Page);
    $this->assertTrue($view->displayHandlers['page_2'] instanceof Page);

    // After initializing the default display is the current used display.
    $this->assertEqual($view->current_display, 'default');
    $this->assertEqual(spl_object_hash($view->display_handler), spl_object_hash($view->displayHandlers['default']));

    // All handlers should have a reference to the default display.
    $this->assertEqual(spl_object_hash($view->displayHandlers['page']->default_display), spl_object_hash($view->displayHandlers['default']));
    $this->assertEqual(spl_object_hash($view->displayHandlers['page_2']->default_display), spl_object_hash($view->displayHandlers['default']));

    // Tests Drupal\views\ViewExecutable::setDisplay().
    $view->setDisplay();
    $this->assertEqual($view->current_display, 'default', 'If setDisplay is called with no parameter the default display should be used.');
    $this->assertEqual(spl_object_hash($view->display_handler), spl_object_hash($view->displayHandlers['default']));

    // Set two different valid displays.
    $view->setDisplay('page');
    $this->assertEqual($view->current_display, 'page', 'If setDisplay is called with a valid display id the appropriate display should be used.');
    $this->assertEqual(spl_object_hash($view->display_handler), spl_object_hash($view->displayHandlers['page']));

    $view->setDisplay('page_2');
    $this->assertEqual($view->current_display, 'page_2', 'If setDisplay is called with a valid display id the appropriate display should be used.');
    $this->assertEqual(spl_object_hash($view->display_handler), spl_object_hash($view->displayHandlers['page_2']));
  }

  /**
   * Tests the deconstructor to be sure that every kind of heavy objects are removed.
   */
  function testDestroy() {
    $view = views_get_view('test_destroy');

    $view->preview();
    $view->destroy();

    $this->assertViewDestroy($view);
  }

  function assertViewDestroy($view) {
    $this->assertFalse(isset($view->displayHandlers['default']), 'Make sure all displays are destroyed.');
    $this->assertFalse(isset($view->displayHandlers['attachment_1']), 'Make sure all displays are destroyed.');

    $this->assertFalse(isset($view->filter), 'Make sure all filter handlers are destroyed');
    $this->assertFalse(isset($view->field), 'Make sure all field handlers are destroyed');
    $this->assertFalse(isset($view->argument), 'Make sure all argument handlers are destroyed');
    $this->assertFalse(isset($view->relationship), 'Make sure all relationship handlers are destroyed');
    $this->assertFalse(isset($view->sort), 'Make sure all sort handlers are destroyed');
    $this->assertFalse(isset($view->area), 'Make sure all area handlers are destroyed');

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

  /**
   * Tests ViewExecutable::viewsHandlerTypes().
   */
  public function testViewsHandlerTypes() {
    $types = ViewExecutable::viewsHandlerTypes();
    foreach (array('field', 'filter', 'argument', 'sort', 'header', 'footer', 'empty') as $type) {
      $this->assertTrue(isset($types[$type]));
      // @todo The key on the display should be footers, headers and empties
      //   or something similar instead of the singular, but so long check for
      //   this special case.
      if (isset($types[$type]['type']) && $types[$type]['type'] == 'area') {
        $this->assertEqual($types[$type]['plural'], $type);
      }
      else {
        $this->assertEqual($types[$type]['plural'], $type . 's');
      }
    }
  }

  function testValidate() {
    // Test a view with multiple displays.
    // Validating a view shouldn't change the active display.
    // @todo Create an extra validation view.
    $view = views_get_view('test_destroy');
    $view->setDisplay('page_1');

    $view->validate();

    $this->assertEqual('page_1', $view->current_display, "The display should be constant while validating");

    // @todo Write real tests for the validation.
    // In general the following things could be tested:
    //   - Deleted displays shouldn't be validated
    //   - Multiple displays are validating and the errors are merged together.
  }

}
