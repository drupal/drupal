<?php

namespace Drupal\Tests\menu_link_content\Kernel\Migrate\d7;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Menu link migration.
 *
 * @group menu_link_content
 */
class MigrateMenuLinkTest extends MigrateDrupal7TestBase {
  const MENU_NAME = 'menu-test-menu';

  /**
   * {@inheritdoc}
   */
  public static $modules = array('link', 'menu_ui', 'menu_link_content', 'node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('node');
    $node = Node::create([
      'nid' => 3,
      'title' => 'node link test',
      'type' => 'article',
    ]);
    $node->save();
    $this->executeMigrations(['d7_menu', 'd7_menu_links']);
    \Drupal::service('router.builder')->rebuild();
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
   * @param string $description
   *   The link's expected description.
   * @param bool $enabled
   *   Whether the link is enabled.
   * @param bool $expanded
   *   Whether the link is expanded
   * @param array $attributes
   *   Additional attributes the link is expected to have.
   * @param string $uri
   *   The expected URI of the link.
   * @param int $weight
   *   The expected weight of the link.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface
   *   The menu link content.
   */
  protected function assertEntity($id, $title, $menu, $description, $enabled, $expanded, array $attributes, $uri, $weight) {
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
    $menu_link = MenuLinkContent::load($id);
    $this->assertTrue($menu_link instanceof MenuLinkContentInterface);
    $this->assertSame($title, $menu_link->getTitle());
    $this->assertSame($menu, $menu_link->getMenuName());
    // The migration sets the description of the link to the value of the
    // 'title' attribute. Bit strange, but there you go.
    $this->assertSame($description, $menu_link->getDescription());
    $this->assertSame($enabled, $menu_link->isEnabled());
    $this->assertSame($expanded, $menu_link->isExpanded());
    $this->assertSame($attributes, $menu_link->link->options);
    $this->assertSame($uri, $menu_link->link->uri);
    $this->assertSame($weight, $menu_link->getWeight());
    return $menu_link;
  }

  /**
   * Tests migration of menu links.
   */
  public function testMenuLinks() {
    $this->assertEntity(469, 'Bing', static::MENU_NAME, 'Bing', TRUE, FALSE, ['attributes' => ['title' => 'Bing']], 'http://bing.com', 0);
    $this->assertEntity(467, 'Google', static::MENU_NAME, 'Google', TRUE, FALSE, ['attributes' => ['title' => 'Google']], 'http://google.com', 0);
    $this->assertEntity(468, 'Yahoo', static::MENU_NAME, 'Yahoo', TRUE, FALSE, ['attributes' => ['title' => 'Yahoo']], 'http://yahoo.com', 0);
    // Tests migrating an external link with an undefined title attribute.
    $this->assertEntity(470, 'Ask', static::MENU_NAME, NULL, TRUE, FALSE, [], 'http://ask.com', 0);
    $this->assertEntity(245, 'Home', 'main', NULL, TRUE, FALSE, [], 'internal:/', 0);
    $this->assertEntity(478, 'custom link test', 'admin', NULL, TRUE, FALSE, ['attributes' => ['title' => '']], 'internal:/admin/content/book', 0);
    $this->assertEntity(479, 'node link test', 'tools', 'node 3', TRUE, FALSE, ['attributes' => ['title' => 'node 3']], 'entity:node/3', 3);

    $menu_link_tree_service = \Drupal::service('menu.link_tree');
    $parameters = new MenuTreeParameters();
    $tree = $menu_link_tree_service->load(static::MENU_NAME, $parameters);
    $this->assertCount(2, $tree);
    $children = 0;
    $google_found = FALSE;
    foreach ($tree as $menu_link_tree_element) {
      $children += $menu_link_tree_element->hasChildren;
      if ($menu_link_tree_element->link->getUrlObject()->toString() == 'http://bing.com') {
        $this->assertEquals(reset($menu_link_tree_element->subtree)->link->getUrlObject()->toString(), 'http://google.com');
        $google_found = TRUE;
      }
    }
    $this->assertEquals(1, $children);
    $this->assertTrue($google_found);
    // Now find the custom link under a system link.
    $parameters->root = 'system.admin_structure';
    $tree = $menu_link_tree_service->load(static::MENU_NAME, $parameters);
    $found = FALSE;
    foreach ($tree as $menu_link_tree_element) {
      $this->assertTrue($menu_link_tree_element->link->getUrlObject()->toString());
      if ($menu_link_tree_element->link->getTitle() == 'custom link test') {
        $found = TRUE;
        break;
      }
    }
    $this->assertTrue($found);
  }

}
