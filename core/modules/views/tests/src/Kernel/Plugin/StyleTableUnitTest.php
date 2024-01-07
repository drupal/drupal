<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\views\Views;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the table style plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\style\Table
 */
class StyleTableUnitTest extends PluginKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_table'];

  /**
   * Tests the table style.
   */
  public function testTable() {
    $view = Views::getView('test_table');

    $view->setDisplay('default');
    $view->initStyle();
    $view->initHandlers();
    $view->initQuery();
    $style_plugin = $view->style_plugin;

    // Test the buildSort() method.
    $request = new Request();
    $view->setRequest($request);

    $style_plugin->options['override'] = FALSE;

    $style_plugin->options['default'] = '';
    $this->assertTrue($style_plugin->buildSort(), 'If no order and no default order is specified, the normal sort should be used.');

    $style_plugin->options['default'] = 'id';
    $this->assertTrue($style_plugin->buildSort(), 'If no order but a default order is specified, the normal sort should be used.');

    $request->attributes->set('order', $this->randomMachineName());
    $this->assertTrue($style_plugin->buildSort(), 'If no valid field is chosen for order, the normal sort should be used.');

    $request->attributes->set('order', 'id');
    $this->assertTrue($style_plugin->buildSort(), 'If a valid order is specified but the table is configured to not override, the normal sort should be used.');

    $style_plugin->options['override'] = TRUE;

    $this->assertFalse($style_plugin->buildSort(), 'If a valid order is specified and the table is configured to override, the normal sort should not be used.');

    // Test the buildSortPost() method.
    $request = new Request();
    $view->setRequest($request);

    // Setup no valid default.
    $this->prepareView($view);
    $style_plugin = $view->style_plugin;
    $style_plugin->options['default'] = '';
    $style_plugin->buildSortPost();
    $this->assertNull($style_plugin->order, 'No sort order was set, when no order was specified and no default column was selected.');
    $this->assertNull($style_plugin->active, 'No sort field was set, when no order was specified and no default column was selected.');
    $view->destroy();

    // Setup a valid default + column specific default sort order.
    $this->prepareView($view);
    $style_plugin = $view->style_plugin;
    $style_plugin->options['default'] = 'id';
    $style_plugin->options['info']['id']['default_sort_order'] = 'desc';
    $style_plugin->buildSortPost();
    $this->assertSame('desc', $style_plugin->order, 'Fallback to the right default sort order.');
    $this->assertSame('id', $style_plugin->active, 'Fallback to the right default sort field.');
    $view->destroy();

    // Setup a valid default + table default sort order.
    $this->prepareView($view);
    $style_plugin = $view->style_plugin;
    $style_plugin->options['default'] = 'id';
    $style_plugin->options['info']['id']['default_sort_order'] = NULL;
    $style_plugin->options['order'] = 'asc';
    $style_plugin->buildSortPost();
    $this->assertSame('asc', $style_plugin->order, 'Fallback to the right default sort order.');
    $this->assertSame('id', $style_plugin->active, 'Fallback to the right default sort field.');
    $view->destroy();

    // Use an invalid field.
    $this->prepareView($view);
    $style_plugin = $view->style_plugin;
    $request->query->set('sort', 'asc');
    $random_name = $this->randomMachineName();
    $request->query->set('order', $random_name);
    $style_plugin->buildSortPost();
    $this->assertSame('asc', $style_plugin->order, 'No sort order was set, when invalid sort order was specified.');
    $this->assertNull($style_plugin->active, 'No sort field was set, when invalid sort order was specified.');
    $view->destroy();

    // Use an existing field, and sort both ascending and descending.
    foreach (['asc', 'desc'] as $order) {
      $this->prepareView($view);
      $style_plugin = $view->style_plugin;
      $request->query->set('sort', $order);
      $request->query->set('order', 'id');
      $style_plugin->buildSortPost();
      $this->assertSame($order, $style_plugin->order, 'Ensure the right sort order was set.');
      $this->assertSame('id', $style_plugin->active, 'Ensure the right order was set.');
      $view->destroy();
    }

    $view->destroy();

    // Excluded field to make sure its wrapping td doesn't show.
    $this->prepareView($view);
    $view->field['name']->options['exclude'] = TRUE;
    $output = $view->preview();
    $output = (string) \Drupal::service('renderer')->renderRoot($output);
    $this->assertStringNotContainsString('views-field-name', $output, "Excluded field's wrapper was not rendered.");
    $view->destroy();

    // Render a non empty result, and ensure that the empty area handler is not
    // rendered.
    $this->executeView($view);
    $output = $view->preview();
    $output = (string) \Drupal::service('renderer')->renderRoot($output);

    $this->assertStringNotContainsString('custom text', $output, 'Empty handler was not rendered on a non empty table.');

    // Render an empty result, and ensure that the area handler is rendered.
    $view->setDisplay('default');
    $view->executed = TRUE;
    $view->result = [];
    $output = $view->preview();
    $output = (string) \Drupal::service('renderer')->renderRoot($output);

    $this->assertStringContainsString('custom text', $output, 'Empty handler got rendered on an empty table.');
  }

  /**
   * Prepares a view executable by initializing everything which is needed.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The executable to prepare.
   */
  protected function prepareView(ViewExecutable $view) {
    $view->setDisplay();
    $view->initStyle();
    $view->initHandlers();
    $view->initQuery();
  }

}
