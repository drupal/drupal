<?php

/**
 * @file
 * Contains \Drupal\views\Element\View.
 */

namespace Drupal\views\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\views\Views;

/**
 * Provides a render element to display a view.
 *
 * @todo Annotate once https://www.drupal.org/node/2326409 is in.
 *   RenderElement("view")
 */
class View extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#theme_wrappers' => array('container'),
      '#pre_render' => array(
        array($class, 'preRenderViewElement'),
      ),
      '#name' => NULL,
      '#display_id' => 'default',
      '#arguments' => array(),
    );
  }

  /**
   * View element pre render callback.
   */
  public static function preRenderViewElement($element) {
    $element['#attributes']['class'][] = 'views-element-container';

    $view = Views::getView($element['#name']);
    if ($view && $view->access($element['#display_id'])) {
      $element['view'] = $view->preview($element['#display_id'], $element['#arguments']);
    }

    return $element;
  }

}
