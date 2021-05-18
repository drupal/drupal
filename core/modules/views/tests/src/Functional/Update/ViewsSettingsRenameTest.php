<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests renaming views module's configuration.
 *
 * @group Update
 */
class ViewsSettingsRenameTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests upgrading views settings.
   *
   * @covers \views_post_update_rename_default_display_setting
   */
  public function testRenameViewsSettings() {
    $config = $this->config('views.settings')->get('ui.show');
    $this->assertArrayHasKey('master_display', $config);
    $this->assertArrayNotHasKey('default_display', $config);
    $this->assertFalse($config['master_display']);

    // Run updates.
    $this->runUpdates();

    $config = $this->config('views.settings')->get('ui.show');
    $this->assertArrayHasKey('default_display', $config);
    $this->assertArrayNotHasKey('master_display', $config);
    $this->assertFalse($config['default_display']);
  }

}
