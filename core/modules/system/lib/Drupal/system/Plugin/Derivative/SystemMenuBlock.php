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
      $menu = "menu-$menu";
      $this->derivatives[$menu] = $base_plugin_definition;
      $this->derivatives[$menu]['delta'] = $menu;
      $this->derivatives[$menu]['subject'] = $name;
      $this->derivatives[$menu]['cache'] = DRUPAL_NO_CACHE;
    }
    return $this->derivatives;
  }

}
