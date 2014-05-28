<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\VariableMultiRowTestBase.
 */

namespace Drupal\migrate_drupal\Tests\source;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Base test class for the variable multirow source tests.
 */
class VariableMultiRowTestBase extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\VariableMultiRow';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    'id' => 'test',
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_variable_multirow',
      'variables' => array(
        'foo',
        'bar',
      ),
    ),
  );

  protected $expectedResults = array(
    array('name' => 'foo', 'value' => 1),
    array('name' => 'bar', 'value' => FALSE),
  );

  protected $databaseContents = array(
    'variable' => array(
      array('name' => 'foo', 'value' => 'i:1;'),
      array('name' => 'bar', 'value' => 'b:0;'),
    ),
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 variable multirow source functionality',
      'description' => 'Tests D6 variable multirow source plugin.',
      'group' => 'Migrate Drupal',
    );
  }
}

namespace Drupal\migrate_drupal\Tests\source;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;

class TestVariableMultiRow extends \Drupal\migrate_drupal\Plugin\migrate\source\VariableMultiRow {
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}
