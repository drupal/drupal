<?php

namespace Drupal\Tests\media_library\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the media library module updates for the view page display links.
 *
 * @group media_library
 * @group legacy
 */
class MediaLibraryUpdateViewStatusExtraFilterTest extends UpdatePathTestBase {

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
   * Tests that the status extra filter is added to the media library view.
   *
   * @see media_library_post_update_add_status_extra_filter()
   */
  public function testMediaLibraryViewStatusExtraFilter() {
    $config = $this->config('views.view.media_library');
    $this->assertNull($config->get('display.default.display_options.filters.status_extra'));

    $this->runUpdates();

    $config = $this->config('views.view.media_library');
    $filter = $config->get('display.default.display_options.filters.status_extra');
    $this->assertInternalType('array', $filter);
    $this->assertSame('status_extra', $filter['field']);
    $this->assertSame('media', $filter['entity_type']);
    $this->assertSame('media_status', $filter['plugin_id']);
    $this->assertSame('status_extra', $filter['id']);
    $this->assertFalse($filter['exposed']);
  }

}
