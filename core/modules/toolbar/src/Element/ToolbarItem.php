<?php

namespace Drupal\toolbar\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Url;

/**
 * Provides a toolbar item that is wrapped in markup for common styling.
 *
 * The 'tray' property contains a renderable array.
 *
 * @RenderElement("toolbar_item")
 */
class ToolbarItem extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#pre_render' => [
        [$class, 'preRenderToolbarItem'],
      ],
      'tab' => [
        '#type' => 'link',
        '#title' => NULL,
        '#url' => Url::fromRoute('<front>'),
      ],
    ];
  }

  /**
   * Provides markup for associating a tray trigger with a tray element.
   *
   * A tray is a responsive container that wraps renderable content. Trays
   * present content well on small and large screens alike.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   A renderable array.
   */
  public static function preRenderToolbarItem($element) {
    $id = $element['#id'];

    // Provide attributes for a toolbar item.
    $attributes = [
      'id' => $id,
    ];

    // If tray content is present, markup the tray and its associated trigger.
    if (!empty($element['tray'])) {
      // Provide attributes necessary for trays.
      $attributes += [
        'data-toolbar-tray' => $id . '-tray',
        'role' => 'button',
        'aria-pressed' => 'false',
      ];

      // Merge in module-provided attributes.
      $element['tab'] += ['#attributes' => []];
      $element['tab']['#attributes'] += $attributes;
      $element['tab']['#attributes']['class'][] = 'trigger';

      // Provide attributes for the tray theme wrapper.
      $attributes = [
        'id' => $id . '-tray',
        'data-toolbar-tray' => $id . '-tray',
      ];
      // Merge in module-provided attributes.
      if (!isset($element['tray']['#wrapper_attributes'])) {
        $element['tray']['#wrapper_attributes'] = [];
      }
      $element['tray']['#wrapper_attributes'] += $attributes;
      $element['tray']['#wrapper_attributes']['class'][] = 'toolbar-tray';
    }

    $element['tab']['#attributes']['class'][] = 'toolbar-item';

    return $element;
  }

}
