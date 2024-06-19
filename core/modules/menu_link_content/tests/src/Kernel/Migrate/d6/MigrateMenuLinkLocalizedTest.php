<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_link_content\Kernel\Migrate\d6;

use Drupal\Tests\menu_link_content\Kernel\Migrate\MigrateMenuLinkTestTrait;
use Drupal\Tests\node\Kernel\Migrate\d6\MigrateNodeTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests Menu link localized translation migration.
 *
 * @group migrate_drupal_6
 */
class MigrateMenuLinkLocalizedTest extends MigrateNodeTestBase {

  use MigrateMenuLinkTestTrait;
  use UserCreationTrait;

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

    $this->setUpCurrentUser();
    $this->installEntitySchema('menu_link_content');
    $this->executeMigrations([
      'language',
      'd6_language_content_menu_settings',
      'd6_menu',
      'd6_menu_links',
      'd6_menu_links_localized',
    ]);
  }

  /**
   * Tests migration of menu link localized translations.
   */
  public function testMenuLinkLocalized(): void {
    // A localized menu link.
    $this->assertEntity('463', 'fr', 'fr - Test 1', 'secondary-links', 'fr - Test menu link 1', TRUE, FALSE, [
      'attributes' => ['title' => 'fr - Test menu link 1'],
      'langcode' => 'fr',
      'alter' => TRUE,
    ], 'internal:/user/login', -49);
  }

}
