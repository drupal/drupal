<?php

/**
 * @file
 * Contains \Drupal\filter\Tests\FilterSettingsTest.
 */

namespace Drupal\filter\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests filter settings.
 *
 * @group filter
 */
class FilterSettingsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter');

  /**
   * Tests explicit and implicit default settings for filters.
   */
  function testFilterDefaults() {
    $filter_info = $this->container->get('plugin.manager.filter')->getDefinitions();

    // Create text format using filter default settings.
    $filter_defaults_format = entity_create('filter_format', array(
      'format' => 'filter_defaults',
      'name' => 'Filter defaults',
    ));
    $filter_defaults_format->save();

    // Verify that default weights defined in hook_filter_info() were applied.
    $saved_settings = array();
    foreach ($filter_defaults_format->filters() as $name => $filter) {
      $expected_weight = $filter_info[$name]['weight'];
      $this->assertEqual($filter->weight, $expected_weight, format_string('@name filter weight %saved equals %default', array(
        '@name' => $name,
        '%saved' => $filter->weight,
        '%default' => $expected_weight,
      )));
      $saved_settings[$name]['weight'] = $expected_weight;
    }

    // Re-save the text format.
    $filter_defaults_format->save();
    // Reload it from scratch.
    filter_formats_reset();

    // Verify that saved filter settings have not been changed.
    foreach ($filter_defaults_format->filters() as $name => $filter) {
      $this->assertEqual($filter->weight, $saved_settings[$name]['weight'], format_string('@name filter weight %saved equals %previous', array(
        '@name' => $name,
        '%saved' => $filter->weight,
        '%previous' => $saved_settings[$name]['weight'],
      )));
    }
  }
}
