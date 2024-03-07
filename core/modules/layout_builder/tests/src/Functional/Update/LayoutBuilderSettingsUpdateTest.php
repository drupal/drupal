<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests creation of layout_builder settings.
 *
 * @see layout_builder_post_update_default_expose_field_block_setting()
 *
 * @group Update
 */
class LayoutBuilderSettingsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/layout-builder.php',
    ];
  }

  /**
   * Tests layout_builder_post_update_default_expose_field_block_setting().
   */
  public function testLayoutBuilderPostUpdateExposeFieldBlockSetting(): void {
    // Ensure config is not present.
    $config = $this->config('layout_builder.settings');
    $this->assertTrue($config->isNew());

    $this->runUpdates();

    // Ensure config is present and setting is enabled.
    $updated_config = $this->config('layout_builder.settings');
    $this->assertFalse($updated_config->isNew());
    $this->assertTrue($updated_config->get('expose_all_field_blocks'));
  }

}
