<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\block\block\SystemMenuBlock.
 */

namespace Drupal\system\Plugin\block\block;

use Drupal\block\BlockBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'System Menu' block.
 *
 * @Plugin(
 *   id = "system_menu_block",
 *   admin_label = @Translation("System Menu"),
 *   module = "system",
 *   derivative = "Drupal\system\Plugin\Derivative\SystemMenuBlock"
 * )
 */
class SystemMenuBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::blockAccess().
   */
  public function blockAccess() {
    // @todo The 'Tools' menu should be available to anonymous users.
    list($plugin, $derivative) = explode(':', $this->getPluginId());
    return ($GLOBALS['user']->uid || in_array($derivative, array('menu-tools', 'menu-footer')));
  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    list($plugin, $derivative) = explode(':', $this->getPluginId());
    // Derivatives are prefixed with 'menu-'.
    $menu = substr($derivative, 5);
    return menu_tree($menu);
  }

}
