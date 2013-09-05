<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldUI.
 */

namespace Drupal\field_ui;

use Symfony\Component\HttpFoundation\Request;

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
  public static function getNextDestination(Request $request) {
    $next_destination = array();
    $destinations = $request->query->get('destinations');
    if (!empty($destinations)) {
      $request->query->remove('destinations');
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
