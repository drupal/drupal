<?php

namespace Drupal\field_ui;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;

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
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public static function getOverviewRouteInfo($entity_type_id, $bundle) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    if ($entity_type->get('field_ui_base_route')) {
      return new Url("entity.{$entity_type_id}.field_ui_fields", static::getRouteBundleParameter($entity_type, $bundle));
    }
  }

  /**
   * Returns the next redirect path in a multipage sequence.
   *
   * @param array $destinations
   *   An array of destinations to redirect to.
   *
   * @return \Drupal\Core\Url|null
   *   The next destination to redirect to.
   */
  public static function getNextDestination(array $destinations) {
    // If there are no valid destinations left, return here.
    if (empty($destinations)) {
      return NULL;
    }

    $next_destination = array_shift($destinations);
    if (is_array($next_destination)) {
      $next_destination['options']['query']['destinations'] = $destinations;
      $next_destination += [
        'route_parameters' => [],
      ];
      $next_destination = Url::fromRoute($next_destination['route_name'], $next_destination['route_parameters'], $next_destination['options']);
    }
    else {
      $options = UrlHelper::parse($next_destination);
      if ($destinations) {
        $options['query']['destinations'] = $destinations;
      }
      // Redirect to any given path within the same domain.
      // @todo Revisit this in https://www.drupal.org/node/2418219.
      $next_destination = Url::fromUserInput('/' . $options['path'], $options);
    }
    return $next_destination;
  }

  /**
   * Gets the route parameter that should be used for Field UI routes.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The actual entity type, not the bundle (e.g. the content entity type).
   * @param string $bundle
   *   The bundle name.
   *
   * @return array
   *   An array that can be used a route parameter.
   */
  public static function getRouteBundleParameter(EntityTypeInterface $entity_type, $bundle) {
    $bundle_parameter_key = $entity_type->getBundleEntityType() ?: 'bundle';
    return [$bundle_parameter_key => $bundle];
  }

}
