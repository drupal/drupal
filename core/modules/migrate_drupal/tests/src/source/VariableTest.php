<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\VariableTest.
 */

namespace Drupal\migrate_drupal\Tests\source;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * @group migrate_drupal
 * @group Drupal
 */
class VariableTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\Variable';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'highwaterProperty' => array('field' => 'test'),
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_variable',
      'variables' => array(
        'foo',
        'bar',
      ),
    ),
  );

  protected $expectedResults = array(
    array(
      'foo' => 1,
      'bar' => FALSE,
    ),
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
      'name' => 'Variable source functionality',
      'description' => 'Tests variable source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

}

namespace Drupal\migrate_drupal\Tests\source;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;

class TestVariable extends \Drupal\migrate_drupal\Plugin\migrate\source\Variable {
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}
