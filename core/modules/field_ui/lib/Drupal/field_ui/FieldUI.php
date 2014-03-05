<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldUI.
 */

namespace Drupal\field_ui;

use Drupal\Component\Utility\UrlHelper;

/**
 * Static service container wrapper for Field UI.
 */
class FieldUI {

  /**
   * Returns the route info for the field overview of a given entity bundle.
   *
   * @param string $entity_type_id
   *   An entity type.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return array
   *   An associative array with the following keys:
   *   - route_name: The name of the route.
   *   - route_parameters: (optional) An associative array of parameter names
   *     and values.
   *   - options: (optional) An associative array of additional options. See
   *     \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute() for
   *     comprehensive documentation.
   */
  public static function getOverviewRouteInfo($entity_type_id, $bundle) {
    $entity_type = \Drupal::entityManager()->getDefinition($entity_type_id);
    if ($entity_type->hasLinkTemplate('admin-form')) {
      return array(
        'route_name' => "field_ui.overview_$entity_type_id",
        'route_parameters' => array(
          $entity_type->getBundleEntityType() => $bundle,
        ),
        'options' => array(),
      );
    }
  }

  /**
   * Returns the next redirect path in a multipage sequence.
   *
   * @param array $destinations
   *   An array of destinations to redirect to.
   *
   * @return array
   *   The next destination to redirect to.
   */
  public static function getNextDestination(array $destinations) {
    $next_destination = array_shift($destinations);
    if (is_array($next_destination)) {
      $next_destination['options']['query']['destinations'] = $destinations;
    }
    else {
      $options = UrlHelper::parse($next_destination);
      if ($destinations) {
        $options['query']['destinations'] = $destinations;
      }
      $next_destination = array($options['path'], $options);
    }
    return $next_destination;
  }

}
