<?php

/**
 * @file
 * Contains \Drupal\menu_link_content\Tests\Migrate\d7\MigrateMenuLinkTest.
 */

namespace Drupal\menu_link_content\Tests\Migrate\d7;

use Drupal\Core\Database\Database;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Menu link migration.
 *
 * @group menu_link_content
 */
class MigrateMenuLinkTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('link', 'menu_ui', 'menu_link_content');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['router']);
    $this->installEntitySchema('menu_link_content');
    $this->executeMigration('menu');
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
   */
  protected function assertEntity($id, $title, $menu, $description, $enabled, $expanded, array $attributes, $uri, $weight) {
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
    $menu_link = MenuLinkContent::load($id);
    $this->assertTrue($menu_link instanceof MenuLinkContentInterface);
    $this->assertIdentical($title, $menu_link->getTitle());
    $this->assertIdentical($menu, $menu_link->getMenuName());
    // The migration sets the description of the link to the value of the
    // 'title' attribute. Bit strange, but there you go.
    $this->assertIdentical($description, $menu_link->getDescription());
    $this->assertIdentical($enabled, $menu_link->isEnabled());
    $this->assertIdentical($expanded, $menu_link->isExpanded());
    $this->assertIdentical($attributes, $menu_link->link->options);
    $this->assertIdentical($uri, $menu_link->link->uri);
    $this->assertIdentical($weight, $menu_link->getWeight());
  }

  /**
   * Tests migration of menu links.
   */
  public function testMenuLinks() {
    $this->executeMigration('d7_menu_links');
    $this->assertEntity(467, 'Google', 'menu-test-menu', 'Google', TRUE, FALSE, ['attributes' => ['title' => 'Google']], 'http://google.com', 0);
    $this->assertEntity(468, 'Yahoo', 'menu-test-menu', 'Yahoo', TRUE, FALSE, ['attributes' => ['title' => 'Yahoo']], 'http://yahoo.com', 0);
    $this->assertEntity(469, 'Bing', 'menu-test-menu', 'Bing', TRUE, FALSE, ['attributes' => ['title' => 'Bing']], 'http://bing.com', 0);
  }

  /**
   * Tests migrating a link with an undefined title attribute.
   */
  public function testUndefinedLinkTitle() {
    Database::getConnection('default', 'migrate')
      ->update('menu_links')
      ->fields(array(
        'options' => 'a:0:{}',
      ))
      ->condition('mlid', 467)
      ->execute();

    $this->executeMigration('d7_menu_links');
    $this->assertEntity(467, 'Google', 'menu-test-menu', NULL, TRUE, FALSE, [], 'http://google.com', 0);
  }

}
