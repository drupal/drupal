<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemMenuBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a generic Menu block.
 *
 * @Block(
 *   id = "system_menu_block",
 *   admin_label = @Translation("Menu"),
 *   category = @Translation("Menu"),
 *   derivative = "Drupal\system\Plugin\Derivative\SystemMenuBlock"
 * )
 */
class SystemMenuBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access() {
    // @todo Clean up when http://drupal.org/node/1874498 lands.
    list( , $derivative) = explode(':', $this->getPluginId());
    return ($GLOBALS['user']->isAuthenticated() || in_array($derivative, array('main', 'tools', 'footer')));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // @todo Clean up when http://drupal.org/node/1874498 lands.
    list(, $menu) = explode(':', $this->getPluginId());
    return menu_tree($menu);
  }

}
