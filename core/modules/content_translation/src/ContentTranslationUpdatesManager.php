<?php

namespace Drupal\content_translation;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

@trigger_error('\Drupal\content_translation\ContentTranslationUpdatesManager is scheduled for removal in Drupal 9.0.0. Definitions are updated automatically now so no replacement is needed. See https://www.drupal.org/node/2973222.', E_USER_DEPRECATED);

/**
 * Provides the logic needed to update field storage definitions when needed.
 *
 * @deprecated in Drupal 8.7.x, to be removed before Drupal 9.0.0.
 *   Definitions are updated automatically now so no replacement is needed.
 *
 * @see https://www.drupal.org/node/2973222
 */
class ContentTranslationUpdatesManager {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The installed entity definition repository.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
   */
  protected $entityLastInstalledSchemaRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $updateManager;

  /**
   * Constructs an updates manager instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager
   *   The entity definition update manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository
   *   The installed entity definition repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityDefinitionUpdateManagerInterface $update_manager, EntityFieldManagerInterface $entity_field_manager = NULL, EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->updateManager = $update_manager;
    if (!$entity_field_manager) {
      $entity_field_manager = \Drupal::service('entity_field.manager');
    }
    $this->entityFieldManager = $entity_field_manager;
    if (!$entity_last_installed_schema_repository) {
      $entity_last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');
    }
    $this->entityLastInstalledSchemaRepository = $entity_last_installed_schema_repository;
  }

  /**
   * Executes field storage definition updates if needed.
   *
   * @param array $entity_types
   *   A list of entity type definitions to be processed.
   */
  public function updateDefinitions(array $entity_types) {
    // Handle field storage definition creation, if needed.
    if ($this->updateManager->needsUpdates()) {
      foreach ($entity_types as $entity_type_id => $entity_type) {
        $storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
        $installed_storage_definitions = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type_id);
        foreach (array_diff_key($storage_definitions, $installed_storage_definitions) as $storage_definition) {
          /** @var $storage_definition \Drupal\Core\Field\FieldStorageDefinitionInterface */
          if ($storage_definition->getProvider() == 'content_translation') {
            $this->updateManager->installFieldStorageDefinition($storage_definition->getName(), $entity_type_id, 'content_translation', $storage_definition);
          }
        }
      }
    }
  }

}
