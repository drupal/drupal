<?php

namespace Drupal\Tests\menu_link_content\Kernel\Migrate\d7;

use Drupal\Tests\menu_link_content\Kernel\Migrate\MigrateMenuLinkTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests Menu link translation migration.
 *
 * @group migrate_drupal_7
 */
class MigrateMenuLinkTranslationTest extends MigrateDrupal7TestBase {

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
      'd7_menu_links_translation',
    ]);
  }

  /**
   * Tests migration of menu link translations.
   */
  public function testMenuLinkTranslation() {
    $this->assertEntity(467, 'fr', 'fr - Google', 'menu-test-menu', 'fr - Google description', TRUE, FALSE, ['attributes' => ['title' => 'Google']], 'http://google.com', 0);
  }

}
