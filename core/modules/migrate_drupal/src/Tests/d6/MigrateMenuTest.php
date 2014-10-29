<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateMenuTest
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\Core\Database\Database;
use Drupal\system\Entity\Menu;

/**
 * Upgrade menus to system.menu.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateMenuTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_menu');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6Menu.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests the Drupal 6 menu to Drupal 8 migration.
   */
  public function testMenu() {
    $navigation_menu = Menu::load('navigation');
    $this->assertEqual($navigation_menu->id(), 'navigation');
    $this->assertEqual($navigation_menu->label(), 'Navigation');
    $this->assertEqual($navigation_menu->getDescription() , 'The navigation menu is provided by Drupal and is the main interactive menu for any site. It is usually the only menu that contains personalized links for authenticated users, and is often not even visible to anonymous users.');

    // Test that we can re-import using the ConfigEntityBase destination.
    Database::getConnection('default', 'migrate')
      ->update('menu_custom')
      ->fields(array('title' => 'Home Navigation'))
      ->condition('menu_name', 'navigation')
      ->execute();

    db_truncate(entity_load('migration', 'd6_menu')->getIdMap()->mapTableName())->execute();
    $migration = entity_load_unchanged('migration', 'd6_menu');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $navigation_menu = entity_load_unchanged('menu', 'navigation');
    $this->assertEqual($navigation_menu->label(), 'Home Navigation');
  }

}
