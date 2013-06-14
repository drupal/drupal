<?php

/**
 * @file
 * Contains \Drupal\shortcut\Plugin\Block\ShortcutsBlock.
 */

namespace Drupal\shortcut\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
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
   * {@inheritdoc}
   */
  public function build() {
    return array(
      shortcut_renderable_links(shortcut_current_displayed_set()),
    );
  }

}
