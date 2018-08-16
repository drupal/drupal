<?php

namespace Drupal\menu_ui\Tests;

@trigger_error(__NAMESPACE__ . '\MenuWebTestBase is deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.0. Use the \Drupal\Tests\BrowserTestBase base class and the \Drupal\Tests\menu_ui\Traits\MenuUiTrait trait instead. See https://www.drupal.org/node/2917910.', E_USER_DEPRECATED);

use Drupal\simpletest\WebTestBase;

/**
 * Base class for menu web tests.
 *
 * @deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.x. Use
 *   \Drupal\Tests\menu_ui\Traits\MenuUiTrait methods, instead.
 *
 * @see https://www.drupal.org/node/2917910
 */
abstract class MenuWebTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['menu_ui', 'menu_link_content'];

  /**
   * Fetches the menu item from the database and compares it to expected item.
   *
   * @param int $menu_plugin_id
   *   Menu item id.
   * @param array $expected_item
   *   Array containing properties to verify.
   */
  public function assertMenuLink($menu_plugin_id, array $expected_item) {
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

}
