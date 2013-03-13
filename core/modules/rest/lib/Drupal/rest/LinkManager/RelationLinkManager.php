<?php

/**
 * @file
 * Contains \Drupal\rest\LinkManager\RelationLinkManager.
 */

namespace Drupal\rest\LinkManager;

class RelationLinkManager implements RelationLinkManagerInterface{

  /**
   * Get a relation link for the field.
   *
   * @param string $entity_type
   *   The bundle's entity type.
   * @param string $bundle
   *   The name of the bundle.
   * @param string $field_name
   *   The name of the field.
   *
   * @return array
   *   The URI that identifies this field.
   */
  public function getRelationUri($entity_type, $bundle, $field_name) {
    // @todo Make the base path configurable.
    return url("rest/relation/$entity_type/$bundle/$field_name", array('absolute' => TRUE));
  }

}
