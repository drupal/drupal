<?php

namespace Drupal\Tests\menu_link_content\Kernel\Migrate\d7;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Tests\menu_link_content\Kernel\Migrate\MigrateMenuLinkTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Menu link migration.
 *
 * @group menu_link_content
 */
class MigrateMenuLinkTest extends MigrateDrupal7TestBase {
  const MENU_NAME = 'menu-test-menu';

  use UserCreationTrait;
  use MigrateMenuLinkTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'link',
    'menu_ui',
    'menu_link_content',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpCurrentUser();
    $this->installEntitySchema('menu_link_content');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(static::$modules);

    $this->migrateUsers(FALSE);
    $this->migrateContentTypes();
    $this->executeMigrations([
      'language',
      'd7_language_content_settings',
      'd7_node',
      'd7_node_translation',
      'd7_menu',
      'd7_menu_links',
      'node_translation_menu_links',
    ]);
  }

  /**
   * Tests migration of menu links.
   */
  public function testMenuLinks() {
    $this->assertEntity(469, 'und', 'Bing', static::MENU_NAME, 'Bing', TRUE, FALSE, ['attributes' => ['title' => 'Bing']], 'http://bing.com', 0);
    // This link has an i18n translation so the language is changed to the
    // default language of the source site.
    $this->assertEntity(467, 'en', 'Google', static::MENU_NAME, 'Google', TRUE, FALSE, ['attributes' => ['title' => 'Google']], 'http://google.com', 0);
    $this->assertEntity(468, 'en', 'Yahoo', static::MENU_NAME, 'english description', TRUE, FALSE, ['attributes' => ['title' => 'english description'], 'alter' => TRUE], 'http://yahoo.com', 0);

    // Tests migrating an external link with an undefined title attribute.
    $this->assertEntity(470, 'und', 'Ask', static::MENU_NAME, NULL, TRUE, FALSE, [], 'http://ask.com', 0);

    $this->assertEntity(245, 'und', 'Home', 'main', NULL, TRUE, FALSE, [], 'internal:/', 0);
    $this->assertEntity(478, 'und', 'custom link test', 'admin', NULL, TRUE, FALSE, ['attributes' => ['title' => '']], 'internal:/admin/content', 0);
    $this->assertEntity(479, 'und', 'node link test', 'tools', 'node 2', TRUE, FALSE, [
      'attributes' => ['title' => 'node 2'],
      'query' => [
        'name' => 'ferret',
        'color' => 'purple',
      ],
    ],
      'entity:node/2', 3);

    $menu_link_tree_service = \Drupal::service('menu.link_tree');
    $parameters = new MenuTreeParameters();
    $tree = $menu_link_tree_service->load(static::MENU_NAME, $parameters);
    $this->assertCount(2, $tree);
    $children = 0;
    $google_found = FALSE;
    foreach ($tree as $menu_link_tree_element) {
      $children += $menu_link_tree_element->hasChildren;
      if ($menu_link_tree_element->link->getUrlObject()->toString() == 'http://bing.com') {
        $this->assertEquals('http://google.com', reset($menu_link_tree_element->subtree)->link->getUrlObject()->toString());
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
      $this->assertNotEmpty($menu_link_tree_element->link->getUrlObject()->toString());
      if ($menu_link_tree_element->link->getTitle() == 'custom link test') {
        $found = TRUE;
        break;
      }
    }
    $this->assertTrue($found);

    // Test the migration of menu links for translated nodes.
    $this->assertEntity(484, 'und', 'The thing about Deep Space 9', 'tools', NULL, TRUE, FALSE, ['attributes' => ['title' => '']], 'entity:node/2', 9);
    $this->assertEntity(485, 'en', 'is - The thing about Deep Space 9', 'tools', NULL, TRUE, FALSE, ['attributes' => ['title' => '']], 'entity:node/2', 10);
    $this->assertEntity(486, 'und', 'is - The thing about Firefly', 'tools', NULL, TRUE, FALSE, ['attributes' => ['title' => '']], 'entity:node/4', 11);
    $this->assertEntity(487, 'en', 'en - The thing about Firefly', 'tools', NULL, TRUE, FALSE, ['attributes' => ['title' => '']], 'entity:node/4', 12);

    // Test there have been no attempts to stub a shortcut in a MigrationLookup
    // process.
    $messages = $this->getMigration('d7_menu')->getIdMap()->getMessages()->fetchAll();
    $this->assertCount(0, $messages);
  }

}
