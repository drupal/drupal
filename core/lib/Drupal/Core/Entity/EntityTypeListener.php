<?php

namespace Drupal\Core\Entity;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Reacts to entity type CRUD on behalf of the Entity system.
 *
 * @see \Drupal\Core\Entity\EntityTypeEvents
 */
class EntityTypeListener implements EntityTypeListenerInterface {

  /**
   * Constructs a new EntityTypeListener.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository
   *   The entity last installed schema repository.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected EventDispatcherInterface $eventDispatcher,
    protected EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();

    // @todo Forward this to all interested handlers, not only storage, once
    //   iterating handlers is possible: https://www.drupal.org/node/2332857.
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if ($storage instanceof EntityTypeListenerInterface) {
      $storage->onEntityTypeCreate($entity_type);
    }

    $this->entityLastInstalledSchemaRepository->setLastInstalledDefinition($entity_type);
    if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      $this->entityLastInstalledSchemaRepository->setLastInstalledFieldStorageDefinitions($entity_type_id, $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id));
    }

    $this->eventDispatcher->dispatch(new EntityTypeEvent($entity_type), EntityTypeEvents::CREATE);
    $this->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldableEntityTypeCreate(EntityTypeInterface $entity_type, array $field_storage_definitions) {
    $entity_type_id = $entity_type->id();

    // @todo Forward this to all interested handlers, not only storage, once
    //   iterating handlers is possible: https://www.drupal.org/node/2332857.
    $storage = $this->entityTypeManager->createHandlerInstance($entity_type->getStorageClass(), $entity_type);
    if ($storage instanceof EntityTypeListenerInterface) {
      $storage->onFieldableEntityTypeCreate($entity_type, $field_storage_definitions);
    }

    $this->entityLastInstalledSchemaRepository->setLastInstalledDefinition($entity_type);
    if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      $this->entityLastInstalledSchemaRepository->setLastInstalledFieldStorageDefinitions($entity_type_id, $field_storage_definitions);
    }

    $this->eventDispatcher->dispatch(new EntityTypeEvent($entity_type), EntityTypeEvents::CREATE);
    $this->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    // An entity type can be updated even when its live (in-code) definition has
    // been removed from the codebase, so we need to instantiate a custom
    // storage handler that uses the passed-in entity type definition.
    $storage = $this->entityTypeManager->createHandlerInstance($entity_type->getStorageClass(), $entity_type);

    // @todo Forward this to all interested handlers, not only storage, once
    //   iterating handlers is possible: https://www.drupal.org/node/2332857.
    if ($storage instanceof EntityTypeListenerInterface) {
      $storage->onEntityTypeUpdate($entity_type, $original);
    }

    $this->entityLastInstalledSchemaRepository->setLastInstalledDefinition($entity_type);

    $this->eventDispatcher->dispatch(new EntityTypeEvent($entity_type, $original), EntityTypeEvents::UPDATE);
    $this->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();

    // An entity type can be deleted even when its live (in-code) definition has
    // been removed from the codebase, so we need to instantiate a custom
    // storage handler that uses the passed-in entity type definition.
    $storage = $this->entityTypeManager->createHandlerInstance($entity_type->getStorageClass(), $entity_type);

    // @todo Forward this to all interested handlers, not only storage, once
    //   iterating handlers is possible: https://www.drupal.org/node/2332857.
    if ($storage instanceof EntityTypeListenerInterface) {
      $storage->onEntityTypeDelete($entity_type);
    }

    $this->entityLastInstalledSchemaRepository->deleteLastInstalledDefinition($entity_type_id);

    $this->eventDispatcher->dispatch(new EntityTypeEvent($entity_type), EntityTypeEvents::DELETE);
    $this->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldableEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original, array $field_storage_definitions, array $original_field_storage_definitions, ?array &$sandbox = NULL) {
    $entity_type_id = $entity_type->id();

    // @todo Forward this to all interested handlers, not only storage, once
    //   iterating handlers is possible: https://www.drupal.org/node/2332857.
    $storage = $this->entityTypeManager->createHandlerInstance($entity_type->getStorageClass(), $entity_type);
    if ($storage instanceof EntityTypeListenerInterface) {
      $storage->onFieldableEntityTypeUpdate($entity_type, $original, $field_storage_definitions, $original_field_storage_definitions, $sandbox);
    }

    if ($sandbox === NULL || (isset($sandbox['#finished']) && $sandbox['#finished'] == 1)) {
      $this->entityLastInstalledSchemaRepository->setLastInstalledDefinition($entity_type);
      if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
        $this->entityLastInstalledSchemaRepository->setLastInstalledFieldStorageDefinitions($entity_type_id, $field_storage_definitions);
      }

      $this->eventDispatcher->dispatch(new EntityTypeEvent($entity_type, $original), EntityTypeEvents::UPDATE);
      $this->clearCachedDefinitions();
    }
  }

  /**
   * Clears necessary caches to apply entity/field definition updates.
   */
  protected function clearCachedDefinitions() {
    $this->entityTypeManager->clearCachedDefinitions();
    $this->entityFieldManager->clearCachedFieldDefinitions();
  }

}
