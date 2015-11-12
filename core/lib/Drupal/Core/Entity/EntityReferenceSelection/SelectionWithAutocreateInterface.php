<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface.
 */

namespace Drupal\Core\Entity\EntityReferenceSelection;

/**
 * Interface for Selection plugins that support newly created entities.
 *
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager
 * @see \Drupal\Core\Entity\Annotation\EntityReferenceSelection
 * @see plugin_api
 */
interface SelectionWithAutocreateInterface {

  /**
   * Creates a new entity object that can be used as a valid reference.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle name.
   * @param string $label
   *   The entity label.
   * @param int $uid
   *   The entity owner ID, if the entity type supports it.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An unsaved entity object.
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid);

  /**
   * Validates which newly created entities can be referenced.
   *
   * This method should replicate the logic implemented by
   * \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface::validateReferenceableEntities(),
   * but applied to newly created entities that have not been saved yet.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities to check.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The incoming $entities parameter, filtered for valid entities. Array keys
   *   are preserved.
   */
  public function validateReferenceableNewEntities(array $entities);

}
