<?php

/**
 * @file
 * Contains \Drupal\rest\LinkManager\RelationLinkManagerInterface.
 */

namespace Drupal\rest\LinkManager;

interface RelationLinkManagerInterface {

  /**
   * Gets the URI that corresponds to a field.
   *
   * @param string $entity_type
   *   The bundle's entity type.
   * @param string $bundle
   *   The bundle name.
   * @param string $field_name
   *   The field name.
   *
   * @return string
   *   The corresponding URI for the field.
   */
  public function getRelationUri($entity_type, $bundle, $field_name);
}
