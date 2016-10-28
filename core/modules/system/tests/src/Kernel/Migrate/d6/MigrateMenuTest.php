<?php

namespace Drupal\Tests\system\Kernel\Migrate\d6;

use Drupal\Core\Database\Database;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
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
    $this->assertSame('navigation', $navigation_menu->id());
    $this->assertSame('Navigation', $navigation_menu->label());
    $expected = <<<EOT
The navigation menu is provided by Drupal and is the main interactive menu for any site. It is usually the only menu that contains personalized links for authenticated users, and is often not even visible to anonymous users.
EOT;
    $this->assertSame($expected, $navigation_menu->getDescription());

    // Test that we can re-import using the ConfigEntityBase destination.
    Database::getConnection('default', 'migrate')
      ->update('menu_custom')
      ->fields(array('title' => 'Home Navigation'))
      ->condition('menu_name', 'navigation')
      ->execute();

    $migration = $this->getMigration('d6_menu');
    \Drupal::database()
        ->truncate($migration->getIdMap()->mapTableName())
        ->execute();
    $this->executeMigration($migration);

    $navigation_menu = Menu::load('navigation');
    $this->assertSame('Home Navigation', $navigation_menu->label());
  }

}
