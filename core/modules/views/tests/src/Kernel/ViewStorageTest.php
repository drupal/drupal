<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\views\display\Page;
use Drupal\views\Views;

/**
 * Tests the CRUD functionality for a view.
 *
 * @group views
 * @see \Drupal\views\Entity\View
 * @see \Drupal\Core\Config\Entity\ConfigEntityStorage
 */
class ViewStorageTest extends ViewsKernelTestBase {

  /**
   * Properties that should be stored in the configuration.
   *
   * @var array
   */
  protected $configProperties = [
    'status',
    'module',
    'id',
    'description',
    'tag',
    'base_table',
    'label',
    'display',
  ];

  /**
   * The entity type definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The configuration entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $controller;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view_storage'];

  /**
   * Tests CRUD operations.
   */
  public function testConfigurationEntityCRUD(): void {
    // Get the configuration entity type and controller.
    $this->entityType = \Drupal::entityTypeManager()->getDefinition('view');
    $this->controller = $this->container->get('entity_type.manager')->getStorage('view');

    // Confirm that an info array has been returned.
    $this->assertInstanceOf(EntityTypeInterface::class, $this->entityType);

    // CRUD tests.
    $this->loadTests();
    $this->createTests();
    $this->displayTests();

    // Helper method tests
    $this->displayMethodTests();
  }

  /**
   * Tests loading configuration entities.
   */
  protected function loadTests(): void {
    $view = View::load('test_view_storage');
    $data = $this->config('views.view.test_view_storage')->get();

    // Confirm that an actual view object is loaded and that it returns all of
    // expected properties.
    $this->assertInstanceOf(View::class, $view);
    foreach ($this->configProperties as $property) {
      $this->assertNotNull($view->get($property), "Property: $property loaded onto View.");
    }

    // Check the displays have been loaded correctly from config display data.
    $expected_displays = ['default', 'block_1', 'page_1'];
    $this->assertEquals($expected_displays, array_keys($view->get('display')), 'The correct display names are present.');

    // Check each ViewDisplay object and confirm that it has the correct key and
    // property values.
    foreach ($view->get('display') as $key => $display) {
      $this->assertEquals($key, $display['id'], 'The display has the correct ID assigned.');

      // Get original display data and confirm that the display options array
      // exists.
      $original_options = $data['display'][$key];
      foreach ($original_options as $orig_key => $value) {
        $this->assertSame($display[$orig_key], $value, "$key is identical to saved data");
      }
    }

    // Make sure that loaded default views get a UUID.
    $view = Views::getView('test_view_storage');
    $this->assertNotEmpty($view->storage->uuid());
  }

  /**
   * Tests creating configuration entities.
   */
  protected function createTests(): void {
    // Create a new View instance with empty values.
    $created = $this->controller->create([]);

    $this->assertInstanceOf(View::class, $created);
    // Check that the View contains all of the properties.
    foreach ($this->configProperties as $property) {
      $this->assertTrue(property_exists($created, $property), "Property: $property created on View.");
    }

    // Create a new View instance with config values.
    $values = $this->config('views.view.test_view_storage')->get();
    $values['id'] = 'test_view_storage_new';
    unset($values['uuid']);
    $created = $this->controller->create($values);

    $this->assertInstanceOf(View::class, $created);
    // Check that the View contains all of the properties.
    $properties = $this->configProperties;
    // Remove display from list.
    array_pop($properties);

    // Test all properties except displays.
    foreach ($properties as $property) {
      $this->assertNotNull($created->get($property), "Property: $property created on View.");
      $this->assertSame($values[$property], $created->get($property), "Property value: $property matches configuration value.");
    }

    // Check the UUID of the loaded View.
    $created->save();
    $created_loaded = View::load('test_view_storage_new');
    $this->assertSame($created->uuid(), $created_loaded->uuid(), 'The created UUID has been saved correctly.');
  }

  /**
   * Tests adding, saving, and loading displays on configuration entities.
   */
  protected function displayTests(): void {
    // Check whether a display can be added and saved to a View.
    $view = View::load('test_view_storage_new');

    $new_id = $view->addDisplay('page', 'Test', 'test');
    $display = $view->get('display');

    // Ensure the right display_plugin is created/instantiated.
    $this->assertEquals('page', $display[$new_id]['display_plugin'], 'New page display "test" uses the right display plugin.');

    $executable = $view->getExecutable();
    $executable->initDisplay();
    $this->assertInstanceOf(Page::class, $executable->displayHandlers->get($new_id));

    // To save this with a new ID, we should use createDuplicate().
    $view = $view->createDuplicate();
    $view->set('id', 'test_view_storage_new_new2');
    $view->save();
    $values = $this->config('views.view.test_view_storage_new_new2')->get();

    // Verify that the display was saved by ensuring it contains an array of
    // values in the view data.
    $this->assertIsArray($values['display']['test']);
  }

