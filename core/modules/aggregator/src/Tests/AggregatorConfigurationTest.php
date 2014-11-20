<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\AggregatorConfigurationTest.
 */

namespace Drupal\aggregator\Tests;

/**
 * Tests aggregator settings page.
 *
 * @group aggregator
 */
class AggregatorConfigurationTest extends AggregatorTestBase {
  /**
   * Tests the settings form to ensure the correct default values are used.
   */
  function testSettingsPage() {
    $this->drupalGet('admin/config');
    $this->clickLink('Feed aggregator');
    $this->clickLink('Settings');
    // Make sure that test plugins are present.
    $this->assertText('Test fetcher');
    $this->assertText('Test parser');
    $this->assertText('Test processor');

    // Set new values and enable test plugins.
    $edit = array(
      'aggregator_allowed_html_tags' => '<a>',
      'aggregator_summary_items' => 10,
      'aggregator_clear' => 3600,
      'aggregator_teaser_length' => 200,
      'aggregator_fetcher' => 'aggregator_test_fetcher',
      'aggregator_parser' => 'aggregator_test_parser',
      'aggregator_processors[aggregator_test_processor]' => 'aggregator_test_processor',
    );
    $this->drupalPostForm('admin/config/services/aggregator/settings', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    foreach ($edit as $name => $value) {
      $this->assertFieldByName($name, $value, format_string('"@name" has correct default value.', array('@name' => $name)));
    }

    // Check for our test processor settings form.
    $this->assertText(t('Dummy length setting'));
    // Change its value to ensure that settingsSubmit is called.
    $edit = array(
      'dummy_length' => 100,
    );
    $this->drupalPostForm('admin/config/services/aggregator/settings', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));
    $this->assertFieldByName('dummy_length', 100, '"dummy_length" has correct default value.');

    // Make sure settings form is still accessible even after uninstalling a module
    // that provides the selected plugins.
    $this->container->get('module_handler')->uninstall(array('aggregator_test'));
    $this->resetAll();
    $this->drupalGet('admin/config/services/aggregator/settings');
    $this->assertResponse(200);
  }
}
