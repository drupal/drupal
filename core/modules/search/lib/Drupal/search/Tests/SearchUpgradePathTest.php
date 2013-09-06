<?php

/**
 * @file
 * Contains \Drupal\search\Tests\SearchUpgradePathTest.
 */

namespace Drupal\search\Tests;

use Drupal\system\Tests\Upgrade\UpgradePathTestBase;

/**
 * Tests the upgrade path of search configuration and tables.
 */
class SearchUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Search module upgrade test',
      'description' => 'Upgrade tests for search module configuration and tables.',
      'group' => 'Search',
    );
  }

  public function setUp() {
    // Path to the database dump files.
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'search') . '/tests/upgrade/drupal-7.search.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests to see if search configuration and tables were upgraded.
   */
  public function testSearchUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');
    $this->assertFalse(db_table_exists('search_node_links'), 'search_node_links table was dropped.');
    $config = \Drupal::config('search.settings');
     // The starting database has user module as the only active search.
    $this->assertEqual($config->get('active_plugins'), array('user_search' => 'user_search'));
    $exists = db_query('SELECT 1 FROM {variable} WHERE name = :name', array(':name' => 'search_active_modules'))->fetchField();
    $this->assertFalse($exists, 'The search_active_modules variable was deleted by the update');
    // The starting database has user module as the default.
    $this->assertEqual($config->get('default_plugin'), 'user_search');
  }

}
