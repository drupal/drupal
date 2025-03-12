<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional\Formatter;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for video formatters.
 *
 * @see file_post_update_add_playsinline()
 *
 * @group Update
 * @group legacy
 */
class FileVideoFormatterUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'media'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/file_post_update_playsinline-3046152.php',
    ];
  }

  /**
   * @covers \file_post_update_add_playsinline
   */
  public function testPlaysInlineUpdate(): void {
    $display = $this->config('core.entity_view_display.node.article.default');

    $settings = $display->get('content.field_video.settings');
    $this->assertArrayNotHasKey('playsinline', $settings);

    $this->runUpdates();

    $display = $this->config('core.entity_view_display.node.article.default');
    $settings = $display->get('content.field_video.settings');

    $this->assertFalse($settings['playsinline']);
  }

}
