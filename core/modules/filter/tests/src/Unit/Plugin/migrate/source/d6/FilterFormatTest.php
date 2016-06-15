<?php

namespace Drupal\Tests\filter\Unit\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests d6_filter_format source plugin.
 *
 * @group filter
 */
class FilterFormatTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\filter\Plugin\migrate\source\d6\FilterFormat';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'highWaterProperty' => array('field' => 'test'),
    'source' => array(
      'plugin' => 'd6_filter_formats',
    ),
  );

  protected $expectedResults = array(
    array(
      'format' => 1,
      'name' => 'Filtered HTML',
      'roles' => array(1, 2),
      'cache' => 1,
      'filters' => array(
        array(
          'module' => 'filter',
          'delta' => 2,
          'weight' => 0,
          'settings' => array(),
        ),
        array(
          'module' => 'filter',
          'delta' => 0,
          'weight' => 1,
          'settings' => array(),
        ),
        array(
          'module' => 'filter',
          'delta' => 1,
          'weight' => 2,
          'settings' => array(),
        ),
      ),
    ),
    array(
      'format' => 2,
      'name' => 'Full HTML',
      'roles' => array(),
      'cache' => 1,
      'filters' => array(
        array(
          'module' => 'filter',
          'delta' => 2,
          'weight' => 0,
          'settings' => array(),
        ),
        array(
          'module' => 'filter',
          'delta' => 1,
          'weight' => 1,
          'settings' => array(),
        ),
        array(
          'module' => 'filter',
          'delta' => 3,
          'weight' => 10,
          'settings' => array(),
        ),
      ),
    ),
    array(
      'format' => 4,
      'name' => 'Example Custom Format',
      'roles' => array(4),
      'cache' => 1,
      'filters' => array(
        // This custom format uses a filter defined by a contrib module.
        array(
          'module' => 'markdown',
          'delta' => 1,
          'weight' => 10,
          'settings' => array(),
        ),
      ),
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $fid = 1;
    $empty_array = serialize(array());
    foreach ($this->expectedResults as $k => $row) {
      $row['roles'] = ',' . implode(',', $row['roles']) . ',';
      foreach ($row['filters'] as $filter) {
        $filter['settings'] = $empty_array;
        $this->databaseContents['filters'][$fid] = $filter;
        $this->databaseContents['filters'][$fid]['format'] = $row['format'];
        $this->databaseContents['filters'][$fid]['fid'] = $fid;
        $fid++;
      }
      unset($row['filters']);
      $this->databaseContents['filter_formats'][$k] = $row;
    }
    parent::setUp();
  }

}
