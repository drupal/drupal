<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldUI.
 */

namespace Drupal\field_ui;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityType;
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
    $entity_type = \Drupal::entityManager()->getDefinition($entity_type_id);
    if ($entity_type->get('field_ui_base_route')) {
      $bundle_entity_type = static::getRouteBundleEntityType($entity_type);
      return new Url("entity.{$bundle_entity_type}.field_ui_fields", array(
        $entity_type->getBundleEntityType() => $bundle,
      ));
    }
  }

  /**
   * Returns the next redirect path in a multipage sequence.
   *
   * @param array $destinations
   *   An array of destinations to redirect to.
   *
   * @return \Drupal\Core\Url
   *   The next destination to redirect to.
   */
  public static function getNextDestination(array $destinations) {
    $next_destination = array_shift($destinations);
    if (is_array($next_destination)) {
      $next_destination['options']['query']['destinations'] = $destinations;
      $next_destination += array(
        'route_parameters' => array(),
      );
      $next_destination = Url::fromRoute($next_destination['route_name'], $next_destination['route_parameters'], $next_destination['options']);
    }
    else {
      $options = UrlHelper::parse($next_destination);
      if ($destinations) {
        $options['query']['destinations'] = $destinations;
      }
      // Redirect to any given path within the same domain.
      // @todo Use Url::fromPath() once https://www.drupal.org/node/2351379 is
      //   resolved.
      $next_destination = Url::fromUri('base:' . $options['path']);
    }
    return $next_destination;
  }

  /**
   * Gets the bundle entity type used for route names.
   *
   * This method returns the bundle entity type, in case there is one.
   *
   * @param \Drupal\Core\Entity\EntityType $entity_type
   *   The actual entity type, not the bundle.
   *
   * @return string
   *   The used entity type ID in the route name.
   */
  public static function getRouteBundleEntityType(EntityType $entity_type) {
    return $entity_type->getBundleEntityType() != 'bundle' ? $entity_type->getBundleEntityType() : $entity_type->id();
  }
}
