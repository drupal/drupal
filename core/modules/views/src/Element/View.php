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
 * @RenderElement("view")
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
      '#embed' => TRUE,
    );
  }

  /**
   * View element pre render callback.
   */
  public static function preRenderViewElement($element) {
    $element['#attributes']['class'][] = 'views-element-container';

    if (!isset($element['#view'])) {
      $view = Views::getView($element['#name']);
    }
    else {
      $view = $element['#view'];
    }

    if ($view && $view->access($element['#display_id'])) {
      if (!empty($element['#embed'])) {
        $element += $view->preview($element['#display_id'], $element['#arguments']);
      }
      else {
        // Add contextual links to the view. We need to attach them to the dummy
        // $view_array variable, since contextual_preprocess() requires that they
        // be attached to an array (not an object) in order to process them. For
        // our purposes, it doesn't matter what we attach them to, since once they
        // are processed by contextual_preprocess() they will appear in the
        // $title_suffix variable (which we will then render in
        // views-view.html.twig).
        $view->setDisplay($element['#display_id']);
        $element += $view->executeDisplay($element['#display_id'], $element['#arguments']);
        views_add_contextual_links($element, 'view', $view, $view->current_display);
      }
    }

    return $element;
  }

}
