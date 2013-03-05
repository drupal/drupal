<?php

/**
 * @file
 * Contains \Drupal\shortcut\Plugin\block\block\ShortcutsBlock.
 */

namespace Drupal\shortcut\Plugin\block\block;

use Drupal\block\BlockBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Shortcut' block.
 *
 * @Plugin(
 *  id = "shortcuts",
 *  admin_label = @Translation("Shortcuts"),
 *  module = "shortcut"
 * )
 */
class ShortcutsBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    return array(
      shortcut_renderable_links(shortcut_current_displayed_set()),
    );
  }

}
