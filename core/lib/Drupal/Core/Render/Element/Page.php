<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Page.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a render element for an entire HTML page.
 *
 * @RenderElement("page")
 */
class Page extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#show_messages' => TRUE,
      '#pre_render' => array(
        array($class, 'preRenderPage'),
      ),
      '#theme' => 'page',
      '#title' => '',
    );
  }

  /**
   * #pre_render callback for the page element type.
   *
   * @param array $element
   *   A structured array containing the page element type build properties.
   *
   * @return array
   */
  public static function preRenderPage($element) {
    $element['#cache']['tags'][] = 'theme:' . \Drupal::theme()->getActiveTheme()->getName();
    $element['#cache']['tags'][] = 'theme_global_settings';
    return $element;
  }

}
