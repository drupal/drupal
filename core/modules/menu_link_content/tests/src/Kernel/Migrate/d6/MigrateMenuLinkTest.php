<?php

namespace Drupal\Tests\menu_link_content\Kernel\Migrate\d6;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\Tests\node\Kernel\Migrate\d6\MigrateNodeTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Menu link migration.
 *
 * @group migrate_drupal_6
 */
class MigrateMenuLinkTest extends MigrateNodeTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
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
      'd6_language_content_settings',
      'd6_node',
      'd6_node_translation',
      'd6_menu',
      'd6_menu_links',
      'node_translation_menu_links',
    ]);
  }

  /**
   * Asserts various aspects of a menu link entity.
   *
   * @param string $id
   *   The link ID.
   * @param string $title
   *   The expected title of the link.
   * @param string $menu
   *   The expected ID of the menu to which the link will belong.
   * @param string|null $description
   *   The link's expected description.
   * @param bool $enabled
   *   Whether the link is enabled.
   * @param bool $expanded
   *   Whether the link is expanded.
   * @param array $attributes
   *   Additional attributes the link is expected to have.
   * @param string $uri
   *   The expected URI of the link.
   * @param int $weight
   *   The expected weight of the link.
   *
   * @internal
   */
  protected function assertEntity(string $id, string $title, string $menu, ?string $description, bool $enabled, bool $expanded, array $attributes, string $uri, int $weight): void {
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
    $menu_link = MenuLinkContent::load($id);
    $this->assertInstanceOf(MenuLinkContentInterface::class, $menu_link);
    $this->assertSame($title, $menu_link->getTitle());
    $this->assertSame($menu, $menu_link->getMenuName());
    $this->assertSame($description, $menu_link->getDescription());
    $this->assertSame($enabled, $menu_link->isEnabled());
    $this->assertSame($expanded, $menu_link->isExpanded());
    $this->assertSame($attributes, $menu_link->link->options);
    $this->assertSame($uri, $menu_link->link->uri);
    $this->assertSame($weight, $menu_link->getWeight());
  }

  /**
   * Tests migration of menu links.
   */
  public function testMenuLinks() {
    $this->assertEntity('138', 'Test 1', 'secondary-links', 'Test menu link 1', TRUE, FALSE, ['attributes' => ['title' => 'Test menu link 1'], 'langcode' => 'en'], 'internal:/user/login', -50);
    $this->assertEntity('139', 'Test 2', 'secondary-links', 'Test menu link 2', TRUE, TRUE, ['query' => ['foo' => 'bar'], 'attributes' => ['title' => 'Test menu link 2']], 'internal:/admin', -49);
    $this->assertEntity('140', 'Drupal.org', 'secondary-links', NULL, TRUE, FALSE, ['attributes' => ['title' => '']], 'https://www.drupal.org', -50);

    // Assert that missing title attributes don't stop or break migration.
    $this->assertEntity('393', 'Test 3', 'secondary-links', NULL, TRUE, FALSE, [], 'internal:/user/login', -47);

    // Test the migration of menu links for translated nodes.
    $this->assertEntity('459', 'The Real McCoy', 'primary-links', NULL, TRUE, FALSE, ['attributes' => ['title' => ''], 'alter' => TRUE], 'entity:node/10', 0);
    $this->assertEntity('460', 'Le Vrai McCoy', 'primary-links', NULL, TRUE, FALSE, ['attributes' => ['title' => ''], 'alter' => TRUE], 'entity:node/10', 0);
    $this->assertEntity('461', 'Abantu zulu', 'primary-links', NULL, TRUE, FALSE, ['attributes' => ['title' => ''], 'alter' => TRUE], 'entity:node/12', 0);
    $this->assertEntity('462', 'The Zulu People', 'primary-links', NULL, TRUE, FALSE, ['attributes' => ['title' => ''], 'alter' => TRUE], 'entity:node/12', 0);
  }

}
