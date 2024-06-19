<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_link_content\Kernel\Migrate\d6;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Menu link migration.
 *
 * @group migrate_drupal_6
 */
class MigrateMenuLinkTranslationTest extends MigrateDrupal6TestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_ui',
    'menu_link_content',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrateContent();
    $this->setUpCurrentUser();
    $this->installEntitySchema('menu_link_content');
    $this->executeMigrations([
      'language',
      'd6_menu',
      'd6_menu_links',
      'd6_menu_links_translation',
    ]);
  }

  /**
   * Tests migration of menu links.
   */
  public function testMenuLinks(): void {
    /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link */
    $menu_link = MenuLinkContent::load(139)->getTranslation('fr');
    $this->assertInstanceOf(MenuLinkContent::class, $menu_link);
    $this->assertSame('fr - Test 2', $menu_link->getTitle());
    $this->assertSame('fr - Test menu link 2', $menu_link->getDescription());
    $this->assertSame('secondary-links', $menu_link->getMenuName());
    $this->assertTrue($menu_link->isEnabled());
    $this->assertTrue($menu_link->isExpanded());
    $this->assertSame(['query' => ['foo' => 'bar'], 'attributes' => ['title' => 'Test menu link 2']], $menu_link->link->options);
    $this->assertSame('internal:/admin', $menu_link->link->uri);
    $this->assertSame(-49, $menu_link->getWeight());

    $menu_link = MenuLinkContent::load(139)->getTranslation('zu');
    $this->assertInstanceOf(MenuLinkContent::class, $menu_link);
    $this->assertSame('Test 2', $menu_link->getTitle());
    $this->assertSame('zu - Test menu link 2', $menu_link->getDescription());
    $this->assertSame('secondary-links', $menu_link->getMenuName());
    $this->assertTrue($menu_link->isEnabled());
    $this->assertTrue($menu_link->isExpanded());
    $this->assertSame(['query' => ['foo' => 'bar'], 'attributes' => ['title' => 'Test menu link 2']], $menu_link->link->options);
    $this->assertSame('internal:/admin', $menu_link->link->uri);
    $this->assertSame(-49, $menu_link->getWeight());

    $menu_link = MenuLinkContent::load(140)->getTranslation('fr');
    $this->assertInstanceOf(MenuLinkContent::class, $menu_link);
    $this->assertSame('fr - Drupal.org', $menu_link->getTitle());
    $this->assertSame('', $menu_link->getDescription());
    $this->assertSame('secondary-links', $menu_link->getMenuName());
    $this->assertTrue($menu_link->isEnabled());
    $this->assertFalse($menu_link->isExpanded());
    $this->assertSame(['attributes' => ['title' => '']], $menu_link->link->options);
    $this->assertSame('https://www.drupal.org', $menu_link->link->uri);
    $this->assertSame(-50, $menu_link->getWeight());
  }

}
