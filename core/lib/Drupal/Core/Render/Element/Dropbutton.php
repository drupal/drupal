<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Dropbutton.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a render element for a set of links rendered as a drop-down button.
 *
 * @see \Drupal\Core\Render\Element\Operations
 *
 * @RenderElement("dropbutton")
 */
class Dropbutton extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#pre_render' => array(
        array($class, 'preRenderDropbutton'),
      ),
      '#theme' => 'links__dropbutton',
    );
  }

  /**
   * Pre-render callback: Attaches the dropbutton library and required markup.
   */
  public static function preRenderDropbutton($element) {
    $element['#attached']['library'][] = 'core/drupal.dropbutton';
    $element['#attributes']['class'][] = 'dropbutton';
    if (!isset($element['#theme_wrappers'])) {
      $element['#theme_wrappers'] = array();
    }
    array_unshift($element['#theme_wrappers'], 'dropbutton_wrapper');

    // Enable targeted theming of specific dropbuttons (e.g., 'operations' or
    // 'operations__node').
    if (isset($element['#subtype'])) {
      $element['#theme'] .= '__' . $element['#subtype'];
    }

    return $element;
  }

}
