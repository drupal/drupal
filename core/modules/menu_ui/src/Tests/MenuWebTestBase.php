<?php

namespace Drupal\menu_ui\Tests;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\simpletest\WebTestBase;

/**
 * Base class for menu web tests.
 */
abstract class MenuWebTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu_ui', 'menu_link_content');

  /**
   * Fetches the menu item from the database and compares it to expected item.
   *
   * @param int $menu_plugin_id
   *   Menu item id.
   * @param array $expected_item
   *   Array containing properties to verify.
   */
  function assertMenuLink($menu_plugin_id, array $expected_item) {
    // Retrieve menu link.
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $menu_link_manager->resetDefinitions();
    // Reset the static load cache.
    \Drupal::entityManager()->getStorage('menu_link_content')->resetCache();
    $definition = $menu_link_manager->getDefinition($menu_plugin_id);

    $entity = NULL;

    // Pull the path from the menu link content.
    if (strpos($menu_plugin_id, 'menu_link_content') === 0) {
      list(, $uuid) = explode(':', $menu_plugin_id, 2);
      /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $entity */
      $entity = \Drupal::entityManager()->loadEntityByUuid('menu_link_content', $uuid);
    }

    if (isset($expected_item['children'])) {
      $child_ids = array_values($menu_link_manager->getChildIds($menu_plugin_id));
      sort($expected_item['children']);
      if ($child_ids) {
        sort($child_ids);
      }
      $this->assertEqual($expected_item['children'], $child_ids);
      unset($expected_item['children']);
    }

    if (isset($expected_item['parents'])) {
      $parent_ids = array_values($menu_link_manager->getParentIds($menu_plugin_id));
      $this->assertEqual($expected_item['parents'], $parent_ids);
      unset($expected_item['parents']);
    }

    if (isset($expected_item['langcode']) && $entity) {
      $this->assertEqual($entity->langcode->value, $expected_item['langcode']);
      unset($expected_item['langcode']);
    }

    if (isset($expected_item['enabled']) && $entity) {
      $this->assertEqual($entity->enabled->value, $expected_item['enabled']);
      unset($expected_item['enabled']);
    }

    foreach ($expected_item as $key => $value) {
      $this->assertTrue(isset($definition[$key]));
      $this->assertEqual($definition[$key], $value);
    }
  }

  /**
   * Adds a menu link using the UI.
   *
   * @param string $parent
   *   Optional parent menu link id.
   * @param string $path
   *   The path to enter on the form. Defaults to the front page.
   * @param string $menu_name
   *   Menu name. Defaults to 'tools'.
   * @param bool $expanded
   *   Whether or not this menu link is expanded. Setting this to TRUE should
   *   test whether it works when we do the authenticatedUser tests. Defaults
   *   to FALSE.
   * @param string $weight
   *   Menu weight. Defaults to 0.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   A menu link entity.
   */
  public function addMenuLink($parent = '', $path = '/', $menu_name = 'tools', $expanded = FALSE, $weight = '0') {
    // View add menu link page.
    $this->drupalGet("admin/structure/menu/manage/$menu_name/add");
    $this->assertResponse(200);

    $title = '!link_' . $this->randomMachineName(16);
    $edit = array(
      'link[0][uri]' => $path,
      'title[0][value]' => $title,
      'description[0][value]' => '',
      'enabled[value]' => 1,
      'expanded[value]' => $expanded,
      'menu_parent' => $menu_name . ':' . $parent,
      'weight[0][value]' => $weight,
    );

    // Add menu link.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText('The menu link has been saved.');

    $menu_links = entity_load_multiple_by_properties('menu_link_content', array('title' => $title));

    $menu_link = reset($menu_links);
    $this->assertTrue($menu_link, 'Menu link was found in database.');
    $this->assertMenuLink($menu_link->getPluginId(), [
      'menu_name' => $menu_name,
      'children' => [],
      'parent' => $parent,
    ]);

    return $menu_link;
  }


  /**
   * Verifies a menu link using the UI.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $item
   *   Menu link.
   * @param object $item_node
   *   Menu link content node.
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $parent
   *   Parent menu link.
   * @param object $parent_node
   *   Parent menu link content node.
   */
  public function verifyMenuLink(MenuLinkContent $item, $item_node, MenuLinkContent $parent = NULL, $parent_node = NULL) {
    // View home page.
    $this->drupalGet('');
    $this->assertResponse(200);

    // Verify parent menu link.
    if (isset($parent)) {
      // Verify menu link.
      $title = $parent->getTitle();
      $this->assertLink($title, 0, 'Parent menu link was displayed');

      // Verify menu link link.
      $this->clickLink($title);
      $title = $parent_node->label();
      $this->assertTitle(t("@title | Drupal", array('@title' => $title)), 'Parent menu link link target was correct');
    }

    // Verify menu link.
    $title = $item->getTitle();
    $this->assertLink($title, 0, 'Menu link was displayed');

    // Verify menu link link.
    $this->clickLink($title);
    $title = $item_node->label();
    $this->assertTitle(t("@title | Drupal", array('@title' => $title)), 'Menu link link target was correct');
  }

}
