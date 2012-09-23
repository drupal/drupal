<?php

/**
 * Definition of Drupal\views\Tests\ViewExecutable.
 */

namespace Drupal\views\Tests;

/**
 * Tests the ViewExecutable class.
 *
 * @see Drupal\views\ViewExecutableExecutable
 */
class ViewExecutableTest extends ViewTestBase {

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
   * Tests the generation of the executable object.
   */
  public function testConstructing() {
    $view = $this->getView();
  }

  /**
   * Tests the accessing of values on the object.
   */
  public function testProperties() {
    $view = $this->getView();
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
    $this->assertTrue($view->displayHandlers['default'] instanceof \Drupal\views\Plugin\views\display\DefaultDisplay);
    $this->assertTrue($view->displayHandlers['page'] instanceof \Drupal\views\Plugin\views\display\Page);
    $this->assertTrue($view->displayHandlers['page_2'] instanceof \Drupal\views\Plugin\views\display\Page);

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
}
