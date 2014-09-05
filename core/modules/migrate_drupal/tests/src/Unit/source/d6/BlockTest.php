<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_drupal\Unit\source\d6\BlockTest.
 */

namespace Drupal\Tests\migrate_drupal\Unit\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 block source plugin.
 *
 * @group migrate_drupal
 */
class BlockTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\Block';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'test',
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_block',
    ),
  );

  /**
   * Sample block instance query results from the source.
   */
  protected $expectedResults = array(
    array(
      'bid' => 1,
      'module' => 'block',
      'delta' => '1',
      'theme' => 'garland',
      'status' => 1,
      'weight' => 0,
      'region' => 'left',
      'visibility' => 0,
      'pages' => '',
      'title' => 'Test Title 01',
      'cache' => -1,
    ),
    array(
      'bid' => 2,
      'module' => 'block',
      'delta' => '2',
      'theme' => 'garland',
      'status' => 1,
      'weight' => 5,
      'region' => 'right',
      'visibility' => 0,
      'pages' => '<front>',
      'title' => 'Test Title 02',
      'cache' => -1,
    ),
  );

  /**
   * Sample block roles table.
   */
  protected $expectedBlocksRoles = array(
    array(
      'module' => 'block',
      'delta' => 1,
      'rid' => 2,
    ),
  );

  /**
   * Prepopulate database contents.
   */
  protected function setUp() {
    $this->databaseContents['blocks'] = $this->expectedResults;
    $this->databaseContents['blocks_roles'] = $this->expectedBlocksRoles;
    parent::setUp();
  }

}

namespace Drupal\Tests\migrate_drupal\Unit\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\Block;

class TestBlock extends Block {
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}
