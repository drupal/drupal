<?php

/**
 * @file
 * Contains \Drupal\rest\LinkManager\RelationLinkManagerInterface.
 */

namespace Drupal\rest\LinkManager;

interface RelationLinkManagerInterface extends ConfigurableLinkManagerInterface {

  /**
   * Gets the URI that corresponds to a field.
   *
   * @param string $entity_type
   *   The bundle's entity type.
   * @param string $bundle
   *   The bundle name.
   * @param string $field_name
   *   The field name.
   * @param array $context
   *   (optional) Optional serializer/normalizer context.
   *
   * @return string
   *   The corresponding URI for the field.
   */
  public function getRelationUri($entity_type, $bundle, $field_name, $context = array());

  /**
   * Translates a REST URI into internal IDs.
   *
   * @param string $relation_uri
   *   Relation URI to transform into internal IDs
   *
   * @return array
   *   Array with keys 'entity_type', 'bundle' and 'field_name'.
   */
  public function getRelationInternalIds($relation_uri);

}
