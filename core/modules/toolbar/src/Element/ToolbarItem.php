<?php

/**
 * @file
 * Contains \Drupal\toolbar\Element\ToolbarItem.
 */

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
    $class = get_class($this);
    return array(
      '#pre_render' => array(
        array($class, 'preRenderToolbarItem'),
      ),
      'tab' => array(
        '#type' => 'link',
        '#title' => NULL,
        '#url' => Url::fromRoute('<front>'),
      ),
    );
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
    $attributes = array(
      'id' => $id,
    );

    // If tray content is present, markup the tray and its associated trigger.
    if (!empty($element['tray'])) {
      // Provide attributes necessary for trays.
      $attributes += array(
        'data-toolbar-tray' => $id . '-tray',
        'aria-owns' => $id . '-tray',
        'role' => 'button',
        'aria-pressed' => 'false',
      );

      // Merge in module-provided attributes.
      $element['tab'] += array('#attributes' => array());
      $element['tab']['#attributes'] += $attributes;
      $element['tab']['#attributes']['class'][] = 'trigger';

      // Provide attributes for the tray theme wrapper.
      $attributes = array(
        'id' => $id . '-tray',
        'data-toolbar-tray' => $id . '-tray',
      );
      // Merge in module-provided attributes.
      if (!isset($element['tray']['#wrapper_attributes'])) {
        $element['tray']['#wrapper_attributes'] = array();
      }
      $element['tray']['#wrapper_attributes'] += $attributes;
      $element['tray']['#wrapper_attributes']['class'][] = 'toolbar-tray';
    }

    $element['tab']['#attributes']['class'][] = 'toolbar-item';

    return $element;
  }

}
