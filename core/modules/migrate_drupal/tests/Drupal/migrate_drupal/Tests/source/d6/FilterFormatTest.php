<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\FilterFormatTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Tests comment migration from D6 to D8.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class FilterFormatTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\FilterFormat';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    'id' => 'test',
    'highwaterProperty' => array('field' => 'test'),
    'idlist' => array(),
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
        ),
        array(
          'module' => 'filter',
          'delta' => 0,
          'weight' => 1,
        ),
        array(
          'module' => 'filter',
          'delta' => 1,
          'weight' => 2,
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
        ),
        array(
          'module' => 'filter',
          'delta' => 1,
          'weight' => 1,
        ),
        array(
          'module' => 'filter',
          'delta' => 3,
          'weight' => 10,
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
        ),
      ),
    ),
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 Filter Formats source functionality',
      'description' => 'Tests D6 filter_formats table source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $fid = 1;
    foreach ($this->expectedResults as $k => $row) {
      $row['roles'] = ',' . implode(',', $row['roles']) . ',';
      foreach ($row['filters'] as $filter) {
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

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\FilterFormat;

class TestFilterFormat extends FilterFormat {
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}
