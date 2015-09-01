<?php

/**
 * @file
 * Contains \Drupal\Tests\system\Unit\Plugin\migrate\source\MenuTest.
 */

namespace Drupal\Tests\system\Unit\Plugin\migrate\source;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests menu source plugin.
 *
 * @group system
 */
class MenuTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\system\Plugin\migrate\source\Menu';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'menu',
    ),
  );

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
    $this->databaseContents['menu_custom'] = $this->expectedResults;
    parent::setUp();
  }

}
