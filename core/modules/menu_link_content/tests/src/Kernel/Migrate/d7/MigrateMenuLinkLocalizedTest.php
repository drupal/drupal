<?php

namespace Drupal\Tests\menu_link_content\Kernel\Migrate\d7;

use Drupal\Tests\menu_link_content\Kernel\Migrate\MigrateMenuLinkTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests Menu link localized translation migration.
 *
 * @group migrate_drupal_7
 */
class MigrateMenuLinkLocalizedTest extends MigrateDrupal7TestBase {

  use MigrateMenuLinkTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'link',
    'menu_link_content',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->executeMigrations(['language']);
    $this->installEntitySchema('menu_link_content');
    $this->executeMigrations([
      'd7_menu',
      'd7_language_content_menu_settings',
      'd7_menu_links',
      'd7_menu_links_localized',
    ]);
  }

  /**
   * Tests migration of menu link localized translations.
   */
  public function testMenuLinkLocalized() {
    // A translate and localize menu, menu-test-menu.
    $this->assertEntity(468, 'en', 'Yahoo', 'menu-test-menu', 'english description', TRUE, FALSE, ['attributes' => ['title' => 'english description'], 'alter' => TRUE], 'http://yahoo.com', 0);
    $this->assertEntity(468, 'fr', 'fr - Yahoo', 'menu-test-menu', 'fr - description', TRUE, FALSE, ['attributes' => ['title' => 'english description'], 'alter' => TRUE], 'http://yahoo.com', 0);
    $this->assertEntity(468, 'is', 'is - Yahoo', 'menu-test-menu', 'is - description', TRUE, FALSE, ['attributes' => ['title' => 'english description'], 'alter' => TRUE], 'http://yahoo.com', 0);
  }

}
