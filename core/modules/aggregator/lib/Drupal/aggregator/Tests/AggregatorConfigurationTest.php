<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\AggregatorConfigurationTest.
 */

namespace Drupal\aggregator\Tests;

/**
 * Tests aggregator configuration settings.
 */
class AggregatorConfigurationTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Aggregator configuration',
      'description' => 'Test aggregator settings page.',
      'group' => 'Aggregator',
    );
  }

  /**
   * Tests the settings form to ensure the correct default values are used.
   */
  function testSettingsPage() {
    $edit = array(
      'aggregator_allowed_html_tags' => '<a>',
      'aggregator_summary_items' => 10,
      'aggregator_clear' => 3600,
      'aggregator_category_selector' => 'select',
      'aggregator_teaser_length' => 200,
    );
    $this->drupalPost('admin/config/services/aggregator/settings', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    foreach ($edit as $name => $value) {
      $this->assertFieldByName($name, $value, t('"@name" has correct default value.', array('@name' => $name)));
    }
  }
}
