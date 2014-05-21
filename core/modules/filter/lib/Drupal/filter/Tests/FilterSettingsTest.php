<?php

/**
 * @file
 * Definition of Drupal\filter\Tests\FilterSettingsTest.
 */

namespace Drupal\filter\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests filter settings.
 */
class FilterSettingsTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter');

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
    $filter_info = $this->container->get('plugin.manager.filter')->getDefinitions();
    $filters = array_fill_keys(array_keys($filter_info), array());

    // Create text format using filter default settings.
    $filter_defaults_format = entity_create('filter_format', array(
      'format' => 'filter_defaults',
      'name' => 'Filter defaults',
      'filters' => $filters,
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
