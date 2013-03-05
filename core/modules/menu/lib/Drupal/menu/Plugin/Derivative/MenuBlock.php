<?php

/**
 * @file
 * Contains \Drupal\menu\Plugin\Derivative\MenuBlock.
 */

namespace Drupal\menu\Plugin\Derivative;

use Drupal\system\Plugin\Derivative\SystemMenuBlock;

/**
 * Provides block plugin definitions for custom menus.
 *
 * @see \Drupal\menu\Plugin\block\block\MenuBlock
 */
class MenuBlock extends SystemMenuBlock {

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Provide block plugin definitions for all user-defined (custom) menus.
    foreach (menu_get_menus(FALSE) as $menu => $name) {
      $this->derivatives[$menu] = $base_plugin_definition;
      $this->derivatives[$menu]['admin_label'] = $name;
      $this->derivatives[$menu]['cache'] = DRUPAL_NO_CACHE;
    }
    return $this->derivatives;
  }

}
