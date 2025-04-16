<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\views\Plugin\views\area\Broken as BrokenArea;
use Drupal\views\Plugin\views\field\Broken as BrokenField;
use Drupal\views\Plugin\views\filter\Broken as BrokenFilter;
use Drupal\views\Plugin\views\filter\Standard;
use Drupal\views\Plugin\views\ViewsHandlerInterface;
use Drupal\views\Views;

/**
 * Tests basic functions from the Views module.
 *
 * @group views
 */
class ModuleTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view_status', 'test_view', 'test_argument'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'user', 'block'];

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
  public function testViewsGetHandler(): void {
    $types = [
      'field' => BrokenField::class,
      'area' => BrokenArea::class,
      'filter' => BrokenFilter::class,
    ];
    // Test non-existent tables/fields.
    $items = [
      [
        'table' => 'table_invalid',
        'field' => 'id',
      ],
      [
        'table' => 'views_test_data',
        'field' => 'field_invalid',
      ],
    ];
    $form_state = new FormState();
    $description_top = '<p>The handler for this item is broken or missing. The following details are available:</p>';
    $description_bottom = '<p>Installing the appropriate module may solve this issue. Otherwise, check to see if there is a module update available.</p>';
    foreach ($types as $type => $class) {
      foreach ($items as $item) {
        $handler = $this->container->get('plugin.manager.views.' . $type)
          ->getHandler($item);
        $this->assertTrue($handler instanceof $class);
        // Make sure details available at edit form.
        $form = [];
        $handler->buildOptionsForm($form, $form_state);
        $this->assertEquals($description_top, $form['description']['description_top']['#markup']);
        $this->assertEquals($description_bottom, $form['description']['description_bottom']['#markup']);
        $details = [];
        foreach ($item as $key => $value) {
          $details[] = "$key: $value";
        }
        $this->assertEquals($details, $form['description']['detail_list']['#items']);
      }
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
    $this->assertInstanceOf(Standard::class, $handler);
  }

  /**
   * Tests the load wrapper/helper functions.
   */
  public function testLoadFunctions(): void {
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
    $this->assertSame(array_keys($all_views), array_keys(Views::getViewsAsOptions(TRUE)), 'Expected option keys for all views were returned.');
    $expected_options = [];
    foreach ($all_views as $id => $view) {
      $expected_options[$id] = $view->label();
    }
    $this->assertSame($expected_options, Views::getViewsAsOptions(TRUE), 'Expected options array was returned.');

    // Test the default.
    $this->assertEquals($this->formatViewOptions($all_views), Views::getViewsAsOptions(), 'Expected options array for all views was returned.');
    // Test enabled views.
    $this->assertEquals($this->formatViewOptions($expected_enabled), Views::getViewsAsOptions(FALSE, 'enabled'), 'Expected enabled options array was returned.');
    // Test disabled views.
    $this->assertEquals($this->formatViewOptions($expected_disabled), Views::getViewsAsOptions(FALSE, 'disabled'), 'Expected disabled options array was returned.');

    // Test the sort parameter.
    $all_views_sorted = $all_views;
    ksort($all_views_sorted);
    $this->assertSame(array_keys($all_views_sorted), array_keys(Views::getViewsAsOptions(TRUE, 'all', NULL, FALSE, TRUE)), 'All view id keys returned in expected sort order');

    // Test $exclude_view parameter.
    $this->assertArrayNotHasKey('archive', Views::getViewsAsOptions(TRUE, 'all', 'archive'));
    $this->assertArrayNotHasKey('archive:default', Views::getViewsAsOptions(FALSE, 'all', 'archive:default'));
    $this->assertArrayNotHasKey('archive', Views::getViewsAsOptions(TRUE, 'all', $archive->getExecutable()));

    // Test the $opt_group parameter.
    $expected_opt_groups = [];
    foreach ($all_views as $view) {
      foreach ($view->get('display') as $display) {
        $expected_opt_groups[$view->id()][$view->id() . ':' . $display['id']] = $view->id() . ' : ' . $display['id'];
      }
    }
    $this->assertEquals($expected_opt_groups, Views::getViewsAsOptions(FALSE, 'all', NULL, TRUE), 'Expected option array for an option group returned.');
  }

  /**
   * Tests view enable and disable procedural wrapper functions.
   */
  public function testStatusFunctions(): void {
    $view = Views::getView('test_view_status')->storage;

    $this->assertFalse($view->status(), 'The view status is disabled.');

    views_enable_view($view);
    $this->assertTrue($view->status(), 'A view has been enabled.');
    $this->assertEquals(views_view_is_enabled($view), $view->status(), 'views_view_is_enabled is correct.');

    views_disable_view($view);
    $this->assertFalse($view->status(), 'A view has been disabled.');
    $this->assertEquals(views_view_is_disabled($view), !$view->status(), 'views_view_is_disabled is correct.');
  }

  /**
   * Tests the \Drupal\views\Views::fetchPluginNames() method.
   */
  public function testViewsFetchPluginNames(): void {
    // All style plugins should be returned, as we have not specified a type.
    $plugins = Views::fetchPluginNames('style');
    $definitions = $this->container->get('plugin.manager.views.style')->getDefinitions();
    $expected = [];
    foreach ($definitions as $id => $definition) {
      $expected[$id] = $definition['title'];
    }
    asort($expected);
    $this->assertSame(array_keys($expected), array_keys($plugins));

    // Test using the 'test' style plugin type only returns the test_style and
    // mapping_test plugins.
    $plugins = Views::fetchPluginNames('style', 'test');
    $this->assertSame(['mapping_test', 'test_style', 'test_template_style'], array_keys($plugins));

    // Test a non existent style plugin type returns no plugins.
    $plugins = Views::fetchPluginNames('style', $this->randomString());
    $this->assertSame([], $plugins);
  }

  /**
   * Tests the \Drupal\views\Views::pluginList() method.
   */
  public function testViewsPluginList(): void {
    $plugin_list = Views::pluginList();
    // Only plugins used by 'test_view' should be in the plugin list.
    foreach (['display:default', 'pager:none'] as $key) {
      [$plugin_type, $plugin_id] = explode(':', $key);
      $plugin_def = $this->container->get("plugin.manager.views.$plugin_type")->getDefinition($plugin_id);

      $this->assertTrue(isset($plugin_list[$key]), "The expected $key plugin list key was found.");
      $plugin_details = $plugin_list[$key];

      $this->assertEquals($plugin_type, $plugin_details['type'], 'The expected plugin type was found.');
      $this->assertEquals($plugin_def['title'], $plugin_details['title'], 'The expected plugin title was found.');
      $this->assertEquals($plugin_def['provider'], $plugin_details['provider'], 'The expected plugin provider was found.');
      $this->assertContains('test_view', $plugin_details['views'], 'The test_view View was found in the list of views using this plugin.');
    }
  }

  /**
   * Tests views.module: views_embed_view().
   */
  public function testViewsEmbedView(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $result = views_embed_view('test_argument');
    $renderer->renderInIsolation($result);
    $this->assertCount(5, $result['view_build']['#view']->result);

    $result = views_embed_view('test_argument', 'default', 1);
    $renderer->renderInIsolation($result);
    $this->assertCount(1, $result['view_build']['#view']->result);

    $result = views_embed_view('test_argument', 'default', '1,2');
    $renderer->renderInIsolation($result);
    $this->assertCount(2, $result['view_build']['#view']->result);

    $result = views_embed_view('test_argument', 'default', '1,2', 'John');
    $renderer->renderInIsolation($result);
    $this->assertCount(1, $result['view_build']['#view']->result);

    $result = views_embed_view('test_argument', 'default', '1,2', 'John,George');
    $renderer->renderInIsolation($result);
    $this->assertCount(2, $result['view_build']['#view']->result);
  }

  /**
   * Tests the \Drupal\views\ViewsExecutable::preview() method.
   */
  public function testViewsPreview(): void {
    $view = Views::getView('test_argument');
    $result = $view->preview('default');
    $this->assertCount(5, $result['#view']->result);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['0' => 1]);
    $this->assertCount(1, $result['#view']->result);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['3' => 1]);
    $this->assertCount(1, $result['#view']->result);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['0' => '1,2']);
    $this->assertCount(2, $result['#view']->result);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['3' => '1,2']);
    $this->assertCount(2, $result['#view']->result);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['0' => '1,2', '1' => 'John']);
    $this->assertCount(1, $result['#view']->result);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['3' => '1,2', '4' => 'John']);
    $this->assertCount(1, $result['#view']->result);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['0' => '1,2', '1' => 'John,George']);
    $this->assertCount(2, $result['#view']->result);

    $view = Views::getView('test_argument');
    $result = $view->preview('default', ['3' => '1,2', '4' => 'John,George']);
    $this->assertCount(2, $result['#view']->result);
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
  protected function formatViewOptions(array $views = []): array {
    $expected_options = [];
    foreach ($views as $view) {
      foreach ($view->get('display') as $display) {
        $expected_options[$view->id() . ':' . $display['id']] = "View: {$view->id()} - Display: {$display['id']}";
      }
    }

    return $expected_options;
  }

  /**
   * Ensure that a certain handler is an instance of a certain table/field.
   *
   * @internal
   */
  public function assertInstanceHandler(ViewsHandlerInterface $handler, string $table, string $field, string $id): void {
    $table_data = $this->container->get('views.views_data')->get($table);
    $field_data = $table_data[$field][$id];

    $this->assertEquals($handler->getPluginId(), $field_data['id']);
  }

}
