<?php

namespace Drupal\Tests\media_library\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the media library module updates for the widget view.
 *
 * @group media_library
 * @group legacy
 */
class MediaLibraryUpdateWidgetViewTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../../../media/tests/fixtures/update/drupal-8.4.0-media_installed.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.media_library-update-widget-view-3020716.php',
    ];
  }

  /**
   * Tests that the media library view config is updated.
   *
   * @see media_library_update_8702()
   */
  public function testMediaLibraryViewsConfig() {
    $config = $this->config('views.view.media_library');
    $this->assertNull($config->get('display.widget.display_options.defaults.filters'));
    $this->assertNull($config->get('display.widget.display_options.defaults.arguments'));
    $this->assertArrayNotHasKey('filters', $config->get('display.widget.display_options'));
    $this->assertArrayNotHasKey('arguments', $config->get('display.widget.display_options'));

    $this->runUpdates();

    $config = $this->config('views.view.media_library');
    $this->assertFalse($config->get('display.widget.display_options.defaults.filters'));
    $this->assertFalse($config->get('display.widget.display_options.defaults.arguments'));
    $this->assertArrayHasKey('filters', $config->get('display.widget.display_options'));
    $this->assertArrayHasKey('arguments', $config->get('display.widget.display_options'));
    $this->assertSame('1', $config->get('display.widget.display_options.filters.status.value'));
    $this->assertTrue($config->get('display.widget.display_options.filters.name.exposed'));
    $this->assertSame('ignore', $config->get('display.widget.display_options.arguments.bundle.default_action'));
  }

}
