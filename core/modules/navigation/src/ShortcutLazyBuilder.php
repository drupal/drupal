<?php

declare(strict_types=1);

namespace Drupal\navigation;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\shortcut\ShortcutLazyBuilders;

/**
 * Lazy Builders for Navigation shortcuts links.
 *
 * @internal The navigation module is experimental.
 * @see \Drupal\shortcut\ShortcutLazyBuilders
 */
final class ShortcutLazyBuilder implements TrustedCallbackInterface {

  /**
   * Constructs a ShortcutLazyBuilders object.
   *
   * @param \Drupal\shortcut\ShortcutLazyBuilders $shortcutLazyBuilder
   *   The original shortcuts lazy builder service.
   */
  public function __construct(
    protected readonly ShortcutLazyBuilders $shortcutLazyBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['lazyLinks'];
  }

  /**
   * The #lazy_builder callback; builds shortcut navigation links.
   *
   * @param string $label
   *   (Optional) The links label. Defaults to "Shortcuts".
   *
   * @return array
   *   A renderable array of shortcut links.
   */
  public function lazyLinks(string $label = 'Shortcuts') {
    $shortcut_links = $this->shortcutLazyBuilder->lazyLinks();

    if (empty($shortcut_links['shortcuts']['#links'])) {
      return [
        '#cache' => $shortcut_links['#cache'],
      ];
    }
    $shortcuts_items = [
      [
        'title' => $label,
        'class' => 'shortcuts',
        'below' => $shortcut_links['shortcuts']['#links'],
      ],
    ];

    return [
      '#title' => $label,
      '#theme' => 'navigation_menu',
      '#menu_name' => 'shortcuts',
      '#items' => $shortcuts_items,
      '#cache' => $shortcut_links['#cache'],
    ];
  }

}
