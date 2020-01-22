<?php

namespace Drupal\Tests\media_library\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Views;

/**
 * Tests the media library module updates for the view table display.
 *
 * @group media_library
 * @group legacy
 */
class MediaLibraryUpdateViewTableDisplayTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../../../media/tests/fixtures/update/drupal-8.4.0-media_installed.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.media_library-update-view-table-display-2981044.php',
    ];
  }

  /**
   * Tests the widget_table display is added to the views config.
   *
   * @see media_library_post_update_table_display()
   */
  public function testMediaLibraryViewTableDisplay() {
    $grid_prefix = 'display.widget';
    $table_prefix = 'display.widget_table';

    $config = $this->config('views.view.media_library');
    $this->assertNull($config->get("$grid_prefix.display_options.defaults.css_class"));
    $this->assertNull($config->get("$grid_prefix.display_options.css_class"));
    $this->assertNull($config->get($table_prefix));

    $this->runUpdates();

    $config = $this->config('views.view.media_library');

    // Assert the CSS classes are updated for the widget display.
    $this->assertFalse($config->get("$grid_prefix.display_options.defaults.css_class"));
    $this->assertSame('media-library-view js-media-library-view media-library-view--widget', $config->get("$grid_prefix.display_options.css_class"));
    $this->assertSame('media-library-item media-library-item--grid js-media-library-item js-click-to-select', $config->get('display.default.display_options.style.options.row_class'));

    // Assert the widget_table display was added correctly.
    $this->assertSame('table', $config->get("$table_prefix.display_options.style.type"));
    $this->assertSame('media-library-item media-library-item--table js-media-library-item js-click-to-select', $config->get("$table_prefix.display_options.style.options.row_class"));
    $this->assertSame('fields', $config->get("$table_prefix.display_options.row.type"));
    $this->assertSame(['media_library_select_form', 'thumbnail__target_id', 'name', 'uid', 'changed'], array_keys($config->get("$table_prefix.display_options.fields")));

    // Assert the CSS classes are added to the widget_table display.
    $this->assertFalse($config->get("$table_prefix.display_options.defaults.css_class"));
    $this->assertSame('media-library-view js-media-library-view media-library-view--widget', $config->get("$table_prefix.display_options.css_class"));

    // Assert all display options are set correctly on the widget_table display.
    $this->assertSame($config->get("$grid_prefix.display_options.filters"), $config->get("$table_prefix.display_options.filters"));
    $this->assertSame($config->get("$grid_prefix.display_options.access"), $config->get("$table_prefix.display_options.access"));
    $this->assertSame($config->get("$grid_prefix.display_options.sorts"), $config->get("$table_prefix.display_options.sorts"));
    $this->assertSame($config->get("$grid_prefix.display_options.pager"), $config->get("$table_prefix.display_options.pager"));
    $this->assertSame($config->get("$grid_prefix.display_options.arguments"), $config->get("$table_prefix.display_options.arguments"));

    // Assert the display links are added to the widget and widget_table
    // displays.
    $this->assertSame('display_link', $config->get("$grid_prefix.display_options.header.display_link_grid.plugin_id"));
    $this->assertSame('display_link', $config->get("$grid_prefix.display_options.header.display_link_table.plugin_id"));
    $this->assertSame('display_link', $config->get("$table_prefix.display_options.header.display_link_grid.plugin_id"));
    $this->assertSame('display_link', $config->get("$table_prefix.display_options.header.display_link_table.plugin_id"));
    $this->assertSame('widget', $config->get("$grid_prefix.display_options.header.display_link_grid.display_id"));
    $this->assertSame('widget_table', $config->get("$grid_prefix.display_options.header.display_link_table.display_id"));
    $this->assertSame('widget', $config->get("$table_prefix.display_options.header.display_link_grid.display_id"));
    $this->assertSame('widget_table', $config->get("$table_prefix.display_options.header.display_link_table.display_id"));
  }

  /**
   * Tests the views config update when the widget display is overridden.
   *
   * @see media_library_post_update_table_display()
   */
  public function testMediaLibraryChangedViewTableDisplay() {
    $grid_prefix = 'display.widget';
    $table_prefix = 'display.widget_table';

    $view = Views::getView('media_library');

    // The existing 'widget' display could have been overridden. The 'widget'
    // and 'widget_table' displays need to have the same display options, so we
    // need to verify the overridden settings are correctly set when creating
    // the 'widget_table' display.
    $view->setDisplay('widget');
    $grid_display = $view->getDisplay('widget');

    // Change the filters, sorts and pager for the widget display.
    $grid_display->overrideOption('filters', [
      'uid' => [
        'id' => 'uid',
        'table' => 'media_field_data',
        'field' => 'uid',
        'relationship' => 'none',
        'operator' => '=',
        'exposed' => TRUE,
      ],
    ]);
    $grid_display->overrideOption('sorts', [
      'name' => [
        'id' => 'name',
        'table' => 'media_field_data',
        'field' => 'name',
        'relationship' => 'none',
        'order' => 'ASC',
      ],
    ]);
    $grid_display->overrideOption('pager', [
      'type' => 'full',
      'options' => ['items_per_page' => 10],
    ]);
    $view->save();

    $this->runUpdates();

    $config = $this->config('views.view.media_library');

    // Assert the CSS classes are updated for the widget display.
    $this->assertFalse($config->get("$grid_prefix.display_options.defaults.css_class"));
    $this->assertSame('media-library-view js-media-library-view media-library-view--widget', $config->get("$grid_prefix.display_options.css_class"));
    $this->assertSame('media-library-item media-library-item--grid js-media-library-item js-click-to-select', $config->get('display.default.display_options.style.options.row_class'));

    // Assert the widget_table display was added correctly.
    $this->assertSame('table', $config->get("$table_prefix.display_options.style.type"));
    $this->assertSame('media-library-item media-library-item--table js-media-library-item js-click-to-select', $config->get("$table_prefix.display_options.style.options.row_class"));
    $this->assertSame('fields', $config->get("$table_prefix.display_options.row.type"));
    $this->assertSame(['media_library_select_form', 'thumbnail__target_id', 'name', 'uid', 'changed'], array_keys($config->get("$table_prefix.display_options.fields")));

    // Assert the CSS classes are added to the widget_table display.
    $this->assertFalse($config->get("$table_prefix.display_options.defaults.css_class"));
    $this->assertSame('media-library-view js-media-library-view media-library-view--widget', $config->get("$table_prefix.display_options.css_class"));

    // Assert all display options are set correctly on the widget_table display.
    $this->assertSame($config->get("$grid_prefix.display_options.filters"), $config->get("$table_prefix.display_options.filters"));
    $this->assertSame($config->get("$grid_prefix.display_options.access"), $config->get("$table_prefix.display_options.access"));
    $this->assertSame($config->get("$grid_prefix.display_options.sorts"), $config->get("$table_prefix.display_options.sorts"));
    $this->assertSame($config->get("$grid_prefix.display_options.pager"), $config->get("$table_prefix.display_options.pager"));
    $this->assertSame($config->get("$grid_prefix.display_options.arguments"), $config->get("$table_prefix.display_options.arguments"));

    // Assert the display links are added to the widget and widget_table
    // displays.
    $this->assertSame('display_link', $config->get("$grid_prefix.display_options.header.display_link_grid.plugin_id"));
    $this->assertSame('display_link', $config->get("$grid_prefix.display_options.header.display_link_table.plugin_id"));
    $this->assertSame('display_link', $config->get("$table_prefix.display_options.header.display_link_grid.plugin_id"));
    $this->assertSame('display_link', $config->get("$table_prefix.display_options.header.display_link_table.plugin_id"));
    $this->assertSame('widget', $config->get("$grid_prefix.display_options.header.display_link_grid.display_id"));
    $this->assertSame('widget_table', $config->get("$grid_prefix.display_options.header.display_link_table.display_id"));
    $this->assertSame('widget', $config->get("$table_prefix.display_options.header.display_link_grid.display_id"));
    $this->assertSame('widget_table', $config->get("$table_prefix.display_options.header.display_link_table.display_id"));
  }

}
