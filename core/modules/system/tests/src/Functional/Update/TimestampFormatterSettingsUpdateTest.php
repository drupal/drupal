<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Assert;

/**
 * Tests the update of timestamp formatter settings.
 *
 * @group system
 * @group legacy
 */
class TimestampFormatterSettingsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../../../layout_builder/tests/fixtures/update/layout-builder.php',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal.timestamp-formatter-settings-2921810.php',
    ];
  }

  /**
   * Tests the update of timestamp formatter settings.
   *
   * @covers \system_post_update_timestamp_formatter
   * @covers \views_post_update_timestamp_formatter
   * @covers \layout_builder_post_update_timestamp_formatter
   */
  public function testPostUpdateTimestampFormatter(): void {
    $config_factory = \Drupal::configFactory();

    $test_cases = [
      // Timestamp formatter in entity view display.
      'content.field_foo.settings' => 'core.entity_view_display.node.page.default',
      // Timestamp formatter in view.
      'display.default.display_options.fields.changed.settings' => 'views.view.content',
      // Timestamp formatter in Layout Builder field block.
      'third_party_settings.layout_builder.sections.0.components.93bf4359-06a6-4263-bce9-15c90dc8f357.configuration.formatter.settings' => 'core.entity_view_display.node.page.default',
    ];

    foreach ($test_cases as $config_path => $config_name) {
      // Check that 'tooltip' and 'time_diff' are missing before update.
      $settings = $config_factory->get($config_name)->get($config_path);
      Assert::assertArrayNotHasKey('tooltip', $settings);
      Assert::assertArrayNotHasKey('time_diff', $settings);
    }

    $this->runUpdates();

    foreach ($test_cases as $config_path => $config_name) {
      // Check that 'tooltip' and 'time_diff' were created after update.
      $settings = $config_factory->get($config_name)->get($config_path);
      Assert::assertArrayHasKey('tooltip', $settings);
      // Check that 'tooltip' is disabled for existing formatters.
      Assert::assertSame([
        'date_format' => '',
        'custom_date_format' => '',
      ], $settings['tooltip']);
      Assert::assertArrayHasKey('time_diff', $settings);
    }
  }

}
