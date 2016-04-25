<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Field\FieldDefinitionListenerInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;

/**
 * Provides an interface for entity type managers.
 *
 * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
 */
interface EntityManagerInterface extends EntityTypeListenerInterface, EntityBundleListenerInterface, FieldStorageDefinitionListenerInterface, FieldDefinitionListenerInterface, EntityTypeManagerInterface, EntityTypeRepositoryInterface, EntityTypeBundleInfoInterface, EntityDisplayRepositoryInterface, EntityFieldManagerInterface, EntityRepositoryInterface {

  /**
   * @see \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface::getLastInstalledDefinition()
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   */
  public function getLastInstalledDefinition($entity_type_id);

  /**
   * @see \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface::getLastInstalledFieldStorageDefinitions()
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   */
  public function getLastInstalledFieldStorageDefinitions($entity_type_id);

}
