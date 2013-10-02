<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\RebuildTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\simpletest\WebTestBase;

/**
 * Tests rebuilding the menu by setting 'menu_rebuild_needed.'
 */
class RebuildTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Menu rebuild test',
      'description' => 'Test rebuilding of menu.',
      'group' => 'Menu',
    );
  }

  /**
   * Test if the 'menu_rebuild_needed' variable triggers a menu_rebuild() call.
   */
  function testMenuRebuildByVariable() {
    // Check if 'admin' path exists.
    $admin_exists = db_query('SELECT path from {menu_router} WHERE path = :path', array(':path' => 'admin'))->fetchField();
    $this->assertEqual($admin_exists, 'admin', "The path 'admin/' exists prior to deleting.");

    // Delete the path item 'admin', and test that the path doesn't exist in the database.
    db_delete('menu_router')
      ->condition('path', 'admin')
      ->execute();
    $admin_exists = db_query('SELECT path from {menu_router} WHERE path = :path', array(':path' => 'admin'))->fetchField();
    $this->assertFalse($admin_exists, "The path 'admin/' has been deleted and doesn't exist in the database.");

    // Now we enable the rebuild variable and send a request to rebuild the menu
    // item. Now 'admin' should exist.
    \Drupal::state()->set('menu_rebuild_needed', TRUE);
    // The request should trigger the rebuild.
    $this->drupalGet('<front>');
    $admin_exists = db_query('SELECT path from {menu_router} WHERE path = :path', array(':path' => 'admin'))->fetchField();
    $this->assertEqual($admin_exists, 'admin', "The menu has been rebuilt, the path 'admin' now exists again.");
  }

}
