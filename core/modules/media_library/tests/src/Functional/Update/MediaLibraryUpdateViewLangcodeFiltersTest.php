<?php

namespace Drupal\Tests\media_library\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the media library module updates for the langcode filters.
 *
 * @group media_library
 * @group legacy
 */
class MediaLibraryUpdateViewLangcodeFiltersTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../../../media/tests/fixtures/update/drupal-8.4.0-media_installed.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.7.2-media_library_installed.php',
    ];
  }

  /**
   * Tests that the langcode filters are added to the media library view.
   *
   * @see media_library_post_update_add_langcode_filters()
   */
  public function testMediaLibraryViewStatusExtraFilter() {
    $config = $this->config('views.view.media_library');
    // We don't have any language filters yet for all displays.
    $this->assertNull($config->get('display.default.display_options.filters.langcode'));
    $this->assertNull($config->get('display.default.display_options.filters.default_langcode'));
    $this->assertNull($config->get('display.widget.display_options.filters.langcode'));
    $this->assertNull($config->get('display.widget.display_options.filters.default_langcode'));
    $this->assertNull($config->get('display.widget_table.display_options.filters.langcode'));
    $this->assertNull($config->get('display.widget_table.display_options.filters.default_langcode'));
    // The rendering language should not be set for the displays.
    $this->assertNull($config->get('display.default.display_options.rendering_language'));
    $this->assertNull($config->get('display.widget.display_options.rendering_language'));
    $this->assertNull($config->get('display.widget_table.display_options.rendering_language'));

    $this->runUpdates();

    $config = $this->config('views.view.media_library');

    // The update should add the langcode filter to the default display only.
    $this->assertNull($config->get('display.widget.display_options.filters.langcode'));
    $this->assertNull($config->get('display.widget_table.display_options.filters.langcode'));
    $default_langcode_filter = $config->get('display.default.display_options.filters.langcode');
    $this->assertInternalType('array', $default_langcode_filter);
    $this->assertSame('langcode', $default_langcode_filter['field']);
    $this->assertSame('media', $default_langcode_filter['entity_type']);
    $this->assertSame('language', $default_langcode_filter['plugin_id']);
    $this->assertSame('langcode', $default_langcode_filter['id']);
    $this->assertTrue($default_langcode_filter['exposed']);

    // The update should add the default_langcode filter to the widget displays
    // only.
    $this->assertNull($config->get('display.default.display_options.filters.default_langcode'));
    foreach (['widget', 'widget_table'] as $display_id) {
      $filter = $config->get('display.' . $display_id . '.display_options.filters.default_langcode');
      $this->assertInternalType('array', $filter);
      $this->assertSame('default_langcode', $filter['field']);
      $this->assertSame('media', $filter['entity_type']);
      $this->assertSame('boolean', $filter['plugin_id']);
      $this->assertSame('default_langcode', $filter['id']);
      $this->assertFalse($filter['exposed']);
    }

    // The default display should use the default rendering language, which is
    // the language of the content.
    $this->assertNull($config->get('display.default.display_options.rendering_language'));
    // The rendering language of the row should be set to the interface
    // language.
    $this->assertSame('***LANGUAGE_language_interface***', $config->get('display.widget.display_options.rendering_language'));
    $this->assertSame('***LANGUAGE_language_interface***', $config->get('display.widget_table.display_options.rendering_language'));
  }

}
