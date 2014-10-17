<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityDefinitionUpdateManager.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Entity\Schema\EntityStorageSchemaInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Manages entity definition updates.
 */
class EntityDefinitionUpdateManager implements EntityDefinitionUpdateManagerInterface {
  use StringTranslationTrait;

  /**
   * Indicates that a definition has just been created.
   *
   * @var int
   */
  const DEFINITION_CREATED = 1;

  /**
   * Indicates that a definition has changes.
   *
   * @var int
   */
  const DEFINITION_UPDATED = 2;

  /**
   * Indicates that a definition has just been deleted.
   *
   * @var int
   */
  const DEFINITION_DELETED = 3;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EntityDefinitionUpdateManager.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function needsUpdates() {
    return (bool) $this->getChangeList();
  }

  /**
   * {@inheritdoc}
   */
  public function getChangeSummary() {
    $summary = array();

    foreach ($this->getChangeList() as $entity_type_id => $change_list) {
      // Process entity type definition changes.
      if (!empty($change_list['entity_type']) && $change_list['entity_type'] == static::DEFINITION_UPDATED) {
        $entity_type = $this->entityManager->getDefinition($entity_type_id);
        $summary[$entity_type_id][] = $this->t('Update the %entity_type entity type.', array('%entity_type' => $entity_type->getLabel()));
      }

      // Process field storage definition changes.
      if (!empty($change_list['field_storage_definitions'])) {
        $storage_definitions = $this->entityManager->getFieldStorageDefinitions($entity_type_id);
        $original_storage_definitions = $this->entityManager->getLastInstalledFieldStorageDefinitions($entity_type_id);

        foreach ($change_list['field_storage_definitions'] as $field_name => $change) {
          switch ($change) {
            case static::DEFINITION_CREATED:
              $summary[$entity_type_id][] = $this->t('Create the %field_name field.', array('%field_name' => $storage_definitions[$field_name]->getLabel()));
              break;

            case static::DEFINITION_UPDATED:
              $summary[$entity_type_id][] = $this->t('Update the %field_name field.', array('%field_name' => $storage_definitions[$field_name]->getLabel()));
              break;

            case static::DEFINITION_DELETED:
              $summary[$entity_type_id][] = $this->t('Delete the %field_name field.', array('%field_name' => $original_storage_definitions[$field_name]->getLabel()));
              break;
          }
        }
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function applyUpdates() {
    foreach ($this->getChangeList() as $entity_type_id => $change_list) {
      // Process entity type definition changes.
      if (!empty($change_list['entity_type']) && $change_list['entity_type'] == static::DEFINITION_UPDATED) {
        $entity_type = $this->entityManager->getDefinition($entity_type_id);
        $original = $this->entityManager->getLastInstalledDefinition($entity_type_id);
        $this->entityManager->onEntityTypeUpdate($entity_type, $original);
      }

      // Process field storage definition changes.
      if (!empty($change_list['field_storage_definitions'])) {
        $storage_definitions = $this->entityManager->getFieldStorageDefinitions($entity_type_id);
        $original_storage_definitions = $this->entityManager->getLastInstalledFieldStorageDefinitions($entity_type_id);

        foreach ($change_list['field_storage_definitions'] as $field_name => $change) {
          switch ($change) {
            case static::DEFINITION_CREATED:
              $this->entityManager->onFieldStorageDefinitionCreate($storage_definitions[$field_name]);
              break;

            case static::DEFINITION_UPDATED:
              $this->entityManager->onFieldStorageDefinitionUpdate($storage_definitions[$field_name], $original_storage_definitions[$field_name]);
              break;

            case static::DEFINITION_DELETED:
              $this->entityManager->onFieldStorageDefinitionDelete($original_storage_definitions[$field_name]);
              break;
          }
        }
      }
    }
  }

  /**
   * Returns a list of changes to entity type and field storage definitions.
   *
   * @return array
   *   An associative array keyed by entity type id of change descriptors. Every
   *   entry is an associative array with the following optional keys:
   *   - entity_type: a scalar having only the DEFINITION_UPDATED value.
   *   - field_storage_definitions: an associative array keyed by field name of
   *     scalars having one value among:
   *     - DEFINITION_CREATED
   *     - DEFINITION_UPDATED
   *     - DEFINITION_DELETED
   */
  protected function getChangeList() {
    $this->entityManager->clearCachedDefinitions();
    $change_list = array();

    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      $original = $this->entityManager->getLastInstalledDefinition($entity_type_id);

      // Only manage changes to already installed entity types. Entity type
      // installation is handled elsewhere (e.g.,
      // \Drupal\Core\Extension\ModuleHandler::install()).
      if (!$original) {
        continue;
      }

      // @todo Support non-storage-schema-changing definition updates too:
      //   https://www.drupal.org/node/2336895.
      if ($this->requiresEntityStorageSchemaChanges($entity_type, $original)) {
        $change_list[$entity_type_id]['entity_type'] = static::DEFINITION_UPDATED;
      }

      if ($this->entityManager->getStorage($entity_type_id) instanceof DynamicallyFieldableEntityStorageInterface) {
        $field_changes = array();
        $storage_definitions = $this->entityManager->getFieldStorageDefinitions($entity_type_id);
        $original_storage_definitions = $this->entityManager->getLastInstalledFieldStorageDefinitions($entity_type_id);

        // Detect created field storage definitions.
        foreach (array_diff_key($storage_definitions, $original_storage_definitions) as $field_name => $storage_definition) {
          $field_changes[$field_name] = static::DEFINITION_CREATED;
        }

        // Detect deleted field storage definitions.
        foreach (array_diff_key($original_storage_definitions, $storage_definitions) as $field_name => $original_storage_definition) {
          $field_changes[$field_name] = static::DEFINITION_DELETED;
        }

        // Detect updated field storage definitions.
        foreach (array_intersect_key($storage_definitions, $original_storage_definitions) as $field_name => $storage_definition) {
          // @todo Support non-storage-schema-changing definition updates too:
          //   https://www.drupal.org/node/2336895. So long as we're checking
          //   based on schema change requirements rather than definition
          //   equality, skip the check if the entity type itself needs to be
          //   updated, since that can affect the schema of all fields, so we
          //   want to process that update first without reporting false
          //   positives here.
          if (!isset($change_list[$entity_type_id]['entity_type']) && $this->requiresFieldStorageSchemaChanges($storage_definition, $original_storage_definitions[$field_name])) {
            $field_changes[$field_name] = static::DEFINITION_UPDATED;
          }
        }

        if ($field_changes) {
          $change_list[$entity_type_id]['field_storage_definitions'] = $field_changes;
        }
      }
    }

    return array_filter($change_list);
  }

  /**
   * Checks if the changes to the entity type requires storage schema changes.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The updated entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeInterface $original
   *   The original entity type definition.
   *
   * @return bool
   *   TRUE if storage schema changes are required, FALSE otherwise.
   */
  protected function requiresEntityStorageSchemaChanges(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    $storage = $this->entityManager->getStorage($entity_type->id());
    return ($storage instanceof EntityStorageSchemaInterface) && $storage->requiresEntityStorageSchemaChanges($entity_type, $original);
  }

  /**
   * Checks if the changes to the storage definition requires schema changes.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The updated field storage definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $original
   *   The original field storage definition.
   *
   * @return bool
   *   TRUE if storage schema changes are required, FALSE otherwise.
   */
  protected function requiresFieldStorageSchemaChanges(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    $storage = $this->entityManager->getStorage($storage_definition->getTargetEntityTypeId());
    return ($storage instanceof DynamicallyFieldableEntityStorageSchemaInterface) && $storage->requiresFieldStorageSchemaChanges($storage_definition, $original);
  }

}
