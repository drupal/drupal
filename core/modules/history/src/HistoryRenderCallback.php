<?php

namespace Drupal\history;

use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Render callback object.
 */
class HistoryRenderCallback implements RenderCallbackInterface {

  /**
   * #lazy_builder callback; attaches the last read timestamp for a node.
   *
   * @param int $node_id
   *   The node ID for which to attach the last read timestamp.
   *
   * @return array
   *   A renderable array containing the last read timestamp.
   */
  public static function lazyBuilder($node_id) {
    $element = [];
    $timestamps = \Drupal::service('history.repository')->getLastViewed('node', [$node_id], \Drupal::currentUser());
    $element['#attached']['drupalSettings']['history']['lastReadTimestamps'][$node_id] = (int) $timestamps[$node_id];
    return $element;
  }

}
