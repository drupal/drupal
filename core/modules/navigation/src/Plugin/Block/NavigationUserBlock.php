<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a user navigation block.
 *
 * @internal
 */
#[Block(
  id: 'navigation_user',
  admin_label: new TranslatableMarkup('User'),
)]
final class NavigationUserBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      'user' => [
        '#lazy_builder' => [
          'navigation.user_lazy_builder:renderNavigationLinks',
          [],
        ],
        '#create_placeholder' => TRUE,
        '#cache' => [
          'keys' => ['user_set_navigation_links'],
          'contexts' => ['user'],
        ],
        '#lazy_builder_preview' => [
          '#markup' => '<a href="#" class="toolbar-tray-lazy-placeholder-link">&nbsp;</a>',
        ],
      ],
    ];
  }

}
