<?php

namespace Drupal\jsonapi\ResourceType;

/**
 * Provides a repository of all JSON:API resource types.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 */
interface ResourceTypeRepositoryInterface {

  /**
   * Gets all JSON:API resource types.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType[]
   *   The set of all JSON:API resource types in this Drupal instance.
   */
  public function all();

  /**
   * Gets a specific JSON:API resource type based on entity type ID and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The ID for the bundle to find. If the entity type does not have a bundle,
   *   then the entity type ID again.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType
   *   The requested JSON:API resource type, if it exists. NULL otherwise.
   *
   * @see \Drupal\Core\Entity\EntityInterface::bundle()
   */
  public function get($entity_type_id, $bundle);

  /**
   * Gets a specific JSON:API resource type based on a supplied typename.
   *
   * @param string $type_name
   *   The public typename of a JSON:API resource.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType|null
   *   The resource type, or NULL if none found.
   */
  public function getByTypeName($type_name);

}
