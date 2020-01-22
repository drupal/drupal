<?php

namespace Drupal\Tests\media_library\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the media library module updates for the view page display links.
 *
 * @group media_library
 * @group legacy
 */
class MediaLibraryUpdateViewPageDisplayEditDeleteLinkTest extends UpdatePathTestBase {

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
   * Tests that the media library view config is updated.
   *
   * @see media_library_update_8703()
   */
  public function testMediaLibraryViewsConfig() {
    $config = $this->config('views.view.media_library');
    $this->assertNull($config->get('display.page.display_options.defaults.fields'));
    $this->assertNull($config->get('display.page.display_options.fields.name'));
    $this->assertNull($config->get('display.page.display_options.fields.edit_media'));
    $this->assertNull($config->get('display.page.display_options.fields.delete_media'));

    $this->runUpdates();

    $config = $this->config('views.view.media_library');
    $this->assertFalse($config->get('display.page.display_options.defaults.fields'));
    $this->assertSame('field', $config->get('display.page.display_options.fields.name.plugin_id'));
    $this->assertSame('name', $config->get('display.page.display_options.fields.name.entity_field'));
    $this->assertSame('entity_link_edit', $config->get('display.page.display_options.fields.edit_media.plugin_id'));
    $this->assertSame('entity_link_delete', $config->get('display.page.display_options.fields.delete_media.plugin_id'));
    // Check if the rendered entity is last in the field.
    $fields = $config->get('display.page.display_options.fields');
    end($fields);
    $this->assertSame('rendered_entity', key($fields));
  }

}
