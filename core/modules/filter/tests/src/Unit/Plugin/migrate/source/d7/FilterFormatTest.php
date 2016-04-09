<?php

namespace Drupal\Tests\filter\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests d7_filter_format source plugin.
 *
 * @group filter
 */
class FilterFormatTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\filter\Plugin\migrate\source\d7\FilterFormat';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_filter_formats',
    ),
  );

  protected $expectedResults = array(
    array(
      'format' => 'custom_text_format',
      'name' => 'Custom Text format',
      'cache' => 1,
      'status' => 1,
      'weight' => 0,
      'filters' => array(
        'filter_autop' => array(
          'module' => 'filter',
          'name' => 'filter_autop',
          'weight' => 0,
          'status' => 1,
          'settings' => array(),
        ),
        'filter_html' => array(
          'module' => 'filter',
          'name' => 'filter_html',
          'weight' => 1,
          'status' => 1,
          'settings' => array(),
        ),
      ),
    ),
    array(
      'format' => 'full_html',
      'name' => 'Full HTML',
      'cache' => 1,
      'status' => 1,
      'weight' => 1,
      'filters' => array(
        'filter_url' => array(
          'module' => 'filter',
          'name' => 'filter_url',
          'weight' => 0,
          'status' => 1,
          'settings' => array(),
        ),
      ),
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $row) {
      foreach ($row['filters'] as $filter) {
        $filter['format'] = $row['format'];
        $filter['settings'] = serialize($filter['settings']);
        $this->databaseContents['filter'][] = $filter;
      }
      unset($row['filters']);
      $this->databaseContents['filter_format'][] = $row;
    }
    parent::setUp();
  }

}
