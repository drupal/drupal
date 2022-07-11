<?php

namespace Drupal\Tests\ckeditor\Functional\Update;

use Drupal\editor\Entity\Editor;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the update path for CKEditor plugin settings for disabled plugins.
 *
 * @group Update
 */
class CKEditorUpdateOmitDisabledPluginSettings extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.3.0.filled.standard.php.gz',
    ];
  }

  /**
   * Ensure settings for disabled CKEditor 4 plugins are omitted on post update.
   */
  public function testUpdateUpdateOmitDisabledSettingsPostUpdate() {
    $editor = Editor::load('basic_html');
    $settings = $editor->getSettings();
    $this->assertArrayHasKey('stylescombo', $settings['plugins']);

    $this->runUpdates();

    $editor = Editor::load('basic_html');
    $settings = $editor->getSettings();
    $this->assertArrayNotHasKey('stylescombo', $settings['plugins']);
  }

  /**
   * Ensure settings for disabled CKEditor 4 plugins are omitted on entity save.
   */
  public function testUpdateUpdateOmitDisabledSettingsEntitySave() {
    $editor = Editor::load('basic_html');
    $settings = $editor->getSettings();
    $this->assertArrayHasKey('stylescombo', $settings['plugins']);
    $editor->save();

    $editor = Editor::load('basic_html');
    $settings = $editor->getSettings();
    $this->assertArrayNotHasKey('stylescombo', $settings['plugins']);
  }

}
