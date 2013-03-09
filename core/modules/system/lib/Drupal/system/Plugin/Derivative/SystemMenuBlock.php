<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Derivative\SystemMenuBlock.
 */

namespace Drupal\system\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Provides block plugin definitions for system menus.
 *
 * @see \Drupal\system\Plugin\block\block\SystemMenuBlock
 */
class SystemMenuBlock implements DerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
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
    return $this->derivatives;
  }

}
