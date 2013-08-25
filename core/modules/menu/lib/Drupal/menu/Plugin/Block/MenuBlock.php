<?php

/**
 * @file
 * Contains \Drupal\menu\Plugin\Block\MenuBlock.
 */

namespace Drupal\menu\Plugin\Block;

use Drupal\system\Plugin\Block\SystemMenuBlock;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a generic Menu block.
 *
 * @Block(
 *   id = "menu_menu_block",
 *   admin_label = @Translation("Menu"),
 *   derivative = "Drupal\menu\Plugin\Derivative\MenuBlock"
 * )
 */
class MenuBlock extends SystemMenuBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    list($plugin, $menu) = explode(':', $this->getPluginId());
    return menu_tree($menu);
  }

}
