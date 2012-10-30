<?php

/**
 * @file
 * Definition of Drupal\views\Tests\ViewStorageTest.
 */

namespace Drupal\views\Tests;

use Drupal\views\ViewStorageController;
use Drupal\views\Plugin\Core\Entity\View;
use Drupal\views\Plugin\views\display\Page;
use Drupal\views\Plugin\views\display\DefaultDisplay;
use Drupal\views\Plugin\views\display\Feed;

/**
 * Tests the functionality of View and ViewStorageController.
 *
 * @see Drupal\views\ViewStorage
 * @see Drupal\views\ViewStorageController
 */
class ViewStorageTest extends ViewTestBase {

  /**
   * Properties that should be stored in the configuration.
   *
   * @var array
   */
  protected $config_properties = array(
    'disabled',
    'api_version',
    'module',
    'name',
    'description',
    'tag',
    'base_table',
    'human_name',
    'core',
    'display',
  );

  /**
   * The configuration entity information from entity_get_info().
   *
   * @var array
   */
  protected $info;

  /**
   * The configuration entity storage controller.
   *
   * @var Drupal\views\ViewStorageController
   */
  protected $controller;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'search', 'comment', 'taxonomy');

  public static function getInfo() {
    return array(
      'name' => 'Configuration entity CRUD tests',
      'description' => 'Tests the CRUD functionality for View.',
      'group' => 'Views',
    );
  }

  /**
   * Tests CRUD operations.
   */
  function testConfigurationEntityCRUD() {
    // Get the configuration entity information and controller.
    $this->info = entity_get_info('view');
    $this->controller = entity_get_controller('view');

    // Confirm that an info array has been returned.
    $this->assertTrue(!empty($this->info) && is_array($this->info), 'The View info array is loaded.');

    // Confirm we have the correct controller class.
    $this->assertTrue($this->controller instanceof ViewStorageController, 'The correct controller is loaded.');

    // CRUD tests.
    $this->loadTests();
    $this->createTests();
    $this->displayTests();
    $this->statusTests();

    // Helper method tests
    $this->displayMethodTests();
  }

  /**
   * Tests loading configuration entities.
   */
  protected function loadTests() {
    $view = $this->loadView('archive');
    $data = config('views.view.archive')->get();

    // Confirm that an actual view object is loaded and that it returns all of
    // expected properties.
    $this->assertTrue($view instanceof View, 'Single View instance loaded.');
    foreach ($this->config_properties as $property) {
      $this->assertTrue(isset($view->{$property}), format_string('Property: @property loaded onto View.', array('@property' => $property)));
    }

    // Check the displays have been loaded correctly from config display data.
    $expected_displays = array('default', 'page_1', 'block_1');
    $this->assertEqual(array_keys($view->display), $expected_displays, 'The correct display names are present.');

    // Check each ViewDisplay object and confirm that it has the correct key and
    // property values.
    foreach ($view->display as $key => $display) {
      $this->assertEqual($key, $display['id'], 'The display has the correct ID assigned.');

      // Get original display data and confirm that the display options array
      // exists.
      $original_options = $data['display'][$key];
      foreach ($original_options as $orig_key => $value) {
        $this->assertIdentical($display[$orig_key], $value, format_string('@key is identical to saved data', array('@key' => $key)));
      }
    }

    // Fetch data for all configuration entities and default view configurations.
    $all_configuration_entities = $this->controller->load();
    $all_config = config_get_storage_names_with_prefix('views.view');

    // Remove the 'views.view.' prefix from config names for comparision with
    // loaded configuration entities.
    $prefix_map = function ($value) {
      $parts = explode('.', $value);
      return end($parts);
    };

    // Check that the correct number of configuration entities have been loaded.
    $count = count($all_configuration_entities);
    $this->assertEqual($count, count($all_config), format_string('The array of all @count configuration entities is loaded.', array('@count' => $count)));

    // Check that all of these machine names match.
    $this->assertIdentical(array_keys($all_configuration_entities), array_map($prefix_map, $all_config), 'All loaded elements match.');

    // Make sure that loaded default views get a UUID.
    $view = views_get_view('frontpage');
    $this->assertTrue($view->storage->uuid());
  }

  /**
   * Tests creating configuration entities.
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

    // Check the UUID of the loaded View.
    $created->set('name', 'glossary_new');
    $created->save();
    $created_loaded = $this->loadView('glossary_new');
    $this->assertIdentical($created->uuid(), $created_loaded->uuid(), 'The created UUID has been saved correctly.');
  }

  /**
   * Tests adding, saving, and loading displays on configuration entities.
   */
  protected function displayTests() {
    // Check whether a display can be added and saved to a View.
    $view = $this->loadView('frontpage');

    $view->newDisplay('page', 'Test', 'test');

    $new_display = $view->display['test'];

    // Ensure the right display_plugin is created/instantiated.
    $this->assertEqual($new_display['display_plugin'], 'page', 'New page display "test" uses the right display plugin.');
    $this->assertTrue($view->getExecutable()->displayHandlers[$new_display['id']] instanceof Page, 'New page display "test" uses the right display plugin.');


    $view->set('name', 'frontpage_new');
    $view->save();
    $values = config('views.view.frontpage_new')->get();

    $this->assertTrue(isset($values['display']['test']) && is_array($values['display']['test']), 'New display was saved.');
  }

  /**
   * Tests statuses of configuration entities.
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
   * Loads a single configuration entity from the controller.
   *
   * @param string $view_name
   *   The machine name of the view.
   *
   * @return object Drupal\views\ViewExecutable.
   *   The loaded view object.
   */
  protected function loadView($view_name) {
    $load = $this->controller->load(array($view_name));
    return reset($load);
  }

  /**
   * Tests the display related functions like getDisplaysList().
   */
  protected function displayMethodTests() {
    $config['display'] = array(
      'page_1' => array(
        'display_options' => array('path' => 'test'),
        'display_plugin' => 'page',
        'id' => 'page_2',
        'display_title' => 'Page 1',
        'position' => 1
      ),
      'feed_1' => array(
        'display_options' => array('path' => 'test.xml'),
        'display_plugin' => 'feed',
        'id' => 'feed',
        'display_title' => 'Feed',
        'position' => 2
      ),
      'page_2' => array(
        'display_options' => array('path' => 'test/%/extra'),
        'display_plugin' => 'page',
        'id' => 'page_2',
        'display_title' => 'Page 2',
        'position' => 3
      )
    );
    $view = $this->controller->create($config);

    $this->assertEqual($view->getDisplaysList(), array('Feed', 'Page'), 'Make sure the display admin names are returns in alphabetic order.');

    // Paths with a "%" shouldn't not be linked
    $expected_paths = array();
    $expected_paths[] = l('/test', 'test');
    $expected_paths[] = l('/test.xml', 'test.xml');
    $expected_paths[] = '/test/%/extra';

    $this->assertEqual($view->getPaths(), $expected_paths, 'Make sure the paths in the ui are generated as expected.');

    // Tests Drupal\views\Plugin\Core\Entity\View::addDisplay()
    $view = $this->controller->create(array());
    $random_title = $this->randomName();

    $id = $view->addDisplay('page', $random_title);
    $this->assertEqual($id, 'page_1', format_string('Make sure the first display (%id_new) has the expected ID (%id)', array('%id_new' => $id, '%id' => 'page_1')));
    $this->assertEqual($view->display[$id]['display_title'], $random_title);

    $random_title = $this->randomName();
    $id = $view->addDisplay('page', $random_title);
    $this->assertEqual($id, 'page_2', format_string('Make sure the second display (%id_new) has the expected ID (%id)', array('%id_new' => $id, '%id' => 'page_2')));
    $this->assertEqual($view->display[$id]['display_title'], $random_title);

    $id = $view->addDisplay('page');
    $this->assertEqual($view->display[$id]['display_title'], 'Page 3');

    // Tests Drupal\views\Plugin\Core\Entity\View::generateDisplayId().
    // @todo Sadly this method is not public so it cannot be tested.
    // $view = $this->controller->create(array());
    // $this->assertEqual($view->generateDisplayId('default'), 'default', 'The plugin ID for default is always default.');
    // $this->assertEqual($view->generateDisplayId('feed'), 'feed_1', 'The generated ID for the first instance of a plugin type should have an suffix of _1.');
    // $view->addDisplay('feed', 'feed title');
    // $this->assertEqual($view->generateDisplayId('feed'), 'feed_2', 'The generated ID for the first instance of a plugin type should have an suffix of _2.');

    // Tests Drupal\views\Plugin\Core\Entity\View::newDisplay().
    $view = $this->controller->create(array());
    $view->newDisplay('default');

    $display = $view->newDisplay('page');
    $this->assertTrue($display instanceof Page);
    $this->assertTrue($view->getExecutable()->displayHandlers['page_1'] instanceof Page);
    $this->assertTrue($view->getExecutable()->displayHandlers['page_1']->default_display instanceof DefaultDisplay);

    $display = $view->newDisplay('page');
    $this->assertTrue($display instanceof Page);
    $this->assertTrue($view->getExecutable()->displayHandlers['page_2'] instanceof Page);
    $this->assertTrue($view->getExecutable()->displayHandlers['page_2']->default_display instanceof DefaultDisplay);

    $display = $view->newDisplay('feed');
    $this->assertTrue($display instanceof Feed);
    $this->assertTrue($view->getExecutable()->displayHandlers['feed_1'] instanceof Feed);
    $this->assertTrue($view->getExecutable()->displayHandlers['feed_1']->default_display instanceof DefaultDisplay);

    // Tests item related methods().
    $view = $this->controller->create(array('base_table' => 'views_test_data'));
    $view->addDisplay('default');
    $view = $view->getExecutable();

    $display_id = 'default';
    $expected_items = array();
    // Tests addItem with getItem.
    // Therefore add one item without any optioins and one item with some
    // options.
    $id1 = $view->addItem($display_id, 'field', 'views_test_data', 'id');
    $item1 = $view->getItem($display_id, 'field', 'id');
    $expected_items[$id1] = $expected_item = array(
      'id' => 'id',
      'table' => 'views_test_data',
      'field' => 'id'
    );
    $this->assertEqual($item1, $expected_item);

    $options = array(
      'alter' => array(
        'text' => $this->randomName()
      )
    );
    $id2 = $view->addItem($display_id, 'field', 'views_test_data', 'name', $options);
    $item2 = $view->getItem($display_id, 'field', 'name');
    $expected_items[$id2] = $expected_item = array(
      'id' => 'name',
      'table' => 'views_test_data',
      'field' => 'name'
    ) + $options;
    $this->assertEqual($item2, $expected_item);

    // Tests the expected fields from the previous additions.
    $this->assertEqual($view->getItems('field', $display_id), $expected_items);

    // Alter an existing item via setItem and check the result via getItem
    // and getItems.
    $item = array(
      'alter' => array(
        'text' => $this->randomName(),
      )
    ) + $item1;
    $expected_items[$id1] = $item;
    $view->setItem($display_id, 'field', $id1, $item);
    $this->assertEqual($view->getItem($display_id, 'field', 'id'), $item);
    $this->assertEqual($view->getItems('field', $display_id), $expected_items);
  }

  /**
   * Tests the createDuplicate() View method.
   */
  public function testCreateDuplicate() {
    $view = views_get_view('archive');
    $copy = $view->createDuplicate();

    $this->assertTrue($copy instanceof View, 'The copied object is a View.');

    // Check that the original view and the copy have different UUIDs.
    $this->assertNotIdentical($view->storage->uuid(), $copy->uuid(), 'The copied view has a new UUID.');

    // Check the 'name' (ID) is using the View objects default value ('') as it
    // gets unset.
    $this->assertIdentical($copy->id(), '', 'The ID has been reset.');

    // Check the other properties.
    // @todo Create a reusable property on the base test class for these?
    $config_properties = array(
      'disabled',
      'api_version',
      'description',
      'tag',
      'base_table',
      'human_name',
      'core',
    );

    foreach ($config_properties as $property) {
      $this->assertIdentical($view->storage->{$property}, $copy->{$property}, format_string('@property property is identical.', array('@property' => $property)));
    }

    // Check the displays are the same.
    foreach ($view->storage->get('display') as $id => $display) {
      // assertIdentical will not work here.
      $this->assertEqual($display, $copy->display[$id], format_string('The @display display has been copied correctly.', array('@display' => $id)));
    }
  }

}
