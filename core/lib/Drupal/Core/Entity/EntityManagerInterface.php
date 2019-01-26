<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Field\FieldDefinitionListenerInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;

/**
 * Provides an interface for entity type managers.
 *
 * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
 *
 * @see https://www.drupal.org/node/2549139
 */
interface EntityManagerInterface extends EntityTypeListenerInterface, EntityBundleListenerInterface, FieldStorageDefinitionListenerInterface, FieldDefinitionListenerInterface, EntityTypeManagerInterface, EntityTypeRepositoryInterface, EntityTypeBundleInfoInterface, EntityDisplayRepositoryInterface, EntityFieldManagerInterface, EntityRepositoryInterface {

  /**
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0. Use
   *   \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface::getLastInstalledDefinition()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getLastInstalledDefinition($entity_type_id);

  /**
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0. Use
   *   \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface::getLastInstalledFieldStorageDefinitions()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getLastInstalledFieldStorageDefinitions($entity_type_id);

}
