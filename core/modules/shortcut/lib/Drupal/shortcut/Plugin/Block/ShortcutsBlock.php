<?php

/**
 * @file
 * Contains \Drupal\shortcut\Plugin\Block\ShortcutsBlock.
 */

namespace Drupal\shortcut\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Shortcut' block.
 *
 * @Block(
 *  id = "shortcuts",
 *  admin_label = @Translation("Shortcuts")
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
