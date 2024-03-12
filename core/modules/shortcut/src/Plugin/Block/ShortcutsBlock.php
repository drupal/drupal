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
      '#lazy_builder' => ['shortcut.lazy_builders:lazyLinks', [FALSE]],
      '#create_placeholder' => TRUE,
      '#cache' => [
        'keys' => ['shortcut_set_block_links'],
        'contexts' => ['user'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access shortcuts');
  }

}
