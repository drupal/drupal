<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\views\Views;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Drupal\views\Plugin\views\exposed_form\ExposedFormPluginInterface;
use Drupal\views\Plugin\views\pager\PagerPluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * Drupal unit tests for the DisplayPluginBase class.
 *
 * @group views
 */
class DisplayKernelTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'node', 'field', 'user'];

  /**
   * Views plugin types to test.
   *
   * @var array
   */
  protected $pluginTypes = [
    'access',
    'cache',
    'query',
    'exposed_form',
    'pager',
    'style',
    'row',
  ];

  /**
   * Views handler types to test.
   *
   * @var array
   */
  protected $handlerTypes = [
    'fields',
    'sorts',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_display_defaults', 'test_view'];

  /**
   * Tests the default display options.
   */
  public function testDefaultOptions(): void {
    // Save the view.
    $view = Views::getView('test_display_defaults');
    $view->mergeDefaults();
    $view->save();

    // Reload to get saved storage values.
    $view = Views::getView('test_display_defaults');
    $view->initDisplay();
    $display_data = $view->storage->get('display');

    foreach ($view->displayHandlers as $id => $display) {
      // Test the view plugin options against the storage.
      foreach ($this->pluginTypes as $type) {
        $options = $display->getOption($type);
        $this->assertSame($display_data[$id]['display_options'][$type]['options'], $options['options']);
      }
      // Test the view handler options against the storage.
      foreach ($this->handlerTypes as $type) {
        $options = $display->getOption($type);
        $this->assertSame($display_data[$id]['display_options'][$type], $options);
      }
    }
  }

  /**
   * Tests the \Drupal\views\Plugin\views\display\DisplayPluginBase::getPlugin() method.
   */
  public function testGetPlugin(): void {
    $view = Views::getView('test_display_defaults');
    $view->initDisplay();
    $display_handler = $view->display_handler;

    $this->assertInstanceOf(AccessPluginBase::class, $display_handler->getPlugin('access'));
    $this->assertInstanceOf(CachePluginBase::class, $display_handler->getPlugin('cache'));
    $this->assertInstanceOf(ExposedFormPluginInterface::class, $display_handler->getPlugin('exposed_form'));
    $this->assertInstanceOf(PagerPluginBase::class, $display_handler->getPlugin('pager'));
    $this->assertInstanceOf(QueryPluginBase::class, $display_handler->getPlugin('query'));
    $this->assertInstanceOf(RowPluginBase::class, $display_handler->getPlugin('row'));
    $this->assertInstanceOf(StylePluginBase::class, $display_handler->getPlugin('style'));
    // Test that nothing is returned when an invalid type is requested.
    $this->assertNull($display_handler->getPlugin('invalid'), 'NULL was returned for an invalid instance');
    // Test that nothing was returned for an instance with no 'type' in options.
    unset($display_handler->options['access']);
    $this->assertNull($display_handler->getPlugin('access'), 'NULL was returned for a plugin type with no "type" option');

    // Get a plugin twice, and make sure the same instance is returned.
    $view->destroy();
    $view->initDisplay();
    $first = spl_object_hash($display_handler->getPlugin('style'));
    $second = spl_object_hash($display_handler->getPlugin('style'));
    $this->assertSame($first, $second, 'The same plugin instance was returned.');
  }

  /**
   * Tests the ::isIdentifierUnique method.
   */
  public function testisIdentifierUnique(): void {
    $view = Views::getView('test_view');
    $view->initDisplay();

    // Add a handler that doesn't have an Identifier when exposed.
    $sorts = [
      'name' => [
        'id' => 'name',
        'field' => 'name',
        'table' => 'views_test_data',
        'plugin_id' => 'standard',
        'order' => 'asc',
        'expose' => [
          'label' => 'Id',
          'field_identifier' => 'name',
        ],
        'exposed' => TRUE,
      ],
    ];
    // Add a handler that does have an Identifier when exposed.
    $filters = [
      'id' => [
        'field' => 'id',
        'id' => 'id',
        'table' => 'views_test_data',
        'value' => [],
        'plugin_id' => 'numeric',
        'exposed' => TRUE,
        'expose' => [
          'operator_id' => '',
          'label' => 'Id',
          'description' => '',
          'identifier' => 'id',
          'required' => FALSE,
          'remember' => FALSE,
          'multiple' => FALSE,
        ],
      ],
    ];
    $view->display_handler->setOption('sorts', $sorts);
    $view->display_handler->setOption('filters', $filters);

    $this->assertTrue($view->display_handler->isIdentifierUnique('some_id', 'some_id'));
    $this->assertFalse($view->display_handler->isIdentifierUnique('some_id', 'id'));

    // Check that an exposed filter is able to use the same identifier as an
    // exposed sort.
    $sorts['name']['expose']['field_identifier'] = 'id';
    $view->display_handler->handlers = [];
    $view->display_handler->setOption('sorts', $sorts);
    $this->assertTrue($view->display_handler->isIdentifierUnique('id', 'id'));
  }

}
