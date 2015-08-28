<?php

/**
 * @file
 * Contains \Drupal\Tests\system\Unit\Migrate\source\d6\MenuTest.
 */

namespace Drupal\Tests\system\Unit\Migrate\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 menu source plugin.
 *
 * @group system
 */
class MenuTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\system\Plugin\migrate\source\d6\Menu';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'test',
    // This needs to be the identifier of the actual key: cid for comment, nid
    // for node and so on.
    'source' => array(
      'plugin' => 'd6_menu',
    ),
  );

  // We need to set up the database contents; it's easier to do that below.

  protected $expectedResults = array(
    array(
      'menu_name' => 'menu-name-1',
      'title' => 'menu custom value 1',
      'description' => 'menu custom description value 1',
    ),
    array(
      'menu_name' => 'menu-name-2',
      'title' => 'menu custom value 2',
      'description' => 'menu custom description value 2',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // This array stores the database.
    foreach ($this->expectedResults as $k => $row) {
      $this->databaseContents['menu_custom'][$k] = $row;
    }
    parent::setUp();
  }

}
