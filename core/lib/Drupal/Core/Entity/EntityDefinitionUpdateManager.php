<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Entity\Schema\EntityStorageSchemaInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Manages entity definition updates.
 */
class EntityDefinitionUpdateManager implements EntityDefinitionUpdateManagerInterface {
  use StringTranslationTrait;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type listener service.
   *
   * @var \Drupal\Core\Entity\EntityTypeListenerInterface
   */
  protected $entityTypeListener;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field storage definition listener service.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionListenerInterface
   */
  protected $fieldStorageDefinitionListener;

  /**
   * The last installed schema repository.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $entityLastInstalledSchemaRepository;

  /**
   * Constructs a new EntityDefinitionUpdateManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository
   *   The last installed schema repository service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeListenerInterface $entity_type_listener
   *   The entity type listener interface.
   * @param \Drupal\Core\Field\FieldStorageDefinitionListenerInterface $field_storage_definition_listener
   *   The field storage definition listener service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository, EntityFieldManagerInterface $entity_field_manager, EntityTypeListenerInterface $entity_type_listener, FieldStorageDefinitionListenerInterface $field_storage_definition_listener) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityLastInstalledSchemaRepository = $entity_last_installed_schema_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeListener = $entity_type_listener;
    $this->fieldStorageDefinitionListener = $field_storage_definition_listener;
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
    $summary = [];

    foreach ($this->getChangeList() as $entity_type_id => $change_list) {
      // Process entity type definition changes.
      if (!empty($change_list['entity_type'])) {
        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

        switch ($change_list['entity_type']) {
          case static::DEFINITION_CREATED:
            $summary[$entity_type_id][] = $this->t('The %entity_type entity type needs to be installed.', ['%entity_type' => $entity_type->getLabel()]);
            break;

          case static::DEFINITION_UPDATED:
            $summary[$entity_type_id][] = $this->t('The %entity_type entity type needs to be updated.', ['%entity_type' => $entity_type->getLabel()]);
            break;
        }
      }

      // Process field storage definition changes.
      if (!empty($change_list['field_storage_definitions'])) {
        $storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
        $original_storage_definitions = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type_id);

        foreach ($change_list['field_storage_definitions'] as $field_name => $change) {
          switch ($change) {
            case static::DEFINITION_CREATED:
              $summary[$entity_type_id][] = $this->t('The %field_name field needs to be installed.', ['%field_name' => $storage_definitions[$field_name]->getLabel() ?: $field_name]);
              break;

            case static::DEFINITION_UPDATED:
              $summary[$entity_type_id][] = $this->t('The %field_name field needs to be updated.', ['%field_name' => $storage_definitions[$field_name]->getLabel() ?: $field_name]);
              break;

            case static::DEFINITION_DELETED:
              $summary[$entity_type_id][] = $this->t('The %field_name field needs to be uninstalled.', ['%field_name' => $original_storage_definitions[$field_name]->getLabel() ?: $field_name]);
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
  public function getEntityType($entity_type_id) {
    $entity_type = $this->entityLastInstalledSchemaRepository->getLastInstalledDefinition($entity_type_id);
    return $entity_type ? clone $entity_type : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypes() {
    return $this->entityLastInstalledSchemaRepository->getLastInstalledDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function installEntityType(EntityTypeInterface $entity_type) {
    $this->clearCachedDefinitions();
    $this->entityTypeListener->onEntityTypeCreate($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function updateEntityType(EntityTypeInterface $entity_type) {
    $original = $this->getEntityType($entity_type->id());
    $this->clearCachedDefinitions();
    $this->entityTypeListener->onEntityTypeUpdate($entity_type, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function uninstallEntityType(EntityTypeInterface $entity_type) {
    $this->clearCachedDefinitions();
    $this->entityTypeListener->onEntityTypeDelete($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function installFieldableEntityType(EntityTypeInterface $entity_type, array $field_storage_definitions) {
    $this->clearCachedDefinitions();
    foreach ($field_storage_definitions as $name => $field_storage_definition) {
      if ($field_storage_definition instanceof BaseFieldDefinition) {
        $field_storage_definition
          ->setName($name)
          ->setTargetEntityTypeId($entity_type->id())
          ->setProvider($entity_type->getProvider())
          ->setTargetBundle(NULL);
      }
    }
    $this->entityTypeListener->onFieldableEntityTypeCreate($entity_type, $field_storage_definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function updateFieldableEntityType(EntityTypeInterface $entity_type, array $field_storage_definitions, array &$sandbox = NULL) {
    $original = $this->getEntityType($entity_type->id());

    if ($this->requiresEntityDataMigration($entity_type, $original) && $sandbox === NULL) {
      throw new \InvalidArgumentException('The entity schema update for the ' . $entity_type->id() . ' entity type requires a data migration.');
    }

    $original_field_storage_definitions = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type->id());
    $this->entityTypeListener->onFieldableEntityTypeUpdate($entity_type, $original, $field_storage_definitions, $original_field_storage_definitions, $sandbox);
    $this->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function installFieldStorageDefinition($name, $entity_type_id, $provider, FieldStorageDefinitionInterface $storage_definition) {
    // @todo Pass a mutable field definition interface when we have one. See
    //   https://www.drupal.org/node/2346329.
    if ($storage_definition instanceof BaseFieldDefinition) {
      $storage_definition
        ->setName($name)
        ->setTargetEntityTypeId($entity_type_id)
        ->setProvider($provider)
        ->setTargetBundle(NULL);
    }
    $this->clearCachedDefinitions();
    $this->fieldStorageDefinitionListener->onFieldStorageDefinitionCreate($storage_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageDefinition($name, $entity_type_id) {
    $storage_definitions = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type_id);
    return isset($storage_definitions[$name]) ? clone $storage_definitions[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function updateFieldStorageDefinition(FieldStorageDefinitionInterface $storage_definition) {
    $original = $this->getFieldStorageDefinition($storage_definition->getName(), $storage_definition->getTargetEntityTypeId());
    $this->clearCachedDefinitions();
    $this->fieldStorageDefinitionListener->onFieldStorageDefinitionUpdate($storage_definition, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function uninstallFieldStorageDefinition(FieldStorageDefinitionInterface $storage_definition) {
    $this->clearCachedDefinitions();
    $this->fieldStorageDefinitionListener->onFieldStorageDefinitionDelete($storage_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getChangeList() {
    $this->entityTypeManager->useCaches(FALSE);
    $this->entityFieldManager->useCaches(FALSE);
    $change_list = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $original = $this->entityLastInstalledSchemaRepository->getLastInstalledDefinition($entity_type_id);

      // @todo Support non-storage-schema-changing definition updates too:
      //   https://www.drupal.org/node/2336895.
      if (!$original) {
        $change_list[$entity_type_id]['entity_type'] = static::DEFINITION_CREATED;
      }
      else {
        if ($this->requiresEntityStorageSchemaChanges($entity_type, $original)) {
          $change_list[$entity_type_id]['entity_type'] = static::DEFINITION_UPDATED;
        }

        if ($this->entityTypeManager->getStorage($entity_type_id) instanceof DynamicallyFieldableEntityStorageInterface) {
          $field_changes = [];
          $storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
          $original_storage_definitions = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type_id);

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
    }

    // @todo Support deleting entity definitions when we support base field
    //   purging.
    // @see https://www.drupal.org/node/2907779

    $this->entityTypeManager->useCaches(TRUE);
    $this->entityFieldManager->useCaches(TRUE);

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
    $storage = $this->entityTypeManager->getStorage($entity_type->id());
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
    $storage = $this->entityTypeManager->getStorage($storage_definition->getTargetEntityTypeId());
    return ($storage instanceof DynamicallyFieldableEntityStorageSchemaInterface) && $storage->requiresFieldStorageSchemaChanges($storage_definition, $original);
  }

  /**
   * Checks if existing data would be lost if the schema changes were applied.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The updated entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeInterface $original
   *   The original entity type definition.
   *
   * @return bool
   *   TRUE if data migration is required, FALSE otherwise.
   */
  protected function requiresEntityDataMigration(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    $storage = $this->entityTypeManager->getStorage($entity_type->id());
    return ($storage instanceof EntityStorageSchemaInterface) && $storage->requiresEntityDataMigration($entity_type, $original);
  }

  /**
   * Clears necessary caches to apply entity/field definition updates.
   */
  protected function clearCachedDefinitions() {
    $this->entityTypeManager->clearCachedDefinitions();
    $this->entityFieldManager->clearCachedFieldDefinitions();
  }

}
