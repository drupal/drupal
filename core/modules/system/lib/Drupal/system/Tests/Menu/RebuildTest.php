<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\RebuildTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests rebuilding the router.
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
   * Tests that set a router rebuild needed works.
   */
  function testMenuRebuild() {
    // Check if 'admin' path exists.
    $admin_exists = db_query('SELECT path from {menu_router} WHERE path = :path', array(':path' => 'admin'))->fetchField();
    $this->assertEqual($admin_exists, 'admin', "The path 'admin/' exists prior to deleting.");

    // Delete the path item 'admin', and test that the path doesn't exist in the database.
    db_delete('menu_router')
      ->condition('path', 'admin')
      ->execute();
    $admin_exists = db_query('SELECT path from {menu_router} WHERE path = :path', array(':path' => 'admin'))->fetchField();
    $this->assertFalse($admin_exists, "The path 'admin/' has been deleted and doesn't exist in the database.");

    // Now we set the router to be rebuilt. After the rebuild 'admin' should exist.
    \Drupal::service('router.builder')->setRebuildNeeded();

    // The request should trigger the rebuild.
    $this->drupalGet('<front>');
    $admin_exists = db_query('SELECT path from {menu_router} WHERE path = :path', array(':path' => 'admin'))->fetchField();
    $this->assertEqual($admin_exists, 'admin', "The menu has been rebuilt, the path 'admin' now exists again.");
  }

}
