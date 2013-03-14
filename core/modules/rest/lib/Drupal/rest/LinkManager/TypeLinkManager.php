<?php

/**
 * @file
 * Contains \Drupal\rest\LinkManager\TypeLinkManager.
 */

namespace Drupal\rest\LinkManager;

class TypeLinkManager implements TypeLinkManagerInterface {

  /**
   * Get a type link for a bundle.
   *
   * @param string $entity_type
   *   The bundle's entity type.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   The URI that identifies this bundle.
   */
  public function getTypeUri($entity_type, $bundle) {
    // @todo Make the base path configurable.
    return url("rest/type/$entity_type/$bundle", array('absolute' => TRUE));
  }

}
