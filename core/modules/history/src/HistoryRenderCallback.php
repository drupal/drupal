<?php

namespace Drupal\history;

use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Render callback object.
 */
class HistoryRenderCallback implements RenderCallbackInterface {

  /**
   * Render API callback: Attaches the last read timestamp for a node.
   *
   * This function is assigned as a #lazy_builder callback.
   *
   * @param int $node_id
   *   The node ID for which to attach the last read timestamp.
   *
   * @return array
   *   A renderable array containing the last read timestamp.
   */
  public static function lazyBuilder($node_id) {
    $element = [];
    $element['#attached']['drupalSettings']['history']['lastReadTimestamps'][$node_id] = (int) history_read($node_id);
    return $element;
  }

}
