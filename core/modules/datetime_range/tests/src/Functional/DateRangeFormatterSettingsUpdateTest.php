<?php

declare(strict_types=1);

namespace Drupal\Tests\datetime_range\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the update path for daterange formatter settings.
 *
 * @group datetime
 */
class DateRangeFormatterSettingsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'datetime',
    'datetime_range',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../fixtures/update/drupal.daterange-formatter-settings-2827055.php',
    ];
  }

  /**
   * Tests update path for the 'from_to' formatter setting.
   *
   * @covers \datetime_range_post_update_from_to_configuration
   */
  public function testPostUpdateDateRangeFormatter(): void {
    $config_factory = \Drupal::configFactory();
    // Check that 'from_to' is missing before update.
    $settings = $config_factory->get('core.entity_view_display.node.page.default')->get('content.field_datetime_range.settings');
    $this->assertArrayNotHasKey('from_to', $settings);

    $this->runUpdates();

    $settings = $config_factory->get('core.entity_view_display.node.page.default')->get('content.field_datetime_range.settings');
    $this->assertArrayHasKey('from_to', $settings);
  }

}
