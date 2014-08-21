<?php

/**
 * @file
 * Contains \Drupal\shortcut\Plugin\Block\ShortcutsBlock.
 */

namespace Drupal\shortcut\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Shortcut' block.
 *
 * @Block(
 *   id = "shortcuts",
 *   admin_label = @Translation("Shortcuts"),
 *   category = @Translation("Menus")
 * )
 */
class ShortcutsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      shortcut_renderable_links(shortcut_current_displayed_set()),
    );
  }

}
