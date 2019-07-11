<?php

namespace Drupal\Tests\views\Kernel;

/**
 * Tests basic functions from the Views module.
 *
 * @group views
 */
use Drupal\views\Plugin\views\filter\Standard;
use Drupal\views\Views;
use Drupal\Component\Render\FormattableMarkup;

class ModuleTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view_status', 'test_view', 'test_argument'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field', 'user', 'block'];

  /**
   * Stores the last triggered error.
   *
   * @var string
   *
   * @see \Drupal\views\Tests\ModuleTest::errorHandler()
   */
  protected $lastErrorMessage;

  /**
   * Tests the  ViewsHandlerManager::getHandler() method.
   *
   * @see \Drupal\views\Plugin\ViewsHandlerManager::getHandler()
   */
  public function testViewsGetHandler() {
    $types = ['field', 'area', 'filter'];
    foreach ($types as $type) {
      $item = [
        'table' => $this->randomMachineName(),
        'field' => $this->randomMachineName(),
      ];
      $handler = $this->container->get('plugin.manager.views.' . $type)->getHandler($item);
      $this->assertEqual('Drupal\views\Plugin\views\\' . $type . '\Broken', get_class($handler), new FormattableMarkup('Make sure that a broken handler of type: @type is created.', ['@type' => $type]));
    }

    $views_data = $this->viewsData();
    $test_tables = ['views_test_data' => ['id', 'name']];
    foreach ($test_tables as $table => $fields) {
      foreach ($fields as $field) {
        $data = $views_data[$table][$field];
        $item = [
          'table' => $table,
          'field' => $field,
        ];
        foreach ($data as $id => $field_data) {
          if (!in_array($id, ['title', 'help'])) {
            $handler = $this->container->get('plugin.manager.views.' . $id)->getHandler($item);
            $this->assertInstanceHandler($handler, $table, $field, $id);
          }
        }
      }
    }

    // Test the override handler feature.
    $item = [
      'table' => 'views_test_data',
      'field' => 'job',
    ];
    $handler = $this->container->get('plugin.manager.views.filter')->getHandler($item, 'standard');
    $this->assertTrue($handler instanceof Standard);

    // @todo Reinstate these tests when the debug() in views_get_handler() is
    //   restored.
    return;

    // Test non-existent tables/fields.
    set_error_handler([$this, 'customErrorHandler']);
    $item = [
      'table' => 'views_test_data',
      'field' => 'field_invalid',
    ];
    $this->container->get('plugin.manager.views.field')->getHandler($item);
    $this->assertTrue(strpos($this->lastErrorMessage, new FormattableMarkup("Missing handler: @table @field @type", ['@table' => 'views_test_data', '@field' => 'field_invalid', '@type' => 'field'])) !== FALSE, 'An invalid field name throws a debug message.');
    unset($this->lastErrorMessage);

    $item = [
      'table' => 'table_invalid',
      'field' => 'id',
    ];
    $this->container->get('plugin.manager.views.filter')->getHandler($item);
    $this->assertEqual(strpos($this->lastErrorMessage, new FormattableMarkup("Missing handler: @table @field @type", ['@table' => 'table_invalid', '@field' => 'id', '@type' => 'filter'])) !== FALSE, 'An invalid table name throws a debug message.');
    unset($this->lastErrorMessage);

    $item = [
      'table' => 'table_invalid',
      'field' => 'id',
    ];
    $this->container->get('plugin.manager.views.filter')->getHandler($item);
    $this->assertEqual(strpos($this->lastErrorMessage, new FormattableMarkup("Missing handler: @table @field @type", ['@table' => 'table_invalid', '@field' => 'id', '@type' => 'filter'])) !== FALSE, 'An invalid table name throws a debug message.');
    unset($this->lastErrorMessage);

    restore_error_handler();
  }

  /**
   * Defines an error handler which is used in the test.
   *
   * Because this is registered in set_error_handler(), it has to be public.
   *
   * @param int $error_level
   *   The level of the error raised.
   * @param string $message
   *   The error message.
   * @param string $filename
   *   The filename that the error was raised in.
   * @param int $line
   *   The line number the error was raised at.
   * @param array $context
   *   An array that points to the active symbol table at the point the error
   *   occurred.
   *
   * @see set_error_handler()
   */
  public function customErrorHandler($error_level, $message, $filename, $line, $context) {
    $this->lastErrorMessage = $message;
  }

  /**
   * Tests the load wrapper/helper functions.
   */
  public function testLoadFunctions() {
    $this->enableModules(['text', 'node']);
    $this->installEntitySchema('node');
    $this->installConfig(['node']);
    $storage = $this->container->get('entity_type.manager')->getStorage('view');

    // Test views_view_is_enabled/disabled.
    $archive = $storage->load('archive');
    $this->assertTrue(views_view_is_disabled($archive), 'views_view_is_disabled works as expected.');
    // Enable the view and check this.
    $archive->enable();
    $this->assertTrue(views_view_is_enabled($archive), ' views_view_is_enabled works as expected.');

    // We can store this now, as we have enabled/disabled above.
    $all_views = $storage->loadMultiple();

    // Test Views::getAllViews().
    ksort($all_views);
    $this->assertEquals(array_keys($all_views), array_keys(Views::getAllViews()), 'Views::getAllViews works as expected.');

    // Test Views::getEnabledViews().
    $expected_enabled = array_filter($all_views, function ($view) {
      return views_view_is_enabled($view);
    });
    $this->assertEquals(array_keys($expected_enabled), array_keys(Views::getEnabledViews()), 'Expected enabled views returned.');

    // Test Views::getDisabledViews().
    $expected_disabled = array_filter($all_views, function ($view) {
      return views_view_is_disabled($view);
    });
    $this->assertEquals(array_keys($expected_disabled), array_keys(Views::getDisabledViews()), 'Expected disabled views returned.');

    // Test Views::getViewsAsOptions().
    // Test the $views_only parameter.
    $this->assertIdentical(array_keys($all_views), array_keys(Views::getViewsAsOptions(TRUE)), 'Expected option keys for all views were returned.');
    $expected_options = [];
    foreach ($all_views as $id => $view) {
      $expected_options[$id] = $view->label();
    }
    $this->assertIdentical($expected_options, $this->castSafeStrings(Views::getViewsAsOptions(TRUE)), 'Expected options array was returned.');

    // Test the default.
    $this->assertIdentical($this->formatViewOptions($all_views), $this->castSafeStrings(Views::getViewsAsOptions()), 'Expected options array for all views was returned.');
    // Test enabled views.
    $this->assertIdentical($this->formatViewOptions($expected_enabled), $this->castSafeStrings(Views::getViewsAsOptions(FALSE, 'enabled')), 'Expected enabled options array was returned.');
    // Test disabled views.
    $this->assertIdentical($this->formatViewOptions($expected_disabled), $this->castSafeStrings(Views::getViewsAsOptions(FALSE, 'disabled')), 'Expected disabled options array was returned.');

    // Test the sort parameter.
    $all_views_sorted = $all_views;
    ksort($all_views_sorted);
    $this->assertIdentical(array_keys($all_views_sorted), array_keys(Views::getViewsAsOptions(TRUE, 'all', NULL, FALSE, TRUE)), 'All view id keys returned in expected sort order');

    // Test $exclude_view parameter.
    $this->assertFalse(array_key_exists('archive', Views::getViewsAsOptions(TRUE, 'all', 'archive')), 'View excluded from options based on name');
    $this->assertFalse(array_key_exists('archive:default', Views::getViewsAsOptions(FALSE, 'all', 'archive:default')), 'View display excluded from options based on name');
    $this->assertFalse(array_key_exists('archive', Views::getViewsAsOptions(TRUE, 'all', $archive->getExecutable())), 'View excluded from options based on object');

    // Test the $opt_group parameter.
    $expected_opt_groups = [];
    foreach ($all_views as $view) {
      foreach ($view->get('display') as $display) {
        $expected_opt_groups[$view->id()][$view->id() . ':' . $display['id']] = (string) t('@view : @display', ['@view' => $view->id(), '@display' => $display['id']]);
      }
    }
    $this->assertIdentical($expected_opt_groups, $this->castSafeStrings(Views::getViewsAsOptions(FALSE, 'all', NULL, TRUE)), 'Expected option array for an option group returned.');
  }

  /**
   * Tests view enable and disable procedural wrapper functions.
   */
  public function testStatusFunctions() {
    $view = Views::getView('test_view_status')->storage;

    $this->assertFalse($view->status(), 'The view status is disabled.');

    views_enable_view($view);
    $this->assertTrue($view->status(), 'A view has been enabled.');
    $this->assertEqual($view->status(), views_view_is_enabled($view), 'views_view_is_enabled is correct.');

    views_disable_view($view);
    $this->assertFalse($view->status(), 'A view has been disabled.');
    $this->assertEqual(!$view->status(), views_view_is_disabled($view), 'views_view_is_disabled is correct.');
  }

  /**
   * Tests the \Drupal\views\Views::fetchPluginNames() method.
   */
  public function testViewsFetchPluginNames() {
    // All style plugins should be returned, as we have not specified a type.
    $plugins = Views::fetchPluginNames('style');
    $definitions = $this->container->get('plugin.manager.views.style')->getDefinitions();
    $expected = [];
    foreach ($definitions as $id => $definition) {
      $expected[$id] = $definition['title'];
    }
    asort($expected);
    $this->assertIdentical(array_keys($plugins), array_keys($expected));

    // Test using the 'test' style plugin type only returns the test_style and
    // mapping_test plugins.
    $plugins = Views::fetchPluginNames('style', 'test');
    $this->assertIdentical(array_keys($plugins), ['mapping_test', 'test_style', 'test_template_style']);

    // Test a non existent style plugin type returns no plugins.
    $plugins = Views::fetchPluginNames('style', $this->randomString());
    $this->assertIdentical($plugins, []);
  }

  /**
   * Tests the \Drupal\views\Views::pluginList() method.
   */
  public function testViewsPluginList() {
    $plugin_list = Views::pluginList();
    // Only plugins used by 'test_view' should be in the plugin list.
    foreach (['display:default', 'pager:none'] as $key) {
      list($plugin_type, $plugin_id) = explode(':', $key);
      $plugin_def = $this->container->get("plugin.manager.views.$plugin_type")->getDefinition($plugin_id);

      $this->assertTrue(isset($plugin_list[$key]), new FormattableMarkup('The expected @key plugin list key was found.', ['@key' => $key]));
      $plugin_details = $plugin_list[$key];

      $this->assertEqual($plugin_details['type'], $plugin_type, 'The expected plugin type was found.');
      $this->assertEqual($plugin_details['title'], $plugin_def['title'], 'The expected plugin title was found.');
      $this->assertEqual($plugin_details['provider'], $plugin_def['provider'], 'The expected plugin provider was found.');
      $this->assertTrue(in_array('test_view', $plugin_details['views']), 'The test_view View was found in the list of views using this plugin.');
    }
  }

  /**
   * Tests views.module: views_embed_view().
   */
  public function testViewsEmbedView() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $result = views_embed_view('test_argument');
    $renderer->renderPlain($result);
    $this->assertEqual(count($result['view_build']['#view']->result), 5);

    $result = views_embed_view('test_argument', 'default', 1);
    $renderer->renderPlain($result);
    $this->assertEqual(count($result['view_build']['#view']->result), 1);

    $result = views_embed_view('test_argument', 'default', '1,2');
    $renderer->renderPlain($result);
    $this->assertEqual(count($result['view_build']['#view']->result), 2);

    $result = views_embed_view('test_argument', 'default', '1,2', 'John');
    $renderer->renderPlain($result);
    $this->assertEqual(count($result['view_build']['#view']->result), 1);

    $result = views_embed_view('test_argument', 'default', '1,2', 'John,George');
    $renderer->renderPlain($result);
    $this->assertEqual(count($result['view_build']['#view']->result), 2);
  }

  /**
   * Tests the \Drupal\views\ViewsExecutable::preview() method.
   */
  public function testViewsPreview() {
    $view = Views::getView('test_argument');
    $result = $view->preview('default');
    $this->assertEqual(count($result['#view']->result), 5);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['0' => 1]);
    $this->assertEqual(count($result['#view']->result), 1);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['3' => 1]);
    $this->assertEqual(count($result['#view']->result), 1);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['0' => '1,2']);
    $this->assertEqual(count($result['#view']->result), 2);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['3' => '1,2']);
    $this->assertEqual(count($result['#view']->result), 2);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['0' => '1,2', '1' => 'John']);
    $this->assertEqual(count($result['#view']->result), 1);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['3' => '1,2', '4' => 'John']);
    $this->assertEqual(count($result['#view']->result), 1);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['0' => '1,2', '1' => 'John,George']);
    $this->assertEqual(count($result['#view']->result), 2);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['3' => '1,2', '4' => 'John,George']);
    $this->assertEqual(count($result['#view']->result), 2);
  }

  /**
   * Helper to return an expected views option array.
   *
   * @param array $views
   *   An array of Drupal\views\Entity\View objects for which to
   *   create an options array.
   *
   * @return array
   *   A formatted options array that matches the expected output.
   */
  protected function formatViewOptions(array $views = []) {
    $expected_options = [];
    foreach ($views as $view) {
      foreach ($view->get('display') as $display) {
        $expected_options[$view->id() . ':' . $display['id']] = (string) t('View: @view - Display: @display',
          ['@view' => $view->id(), '@display' => $display['id']]);
      }
    }

    return $expected_options;
  }

  /**
   * Ensure that a certain handler is a instance of a certain table/field.
   */
  public function assertInstanceHandler($handler, $table, $field, $id) {
    $table_data = $this->container->get('views.views_data')->get($table);
    $field_data = $table_data[$field][$id];

    $this->assertEqual($field_data['id'], $handler->getPluginId());
  }

}
