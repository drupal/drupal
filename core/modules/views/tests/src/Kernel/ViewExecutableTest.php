<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Utility\Xss;
use Drupal\node\Entity\NodeType;
use Drupal\views\Entity\View;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewExecutableFactory;
use Drupal\views\DisplayPluginCollection;
use Drupal\views\Plugin\views\display\DefaultDisplay;
use Drupal\views\Plugin\views\display\Page;
use Drupal\views\Plugin\views\style\DefaultStyle;
use Drupal\views\Plugin\views\style\Grid;
use Drupal\views\Plugin\views\row\Fields;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\Plugin\views\pager\PagerPluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views_test_data\Plugin\views\display\DisplayTest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the ViewExecutable class.
 *
 * @group views
 * @see \Drupal\views\ViewExecutable
 */
class ViewExecutableTest extends ViewsKernelTestBase {

  use CommentTestTrait;

  public static $modules = ['system', 'node', 'comment', 'user', 'filter', 'field', 'text'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_destroy', 'test_executable_displays');

  /**
   * Properties that should be stored in the configuration.
   *
   * @var array
   */
  protected $configProperties = array(
    'disabled',
    'name',
    'description',
    'tag',
    'base_table',
    'label',
    'core',
    'display',
  );

  /**
   * Properties that should be stored in the executable.
   *
   * @var array
   */
  protected $executableProperties = array(
    'storage',
    'built',
    'executed',
    'args',
    'build_info',
    'result',
    'attachment_before',
    'attachment_after',
    'exposed_data',
    'exposed_raw_input',
    'old_view',
    'parent_views',
  );

  protected function setUpFixtures() {
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', array('comment_entity_statistics'));
    $this->installConfig(array('system', 'field', 'node', 'comment'));

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();
    $this->addDefaultCommentField('node', 'page');
    parent::setUpFixtures();

    $this->installConfig(array('filter'));
  }

  /**
   * Tests the views.executable container service.
   */
  public function testFactoryService() {
    $factory = $this->container->get('views.executable');
    $this->assertTrue($factory instanceof ViewExecutableFactory, 'A ViewExecutableFactory instance was returned from the container.');
    $view = entity_load('view', 'test_executable_displays');
    $this->assertTrue($factory->get($view) instanceof ViewExecutable, 'A ViewExecutable instance was returned from the factory.');
  }

  /**
   * Tests the initDisplay() and initHandlers() methods.
   */
  public function testInitMethods() {
    $view = Views::getView('test_destroy');
    $view->initDisplay();

    $this->assertTrue($view->display_handler instanceof DefaultDisplay, 'Make sure a reference to the current display handler is set.');
    $this->assertTrue($view->displayHandlers->get('default') instanceof DefaultDisplay, 'Make sure a display handler is created for each display.');

    $view->destroy();
    $view->initHandlers();

    // Check for all handler types.
    $handler_types = array_keys(ViewExecutable::getHandlerTypes());
    foreach ($handler_types as $type) {
      // The views_test integration doesn't have relationships.
      if ($type == 'relationship') {
        continue;
      }
      $this->assertTrue(count($view->$type), format_string('Make sure a %type instance got instantiated.', array('%type' => $type)));
    }

    // initHandlers() should create display handlers automatically as well.
    $this->assertTrue($view->display_handler instanceof DefaultDisplay, 'Make sure a reference to the current display handler is set.');
    $this->assertTrue($view->displayHandlers->get('default') instanceof DefaultDisplay, 'Make sure a display handler is created for each display.');

    $view_hash = spl_object_hash($view);
    $display_hash = spl_object_hash($view->display_handler);

    // Test the initStyle() method.
    $view->initStyle();
    $this->assertTrue($view->style_plugin instanceof DefaultStyle, 'Make sure a reference to the style plugin is set.');
    // Test the plugin has been inited and view have references to the view and
    // display handler.
    $this->assertEqual(spl_object_hash($view->style_plugin->view), $view_hash);
    $this->assertEqual(spl_object_hash($view->style_plugin->displayHandler), $display_hash);

    // Test the initQuery method().
    $view->initQuery();
    $this->assertTrue($view->query instanceof Sql, 'Make sure a reference to the query is set');
    $this->assertEqual(spl_object_hash($view->query->view), $view_hash);
    $this->assertEqual(spl_object_hash($view->query->displayHandler), $display_hash);

    $view->destroy();

    // Test the plugin  get methods.
    $display_plugin = $view->getDisplay();
    $this->assertTrue($display_plugin instanceof DefaultDisplay, 'An instance of DefaultDisplay was returned.');
    $this->assertTrue($view->display_handler instanceof DefaultDisplay, 'The display_handler property has been set.');
    $this->assertIdentical($display_plugin, $view->getDisplay(), 'The same display plugin instance was returned.');

    $style_plugin = $view->getStyle();
    $this->assertTrue($style_plugin instanceof DefaultStyle, 'An instance of DefaultStyle was returned.');
    $this->assertTrue($view->style_plugin instanceof DefaultStyle, 'The style_plugin property has been set.');
    $this->assertIdentical($style_plugin, $view->getStyle(), 'The same style plugin instance was returned.');

    $pager_plugin = $view->getPager();
    $this->assertTrue($pager_plugin instanceof PagerPluginBase, 'An instance of PagerPluginBase was returned.');
    $this->assertTrue($view->pager instanceof PagerPluginBase, 'The pager property has been set.');
    $this->assertIdentical($pager_plugin, $view->getPager(), 'The same pager plugin instance was returned.');

    $query_plugin = $view->getQuery();
    $this->assertTrue($query_plugin instanceof QueryPluginBase, 'An instance of QueryPluginBase was returned.');
    $this->assertTrue($view->query instanceof QueryPluginBase, 'The query property has been set.');
    $this->assertIdentical($query_plugin, $view->getQuery(), 'The same query plugin instance was returned.');
  }

  /**
   * Tests the generation of the executable object.
   */
  public function testConstructing() {
    Views::getView('test_destroy');
  }

  /**
   * Tests the accessing of values on the object.
   */
  public function testProperties() {
    $view = Views::getView('test_destroy');
    foreach ($this->executableProperties as $property) {
      $this->assertTrue(isset($view->{$property}));
    }

    // Per default exposed input should fall back to an empty array.
    $this->assertEqual($view->getExposedInput(), []);
  }

  public function testSetDisplayWithInvalidDisplay() {
    $view = Views::getView('test_executable_displays');
    $view->initDisplay();

    // Error is triggered while calling the wrong display.
    $this->setExpectedException(\PHPUnit_Framework_Error::class);
    $view->setDisplay('invalid');

    $this->assertEqual($view->current_display, 'default', 'If setDisplay is called with an invalid display id the default display should be used.');
    $this->assertEqual(spl_object_hash($view->display_handler), spl_object_hash($view->displayHandlers->get('default')));
  }

  /**
   * Tests the display related methods and properties.
   */
  public function testDisplays() {
    $view = Views::getView('test_executable_displays');

    // Tests Drupal\views\ViewExecutable::initDisplay().
    $view->initDisplay();
    $this->assertTrue($view->displayHandlers instanceof DisplayPluginCollection, 'The displayHandlers property has the right class.');
    // Tests the classes of the instances.
    $this->assertTrue($view->displayHandlers->get('default') instanceof DefaultDisplay);
    $this->assertTrue($view->displayHandlers->get('page_1') instanceof Page);
    $this->assertTrue($view->displayHandlers->get('page_2') instanceof Page);

    // After initializing the default display is the current used display.
    $this->assertEqual($view->current_display, 'default');
    $this->assertEqual(spl_object_hash($view->display_handler), spl_object_hash($view->displayHandlers->get('default')));

    // All handlers should have a reference to the default display.
    $this->assertEqual(spl_object_hash($view->displayHandlers->get('page_1')->default_display), spl_object_hash($view->displayHandlers->get('default')));
    $this->assertEqual(spl_object_hash($view->displayHandlers->get('page_2')->default_display), spl_object_hash($view->displayHandlers->get('default')));

    // Tests Drupal\views\ViewExecutable::setDisplay().
    $view->setDisplay();
    $this->assertEqual($view->current_display, 'default', 'If setDisplay is called with no parameter the default display should be used.');
    $this->assertEqual(spl_object_hash($view->display_handler), spl_object_hash($view->displayHandlers->get('default')));

    // Set two different valid displays.
    $view->setDisplay('page_1');
    $this->assertEqual($view->current_display, 'page_1', 'If setDisplay is called with a valid display id the appropriate display should be used.');
    $this->assertEqual(spl_object_hash($view->display_handler), spl_object_hash($view->displayHandlers->get('page_1')));

    $view->setDisplay('page_2');
    $this->assertEqual($view->current_display, 'page_2', 'If setDisplay is called with a valid display id the appropriate display should be used.');
    $this->assertEqual(spl_object_hash($view->display_handler), spl_object_hash($view->displayHandlers->get('page_2')));

    // Destroy the view, so we can start again and test an invalid display.
    $view->destroy();

    // Test the style and row plugins are replaced correctly when setting the
    // display.
    $view->setDisplay('page_1');
    $view->initStyle();
    $this->assertTrue($view->style_plugin instanceof DefaultStyle);
    $this->assertTrue($view->rowPlugin instanceof Fields);

    $view->setDisplay('page_2');
    $view->initStyle();
    $this->assertTrue($view->style_plugin instanceof Grid);
    // @todo Change this rowPlugin type too.
    $this->assertTrue($view->rowPlugin instanceof Fields);

    // Test the newDisplay() method.
    $view = $this->container->get('entity.manager')->getStorage('view')->create(array('id' => 'test_executable_displays'));
    $executable = $view->getExecutable();

    $executable->newDisplay('page');
    $executable->newDisplay('page');
    $executable->newDisplay('display_test');

    $this->assertTrue($executable->displayHandlers->get('default') instanceof DefaultDisplay);
    $this->assertFalse(isset($executable->displayHandlers->get('default')->default_display));
    $this->assertTrue($executable->displayHandlers->get('page_1') instanceof Page);
    $this->assertTrue($executable->displayHandlers->get('page_1')->default_display instanceof DefaultDisplay);
    $this->assertTrue($executable->displayHandlers->get('page_2') instanceof Page);
    $this->assertTrue($executable->displayHandlers->get('page_2')->default_display instanceof DefaultDisplay);
    $this->assertTrue($executable->displayHandlers->get('display_test_1') instanceof DisplayTest);
    $this->assertTrue($executable->displayHandlers->get('display_test_1')->default_display instanceof DefaultDisplay);
  }

  /**
   * Tests the setting/getting of properties.
   */
  public function testPropertyMethods() {
    $view = Views::getView('test_executable_displays');

    // Test the setAjaxEnabled() method.
    $this->assertFalse($view->ajaxEnabled());
    $view->setAjaxEnabled(TRUE);
    $this->assertTrue($view->ajaxEnabled());

    $view->setDisplay();
    // There should be no pager set initially.
    $this->assertNull($view->usePager());

    // Add a pager, initialize, and test.
    $view->displayHandlers->get('default')->overrideOption('pager', array(
      'type' => 'full',
      'options' => array('items_per_page' => 10),
    ));
    $view->initPager();
    $this->assertTrue($view->usePager());

    // Test setting and getting the offset.
    $rand = rand();
    $view->setOffset($rand);
    $this->assertEqual($view->getOffset(), $rand);

    // Test the getBaseTable() method.
    $expected = array(
      'views_test_data' => TRUE,
      '#global' => TRUE,
    );
    $this->assertIdentical($view->getBaseTables(), $expected);

    // Test response methods.
    $this->assertTrue($view->getResponse() instanceof Response, 'New response object returned.');
    $new_response = new Response();
    $view->setResponse($new_response);
    $this->assertIdentical(spl_object_hash($view->getResponse()), spl_object_hash($new_response), 'New response object correctly set.');

    // Test the getPath() method.
    $path = $this->randomMachineName();
    $view->displayHandlers->get('page_1')->overrideOption('path', $path);
    $view->setDisplay('page_1');
    $this->assertEqual($view->getPath(), $path);
    // Test the override_path property override.
    $override_path = $this->randomMachineName();
    $view->override_path = $override_path;
    $this->assertEqual($view->getPath(), $override_path);

    // Test the title methods.
    $title = $this->randomString();
    $view->setTitle($title);
    $this->assertEqual($view->getTitle(), Xss::filterAdmin($title));
  }

  /**
   * Tests the deconstructor to be sure that necessary objects are removed.
   */
  public function testDestroy() {
    $view = Views::getView('test_destroy');

    $view->preview();
    $view->destroy();

    $this->assertViewDestroy($view);
  }

  /**
   * Asserts that expected view properties have been unset by destroy().
   *
   * @param \Drupal\views\ViewExecutable $view
   */
  protected function assertViewDestroy($view) {
    $reflection = new \ReflectionClass($view);
    $defaults = $reflection->getDefaultProperties();
    // The storage and user should remain.
    unset($defaults['storage'], $defaults['user'], $defaults['request'], $defaults['routeProvider']);

    foreach ($defaults as $property => $default) {
      $this->assertIdentical($this->getProtectedProperty($view, $property), $default);
    }
  }

  /**
   * Returns a protected property from a class instance.
   *
   * @param object $instance
   *   The class instance to return the property from.
   * @param string $property
   *   The name of the property to return.
   *
   * @return mixed
   *   The instance property value.
   */
  protected function getProtectedProperty($instance, $property) {
    $reflection = new \ReflectionProperty($instance, $property);
    $reflection->setAccessible(TRUE);
    return $reflection->getValue($instance);
  }

  /**
   * Tests ViewExecutable::getHandlerTypes().
   */
  public function testGetHandlerTypes() {
    $types = ViewExecutable::getHandlerTypes();
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

  /**
   * Tests ViewExecutable::getHandlers().
   */
  public function testGetHandlers() {
    $view = Views::getView('test_executable_displays');
    $view->setDisplay('page_1');

    $view->getHandlers('field', 'page_2');

    // getHandlers() shouldn't change the active display.
    $this->assertEqual('page_1', $view->current_display, "The display shouldn't change after getHandlers()");
  }

  /**
   * Tests the validation of display handlers.
   */
  public function testValidate() {
    $view = Views::getView('test_executable_displays');
    $view->setDisplay('page_1');

    $validate = $view->validate();

    // Validating a view shouldn't change the active display.
    $this->assertEqual('page_1', $view->current_display, "The display should be constant while validating");

    $count = 0;
    foreach ($view->displayHandlers as $id => $display) {
      $match = function($value) use ($display) {
        return strpos($value, $display->display['display_title']) !== false;
      };
      $this->assertTrue(array_filter($validate[$id], $match), format_string('Error message found for @id display', array('@id' => $id)));
      $count++;
    }

    $this->assertEqual(count($view->displayHandlers), $count, 'Error messages from all handlers merged.');

    // Test that a deleted display is not included.
    $display = &$view->storage->getDisplay('default');
    $display['deleted'] = TRUE;
    $validate_deleted = $view->validate();

    $this->assertNotIdentical($validate, $validate_deleted, 'Master display has not been validated.');
  }

  /**
   * Tests that nested loops of the display handlers won't break validation.
   */
  public function testValidateNestedLoops() {
    $view = View::create(['id' => 'test_validate_nested_loops']);
    $executable = $view->getExecutable();

    $executable->newDisplay('display_test');
    $executable->newDisplay('display_test');
    $errors = $executable->validate();
    $total_error_count = array_reduce($errors, function ($carry, $item) {
      $carry += count($item);

      return $carry;
    });
    // Assert that there were 9 total errors across 3 displays.
    $this->assertIdentical(9, $total_error_count);
    $this->assertIdentical(3, count($errors));
  }

  /**
   * Tests serialization of the ViewExecutable object.
   */
  public function testSerialization() {
    $view = Views::getView('test_executable_displays');
    $view->setDisplay('page_1');
    $view->setArguments(['test']);
    $view->setCurrentPage(2);

    $serialized = serialize($view);

    // Test the view storage object is not present in the actual serialized
    // string.
    $this->assertIdentical(strpos($serialized, '"Drupal\views\Entity\View"'), FALSE, 'The Drupal\views\Entity\View class was not found in the serialized string.');

    /** @var \Drupal\views\ViewExecutable $unserialized */
    $unserialized = unserialize($serialized);

    $this->assertTrue($unserialized instanceof ViewExecutable);
    $this->assertIdentical($view->storage->id(), $unserialized->storage->id(), 'The expected storage entity was loaded on the unserialized view.');
    $this->assertIdentical($unserialized->current_display, 'page_1', 'The expected display was set on the unserialized view.');
    $this->assertIdentical($unserialized->args, ['test'], 'The expected argument was set on the unserialized view.');
    $this->assertIdentical($unserialized->getCurrentPage(), 2, 'The expected current page was set on the unserialized view.');
  }

}
