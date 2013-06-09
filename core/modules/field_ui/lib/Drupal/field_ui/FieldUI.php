<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldUI.
 */

namespace Drupal\field_ui;

/**
 * Static service container wrapper for Field UI.
 */
class FieldUI {

  /**
   * Returns the next redirect path in a multipage sequence.
   *
   * @return array
   *   An array of redirect paths.
   */
  public static function getNextDestination() {
    $next_destination = array();
    $destinations = !empty($_REQUEST['destinations']) ? $_REQUEST['destinations'] : array();
    if (!empty($destinations)) {
      unset($_REQUEST['destinations']);
      $path = array_shift($destinations);
      $options = drupal_parse_url($path);
      if ($destinations) {
        $options['query']['destinations'] = $destinations;
      }
      $next_destination = array($options['path'], $options);
    }
    return $next_destination;
  }

}
