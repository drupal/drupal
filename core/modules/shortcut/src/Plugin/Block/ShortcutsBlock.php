<?php

/**
 * @file
 * Contains \Drupal\shortcut\Plugin\Block\ShortcutsBlock.
 */

namespace Drupal\shortcut\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

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

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access shortcuts');
  }

}
