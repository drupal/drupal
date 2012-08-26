<?php

/**
 * Definition of Drupal\views\Tests\ViewsStorageTest.
 */

namespace Drupal\views\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\views\ViewStorageController;
use Drupal\views\View;
use Drupal\views\ViewsDisplay;

class ViewsStorageTest extends WebTestBase {

  protected $config_properties = array (
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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views');

  public static function getInfo() {
    return array(
      'name' => 'Views configuration entity CRUD tests',
      'description' => 'Test the CRUD functionality for ViewStorage.',
      'group' => 'Views',
    );
  }

  /**
   * Tests CRUD operations.
   */
  function testConfigEntityCRUD() {
    // Get the entity info.
    $info = entity_get_info('view');
    // Get the entity controller.
    $controller = entity_get_controller('view');

    // Test an info array has been returned.
    $this->assertTrue(!empty($info) && is_array($info), 'View entity info array loaded.');

    // Test we have the correct controller class.
    $this->assertTrue($controller instanceof ViewStorageController, 'Correct entity controller loaded.');

    //Load a single config entity.
    $load = $controller->load(array('archive'));
    $view = reset($load);

    // Check it's a view.
    $this->assertTrue($view instanceof View, 'Single View instance loaded.');

    // Check that the View contains all of the properties.
    foreach ($this->config_properties as $property) {
      $this->assertTrue(isset($view->{$property}), t('Property: @property loaded onto View.', array('@property' => $property)));
    }

    // Check the displays have been loaded correctly from config display data.
    $expected_displays = array('default', 'page', 'block');
    // Check display keys.
    $this->assertEqual(array_keys($view->display), $expected_displays, 'Correct display names present.');

    foreach ($view->display as $key => $display) {
      $this->assertTrue($display instanceof ViewsDisplay, t('Display: @display is instance of ViewsDisplay.', array('@display' => $key)));
      $this->assertEqual($key, $display->id, 'The display has the correct ID.');
      $display_options = $display->display_options;
      $this->assertTrue(!empty($display_options) && is_array($display_options), 'Display options exist.');
    }

    // Load all config entities.
    $all_entities = $controller->load();
    $all_config = config_get_storage_names_with_prefix('views.view');

    $prefix_map = function ($value) {
      $parts = explode('.', $value);
      return end($parts);
    };

    // Check correct number of entities have been loaded.
    $count = count($all_entities);
    $this->assertEqual($count, count($all_config), t('Array of all @count entities loaded.', array('@count' => $count)));
    $this->assertIdentical(array_keys($all_entities), array_map($prefix_map, $all_config), 'All loaded elements match.');
  }

}
