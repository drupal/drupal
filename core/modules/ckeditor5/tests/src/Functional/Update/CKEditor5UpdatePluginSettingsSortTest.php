<?php

namespace Drupal\Tests\ckeditor5\Functional\Update;

use Drupal\editor\Entity\Editor;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * @covers ckeditor5_post_update_plugins_settings_export_order
 * @group Update
 * @group ckeditor5
 */
class CKEditor5UpdatePluginSettingsSortTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
    ];
  }

  /**
   * Ensure settings for CKEditor 5 plugins are sorted by plugin key.
   */
  public function testUpdatePluginSettingsSortPostUpdate(): void {
    $editor = Editor::load('basic_html');
    $settings = $editor->getSettings();
    $plugin_settings_before = array_keys($settings['plugins']);

    $this->runUpdates();

    $editor = Editor::load('basic_html');
    $settings = $editor->getSettings();
    $plugin_settings_after = array_keys($settings['plugins']);

    // Different sort before and after, but the same values.
    $this->assertNotSame($plugin_settings_before, $plugin_settings_after);
    sort($plugin_settings_before);
    $this->assertSame($plugin_settings_before, $plugin_settings_after);
  }

  /**
   * Ensure settings for CKEditor 5 plugins are sorted by plugin key.
   */
  public function testUpdatePluginSettingsSortEntitySave(): void {
    $editor = Editor::load('basic_html');
    $settings = $editor->getSettings();
    $plugin_settings_before = array_keys($settings['plugins']);

    $editor->save();

    $editor = Editor::load('basic_html');
    $settings = $editor->getSettings();
    $plugin_settings_after = array_keys($settings['plugins']);

    // Different sort before and after, but the same values.
    $this->assertNotSame($plugin_settings_before, $plugin_settings_after);
    sort($plugin_settings_before);
    $this->assertSame($plugin_settings_before, $plugin_settings_after);
  }

}
