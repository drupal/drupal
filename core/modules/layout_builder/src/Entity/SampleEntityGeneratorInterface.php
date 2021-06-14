<?php

namespace Drupal\layout_builder\Entity;

/**
 * Generates a sample entity.
 */
interface SampleEntityGeneratorInterface {

  /**
   * Gets a sample entity for a given entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An entity.
   */
  public function get($entity_type_id, $bundle_id);

  /**
   * Deletes a sample entity for a given entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return $this
   */
  public function delete($entity_type_id, $bundle_id);

}
