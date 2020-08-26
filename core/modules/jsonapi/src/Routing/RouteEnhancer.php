<?php

namespace Drupal\jsonapi\Routing;

use Drupal\Core\Routing\EnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Ensures the loaded entity matches the requested resource type.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class RouteEnhancer implements EnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (!Routes::isJsonApiRequest($defaults)) {
      return $defaults;
    }

    $resource_type = Routes::getResourceTypeNameFromParameters($defaults);
    $entity_type_id = $resource_type->getEntityTypeId();
    if (!isset($defaults[$entity_type_id]) || !($entity = $defaults[$entity_type_id])) {
      return $defaults;
    }
    $retrieved_bundle = $entity->bundle();
    $configured_bundle = $resource_type->getBundle();
    if ($retrieved_bundle != $configured_bundle) {
      // If the bundle in the loaded entity does not match the bundle in the
      // route (which is set based on the corresponding ResourceType), then
      // throw an exception.
      throw new NotFoundHttpException(sprintf('The loaded entity bundle (%s) does not match the configured resource (%s).', $retrieved_bundle, $configured_bundle));
    }
    return $defaults;
  }

}
