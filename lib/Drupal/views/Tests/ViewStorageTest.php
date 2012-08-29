<?php

/**
 * Definition of Drupal\views\Tests\ViewStorageTest.
 */

namespace Drupal\views\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\views\ViewStorageController;
use Drupal\views\View;
use Drupal\views\ViewDisplay;
use Drupal\views\Plugin\views\display\Page;

/**
 * Tests the functionality of the ViewStorageController.
 */
class ViewStorageTest extends WebTestBase {

  /**
   * Properties that should be stored in the configuration.
   *
   * @var array
   */
  protected $config_properties = array(
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
   * The Configurable information from entity_get_info().
   *
   * @var array
   */
  protected $info;

  /**
   * The Configurable controller.
   *
   * @var Drupal\views\ViewStorageController
   */
  protected $controller;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views');

  public static function getInfo() {
    return array(
      'name' => 'Configurables CRUD tests',
      'description' => 'Tests the CRUD functionality for ViewStorage.',
      'group' => 'Views',
    );
  }

  /**
   * Tests CRUD operations.
   */
  function testConfigurableCRUD() {
    // Get the Configurable information and controller.
    $this->info = entity_get_info('view');
    $this->controller = entity_get_controller('view');

    // Confirm that an info array has been returned.
    $this->assertTrue(!empty($this->info) && is_array($this->info), 'The View info array is loaded.');

    // Confirm we have the correct controller class.
    $this->assertTrue($this->controller instanceof ViewStorageController, 'The correct controller is loaded.');

    // CRUD tests.
    $this->loadTests();
    $this->createTests();
    $this->saveTests();
    $this->deleteTests();
    $this->displayTests();
    $this->statusTests();
  }

  /**
   * Tests loading configurables.
   */
  protected function loadTests() {
    $view = $this->loadView('archive');

    // Confirm that an actual view object is loaded and that it returns all of
    // expected properties.
    $this->assertTrue($view instanceof View, 'Single View instance loaded.');
    foreach ($this->config_properties as $property) {
      $this->assertTrue(isset($view->{$property}), format_string('Property: @property loaded onto View.', array('@property' => $property)));
    }

    // Check the displays have been loaded correctly from config display data.
    $expected_displays = array('default', 'page', 'block');
    $this->assertEqual(array_keys($view->display), $expected_displays, 'The correct display names are present.');

    // Check each ViewDisplay object and confirm that it has the correct key.
    foreach ($view->display as $key => $display) {
      $this->assertTrue($display instanceof ViewDisplay, format_string('Display: @display is instance of ViewDisplay.', array('@display' => $key)));
      $this->assertEqual($key, $display->id, 'The display has the correct ID.');
      // Confirm that the display options array exists.
      $display_options = $display->display_options;
      $this->assertTrue(!empty($display_options) && is_array($display_options), 'Display options exist.');
    }

    // Fetch data for all Configurable objects and default view configurations.
    $all_configurables = $this->controller->load();
    $all_config = config_get_storage_names_with_prefix('views.view');

    // Remove the 'views.view.' prefix from config names for comparision with
    // loaded Configurable objects.
    $prefix_map = function ($value) {
      $parts = explode('.', $value);
      return end($parts);
    };

    // Check that the correct number of Configurable objects have been loaded.
    $count = count($all_configurables);
    $this->assertEqual($count, count($all_config), format_string('The array of all @count Configurable objects is loaded.', array('@count' => $count)));

    // Check that all of these machine names match.
    $this->assertIdentical(array_keys($all_configurables), array_map($prefix_map, $all_config), 'All loaded elements match.');
  }

  /**
   * Tests creating configurables.
   */
  protected function createTests() {
    // Create a new View instance with empty values.
    $created = $this->controller->create(array());

    $this->assertTrue($created instanceof View, 'Created object is a View.');
    // Check that the View contains all of the properties.
    foreach ($this->config_properties as $property) {
      $this->assertTrue(property_exists($created, $property), format_string('Property: @property created on View.', array('@property' => $property)));
    }

    // Create a new View instance with config values.
    $values = config('views.view.glossary')->get();
    $created = $this->controller->create($values);

    $this->assertTrue($created instanceof View, 'Created object is a View.');
    // Check that the View contains all of the properties.
    $properties = $this->config_properties;
    // Remove display from list.
    array_pop($properties);

    // Test all properties except displays.
    foreach ($properties as $property) {
      $this->assertTrue(isset($created->{$property}), format_string('Property: @property created on View.', array('@property' => $property)));
      $this->assertIdentical($values[$property], $created->{$property}, format_string('Property value: @property matches configuration value.', array('@property' => $property)));
    }

    // Test created displays.
    foreach ($created->display as $key => $display) {
      $this->assertTrue($display instanceof ViewDisplay, format_string('Display @display is an instance of ViewDisplay.', array('@display' => $key)));
    }

  }

  /**
   * Tests saving configurables.
   */
  protected function saveTests() {
    $view = $this->loadView('archive');

    // Save the newly created view, but modify the name.
    $view->set('name', 'archive_copy');
    $view->set('tag', 'changed');
    $view->save();

    // Load the newly saved config.
    $config = config('views.view.archive_copy');
    $this->assertFalse($config->isNew(), 'New config has been loaded.');

    $this->assertEqual($view->tag, $config->get('tag'), 'A changed value has been saved.');

    // Change a value and save.
    $view->tag = 'changed';
    $view->save();

    // Check values have been written to config.
    $config = config('views.view.archive_copy')->get();
    $this->assertEqual($view->tag, $config['tag'], 'View property saved to config.');

    // Check whether load, save and load produce the same kind of view.
    $values = config('views.view.archive')->get();
    $created = $this->controller->create($values);

    $created->save();
    $created_loaded = $this->loadView($created->id());
    $values_loaded = config('views.view.archive')->get();

    $this->assertTrue(isset($created_loaded->display['default']->display_options), 'Make sure that the display options exist.');
    $this->assertEqual($created_loaded->display['default']->display_plugin, 'default', 'Make sure the right display plugin is set.');

    $this->assertEqual($values, $values_loaded, 'The loaded config is the same as the original loaded one.');

  }

  /**
   * Tests deleting configurables.
   */
  protected function deleteTests() {
    $view = $this->loadView('tracker');

    // Delete the config.
    $view->delete();
    $config = config('views.view.tracker');

    $this->assertTrue($config->isNew(), 'Deleted config is now new.');
  }

  /**
   * Tests adding, saving, and loading displays on configurables.
   */
  protected function displayTests() {
    // Check whether a display can be added and saved to a View.
    $view = $this->loadView('frontpage');

    $view->new_display('page', 'Test', 'test');

    $new_display = $view->display['test'];
    $this->assertTrue($new_display instanceof ViewDisplay, 'New page display "test" created.');

    // Ensure the right display_plugin is created/instantiated.
    $this->assertEqual($new_display->display_plugin, 'page', 'New page display "test" uses the right display plugin.');
    $view->initDisplay();
    $this->assertTrue($new_display->handler instanceof Page, 'New page display "test" uses the right display plugin.');


    $view->set('name', 'frontpage_new');
    $view->save();
    $values = config('views.view.frontpage_new')->get();

    $this->assertTrue(isset($values['display']['test']) && is_array($values['display']['test']), 'New display was saved.');
  }

  /**
   * Tests statuses of configurables.
   */
  protected function statusTests() {
    // Test a View can be enabled and disabled again (with a new view).
    $view = $this->loadView('backlinks');

    // The view should already be disabled.
    $view->enable();
    $this->assertTrue($view->isEnabled(), 'A view has been enabled.');

    // Check the saved values.
    $view->save();
    $config = config('views.view.backlinks')->get();
    $this->assertFalse($config['disabled'], 'The changed disabled property was saved.');

    // Disable the view.
    $view->disable();
    $this->assertFalse($view->isEnabled(), 'A view has been disabled.');

    // Check the saved values.
    $view->save();
    $config = config('views.view.backlinks')->get();
    $this->assertTrue($config['disabled'], 'The changed disabled property was saved.');
  }

  /**
   * Loads a single Configurable object from the controller.
   *
   * @param string $view_name
   *   The machine name of the view.
   *
   * @return object Drupal\views\View.
   *   The loaded view object.
   */
  protected function loadView($view_name) {
    $load = $this->controller->load(array($view_name));
    return reset($load);
  }

}
