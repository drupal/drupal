<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_ui\Traits;

/**
 * Provides common methods for Menu UI module tests.
 */
trait MenuUiTrait {

  /**
   * Asserts that a menu fetched from the database matches an expected one.
   *
   * @param array $expected_item
   *   Array containing properties to check.
   * @param int $menu_plugin_id
   *   Menu item id.
   */
  protected function assertMenuLink(array $expected_item, $menu_plugin_id) {
    // Retrieve the menu link.
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $menu_link_manager->resetDefinitions();
    // Reset the static load cache.
    \Drupal::entityTypeManager()->getStorage('menu_link_content')->resetCache();
    $definition = $menu_link_manager->getDefinition($menu_plugin_id);

    $entity = NULL;

    // Pull the path from the menu link content.
    if (str_starts_with($menu_plugin_id, 'menu_link_content')) {
      [, $uuid] = explode(':', $menu_plugin_id, 2);
      /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $entity */
      $entity = \Drupal::service('entity.repository')
        ->loadEntityByUuid('menu_link_content', $uuid);
    }

    if (isset($expected_item['children'])) {
      $child_ids = array_values($menu_link_manager->getChildIds($menu_plugin_id));
      sort($expected_item['children']);
      if ($child_ids) {
        sort($child_ids);
      }
      $this->assertSame($expected_item['children'], $child_ids);
      unset($expected_item['children']);
    }

    if (isset($expected_item['parents'])) {
      $parent_ids = array_values($menu_link_manager->getParentIds($menu_plugin_id));
      $this->assertSame($expected_item['parents'], $parent_ids);
      unset($expected_item['parents']);
    }

    if (isset($expected_item['langcode']) && $entity) {
      $this->assertEquals($expected_item['langcode'], $entity->langcode->value);
      unset($expected_item['langcode']);
    }

    if (isset($expected_item['enabled']) && $entity) {
      $this->assertEquals($expected_item['enabled'], $entity->enabled->value);
      unset($expected_item['enabled']);
    }

    foreach ($expected_item as $key => $value) {
      $this->assertNotNull($definition[$key]);
      $this->assertSame($value, $definition[$key]);
    }
  }

}
