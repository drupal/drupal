<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Migrate\d6\MigrateMenuTest.
 */

namespace Drupal\system\Tests\Migrate\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\Core\Database\Database;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\system\Entity\Menu;

/**
 * Upgrade menus to system.menu.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateMenuTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_menu');
  }

  /**
   * Tests the Drupal 6 menu to Drupal 8 migration.
   */
  public function testMenu() {
    $navigation_menu = Menu::load('navigation');
    $this->assertIdentical('navigation', $navigation_menu->id());
    $this->assertIdentical('Navigation', $navigation_menu->label());
    $expected = <<<EOT
The navigation menu is provided by Drupal and is the main interactive menu for any site. It is usually the only menu that contains personalized links for authenticated users, and is often not even visible to anonymous users.
EOT;
    $this->assertIdentical($expected, $navigation_menu->getDescription());

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
    $this->assertIdentical('Home Navigation', $navigation_menu->label());
  }

}
