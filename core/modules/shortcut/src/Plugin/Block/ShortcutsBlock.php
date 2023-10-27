<?php

namespace Drupal\shortcut\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'Shortcut' block.
 */
#[Block(
  id: "shortcuts",
  admin_label: new TranslatableMarkup("Shortcuts"),
  category: new TranslatableMarkup("Menus")
)]
class ShortcutsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      shortcut_renderable_links(shortcut_current_displayed_set()),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access shortcuts');
  }

}
