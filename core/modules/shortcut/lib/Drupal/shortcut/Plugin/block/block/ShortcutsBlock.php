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
 *  subject = @Translation("Shortcuts"),
 *  module = "shortcut"
 * )
 */
class ShortcutsBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function blockBuild() {
    return array(
      shortcut_renderable_links(shortcut_current_displayed_set()),
    );
  }

}
