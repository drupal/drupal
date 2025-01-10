<?php

declare(strict_types=1);

namespace Drupal\navigation\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\navigation\TopBarItemManagerInterface;
use Drupal\navigation\TopBarRegion;

/**
 * Provides a render element for the default Drupal toolbar.
 */
#[RenderElement('top_bar')]
class TopBar extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#pre_render' => [
        [static::class, 'preRenderTopBar'],
      ],
      '#theme' => 'top_bar',
      '#attached' => [
        'library' => [
          'navigation/internal.navigation',
        ],
      ],
    ];
  }

  /**
   * Builds the TopBar as a structured array ready for rendering.
   *
   * Since building the TopBar takes some time, it is done just prior to
   * rendering to ensure that it is built only if it will be displayed.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   A renderable array.
   *
   * @see navigation_page_top()
   */
  public static function preRenderTopBar($element): array {
    $top_bar_item_manager = static::topBarItemManager();

    // Group the items by region.
    foreach (TopBarRegion::cases() as $region) {
      $items = $top_bar_item_manager->getRenderedTopBarItemsByRegion($region);
      $element = array_merge($element, [$region->value => $items]);
    }

    return $element;
  }

  /**
   * Wraps the top bar item manager.
   *
   * @return \Drupal\navigation\TopBarItemManager
   *   The top bar item manager.
   */
  protected static function topBarItemManager(): TopBarItemManagerInterface {
    return \Drupal::service(TopBarItemManagerInterface::class);
  }

}
