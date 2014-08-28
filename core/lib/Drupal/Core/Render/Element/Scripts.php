<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Scripts.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a render element for adding JavaScript to the HTML output.
 *
 * @see \Drupal\Core\Render\Element\Styles
 *
 * @RenderElement("scripts")
 */
class Scripts extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#items' => array(),
      '#pre_render' => array(
        array($class, 'preRenderScripts'),
      ),
    );
  }

  /**
   * #pre_render callback to add the elements needed for JavaScript tags to be rendered.
   *
   * This function evaluates the aggregation enabled/disabled condition on a group
   * by group basis by testing whether an aggregate file has been made for the
   * group rather than by testing the site-wide aggregation setting. This allows
   * this function to work correctly even if modules have implemented custom
   * logic for grouping and aggregating files.
   *
   * @param array $element
   *   A render array containing:
   *   - #items: The JavaScript items as returned by _drupal_add_js() and
   *     altered by drupal_get_js().
   *   - #group_callback: A function to call to group #items. Following
   *     this function, #aggregate_callback is called to aggregate items within
   *     the same group into a single file.
   *   - #aggregate_callback: A function to call to aggregate the items within
   *     the groups arranged by the #group_callback function.
   *
   * @return array
   *   A render array that will render to a string of JavaScript tags.
   *
   * @see drupal_get_js()
   */
  public static function preRenderScripts($element) {
    $js_assets = $element['#items'];

    // Aggregate the JavaScript if necessary, but only during normal site
    // operation.
    if (!defined('MAINTENANCE_MODE') && \Drupal::config('system.performance')->get('js.preprocess')) {
      $js_assets = \Drupal::service('asset.js.collection_optimizer')->optimize($js_assets);
    }
    return \Drupal::service('asset.js.collection_renderer')->render($js_assets);
  }

}