  /**
   * Tests the display related functions like getDisplaysList().
   */
  protected function displayMethodTests(): void {
    $config['display'] = [
      'page_1' => [
        'display_options' => ['path' => 'test'],
        'display_plugin' => 'page',
        'id' => 'page_2',
        'display_title' => 'Page 1',
        'position' => 1,
      ],
      'feed_1' => [
        'display_options' => ['path' => 'test.xml'],
        'display_plugin' => 'feed',
        'id' => 'feed',
        'display_title' => 'Feed',
        'position' => 2,
      ],
      'page_2' => [
        'display_options' => ['path' => 'test/%/extra'],
        'display_plugin' => 'page',
        'id' => 'page_2',
        'display_title' => 'Page 2',
        'position' => 3,
      ],
    ];
    $view = $this->controller->create($config);

    // Tests Drupal\views\Entity\View::addDisplay()
    $view = $this->controller->create([]);
    $random_title = $this->randomMachineName();

    $id = $view->addDisplay('page', $random_title);
    $this->assertEquals('page_1', $id, "Make sure the first display ($id) has the expected ID (page_1)");
    $display = $view->get('display');
    $this->assertEquals($random_title, $display[$id]['display_title']);

    $random_title = $this->randomMachineName();
    $id = $view->addDisplay('page', $random_title);
    $display = $view->get('display');
    $this->assertEquals('page_2', $id, "Make sure the second display ($id) has the expected ID (page_2)");
    $this->assertEquals($random_title, $display[$id]['display_title']);

    $id = $view->addDisplay('page');
    $display = $view->get('display');
    $this->assertEquals('Page 3', $display[$id]['display_title']);

    // Ensure the 'default' display always has position zero, regardless of when
    // it was set relative to other displays. Even if the 'default' display
    // exists, adding it again will overwrite it, which is asserted with the new
    // title.
    $view->addDisplay('default', $random_title);
    $displays = $view->get('display');
    $this->assertEquals($random_title, $displays['default']['display_title'], 'Default display is defined with the new title');
    $this->assertEquals(0, $displays['default']['position'], 'Default displays are always in position zero');

    // Tests Drupal\views\Entity\View::generateDisplayId(). Since
    // generateDisplayId() is protected, we have to use reflection to unit-test
    // it.
    $view = $this->controller->create([]);
    $ref_generate_display_id = new \ReflectionMethod($view, 'generateDisplayId');
    $this->assertEquals('default', $ref_generate_display_id->invoke($view, 'default'), 'The plugin ID for default is always default.');
    $this->assertEquals('feed_1', $ref_generate_display_id->invoke($view, 'feed'), 'The generated ID for the first instance of a plugin type should have an suffix of _1.');
    $view->addDisplay('feed', 'feed title');
    $this->assertEquals('feed_2', $ref_generate_display_id->invoke($view, 'feed'), 'The generated ID for the first instance of a plugin type should have an suffix of _2.');

    // Tests item related methods().
    $view = $this->controller->create(['base_table' => 'views_test_data']);
    $view->addDisplay('default');
    $view = $view->getExecutable();

    $display_id = 'default';
    $expected_items = [];
    // Tests addHandler with getItem.
    // Therefore add one item without any options and one item with some
    // options.
    $id1 = $view->addHandler($display_id, 'field', 'views_test_data', 'id');
    $item1 = $view->getHandler($display_id, 'field', 'id');
    $expected_items[$id1] = $expected_item = [
      'id' => 'id',
      'table' => 'views_test_data',
      'field' => 'id',
      'plugin_id' => 'numeric',
    ];
    $this->assertEquals($expected_item, $item1);

    $options = [
      'alter' => [
        'text' => $this->randomMachineName(),
      ],
    ];
    $id2 = $view->addHandler($display_id, 'field', 'views_test_data', 'name', $options);
    $item2 = $view->getHandler($display_id, 'field', 'name');
    $expected_items[$id2] = $expected_item = [
      'id' => 'name',
      'table' => 'views_test_data',
      'field' => 'name',
      'plugin_id' => 'standard',
    ] + $options;
    $this->assertEquals($expected_item, $item2);

    // Tests the expected fields from the previous additions.
    $this->assertEquals($expected_items, $view->getHandlers('field', $display_id));

    // Alter an existing item via setItem and check the result via getItem
    // and getItems.
    $item = [
      'alter' => [
        'text' => $this->randomMachineName(),
      ],
    ] + $item1;
    $expected_items[$id1] = $item;
    $view->setHandler($display_id, 'field', $id1, $item);
    $this->assertEquals($item, $view->getHandler($display_id, 'field', 'id'));
    $this->assertEquals($expected_items, $view->getHandlers('field', $display_id));

    // Test removeItem method.
    unset($expected_items[$id2]);
    $view->removeHandler($display_id, 'field', $id2);
    $this->assertEquals($expected_items, $view->getHandlers('field', $display_id));
  }

  /**
   * Tests the createDuplicate() View method.
   */
  public function testCreateDuplicate(): void {
    $view = Views::getView('test_view_storage');
    $copy = $view->storage->createDuplicate();

    $this->assertInstanceOf(View::class, $copy);

    // Check that the original view and the copy have different UUIDs.
    $this->assertNotSame($view->storage->uuid(), $copy->uuid(), 'The copied view has a new UUID.');

    // Check the 'name' (ID) is using the View objects default value (NULL) as
    // it gets unset.
    $this->assertNull($copy->id(), 'The ID has been reset.');

    // Check the other properties.
    // @todo Create a reusable property on the base test class for these?
    $config_properties = [
      'disabled',
      'description',
      'tag',
      'base_table',
      'label',
    ];

    foreach ($config_properties as $property) {
      $this->assertSame($view->storage->get($property), $copy->get($property), "$property property is identical.");
    }

    // Check the displays are the same.
    $copy_display = $copy->get('display');
    foreach ($view->storage->get('display') as $id => $display) {
      // assertIdentical will not work here.
      $this->assertEquals($copy_display[$id], $display, "The $id display has been copied correctly.");
    }
  }

}
