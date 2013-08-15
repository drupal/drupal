<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemMenuBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'System Menu' block.
 *
 * @Plugin(
 *   id = "system_menu_block",
 *   admin_label = @Translation("System Menu"),
 *   module = "system",
 *   category = "menu",
 *   derivative = "Drupal\system\Plugin\Derivative\SystemMenuBlock"
 * )
 */
class SystemMenuBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access() {
    // @todo The 'Tools' menu should be available to anonymous users.
    list($plugin, $derivative) = explode(':', $this->getPluginId());
    return ($GLOBALS['user']->isAuthenticated() || in_array($derivative, array('menu-main', 'menu-tools', 'menu-footer')));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    list($plugin, $derivative) = explode(':', $this->getPluginId());
    // Derivatives are prefixed with 'menu-'.
    $menu = substr($derivative, 5);
    return menu_tree($menu);
  }

}
