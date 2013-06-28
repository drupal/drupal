<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Derivative\SystemMenuBlock.
 */

namespace Drupal\system\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;

/**
 * Provides block plugin definitions for system menus.
 *
 * @see \Drupal\system\Plugin\block\block\SystemMenuBlock
 */
class SystemMenuBlock extends DerivativeBase {
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Provide a block plugin definition for each system menu.
    foreach (menu_list_system_menus() as $menu => $name) {
      // The block deltas need to be prefixed with 'menu-', since the 'main'
      // menu would otherwise clash with the 'main' page content block.
      $menu_key = 'menu-' . $menu;
      $this->derivatives[$menu_key] = $base_plugin_definition;
      $this->derivatives[$menu_key]['delta'] = $menu_key;
      // It is possible that users changed the menu label. Fall back on the
      // built-in menu label if the entity was not found.
      $entity = entity_load('menu', $menu);
      $this->derivatives[$menu_key]['admin_label'] = !empty($entity) ? $entity->label() : $name;
      $this->derivatives[$menu_key]['cache'] = DRUPAL_NO_CACHE;
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
