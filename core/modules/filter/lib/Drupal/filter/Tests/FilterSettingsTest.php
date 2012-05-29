<?php

/**
 * @file
 * Definition of Drupal\filter\Tests\FilterSettingsTest.
 */

namespace Drupal\filter\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests filter settings.
 */
class FilterSettingsTest extends WebTestBase {
  protected $profile = 'testing';

  public static function getInfo() {
    return array(
      'name' => 'Filter settings',
      'description' => 'Tests filter settings.',
      'group' => 'Filter',
    );
  }

  /**
   * Tests explicit and implicit default settings for filters.
   */
  function testFilterDefaults() {
    $filter_info = filter_filter_info();
    $filters = array_fill_keys(array_keys($filter_info), array());

    // Create text format using filter default settings.
    $filter_defaults_format = (object) array(
      'format' => 'filter_defaults',
      'name' => 'Filter defaults',
      'filters' => $filters,
    );
    filter_format_save($filter_defaults_format);

    // Verify that default weights defined in hook_filter_info() were applied.
    $saved_settings = array();
    foreach ($filter_defaults_format->filters as $name => $settings) {
      $expected_weight = (isset($filter_info[$name]['weight']) ? $filter_info[$name]['weight'] : 0);
      $this->assertEqual($settings['weight'], $expected_weight, format_string('@name filter weight %saved equals %default', array(
        '@name' => $name,
        '%saved' => $settings['weight'],
        '%default' => $expected_weight,
      )));
      $saved_settings[$name]['weight'] = $expected_weight;
    }

    // Re-save the text format.
    filter_format_save($filter_defaults_format);
    // Reload it from scratch.
    filter_formats_reset();
    $filter_defaults_format = filter_format_load($filter_defaults_format->format);
    $filter_defaults_format->filters = filter_list_format($filter_defaults_format->format);

    // Verify that saved filter settings have not been changed.
    foreach ($filter_defaults_format->filters as $name => $settings) {
      $this->assertEqual($settings->weight, $saved_settings[$name]['weight'], format_string('@name filter weight %saved equals %previous', array(
        '@name' => $name,
        '%saved' => $settings->weight,
        '%previous' => $saved_settings[$name]['weight'],
      )));
    }
  }
}
