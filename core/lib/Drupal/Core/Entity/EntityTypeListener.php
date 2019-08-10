<?php

namespace Drupal\Core\Entity;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Reacts to entity type CRUD on behalf of the Entity system.
 *
 * @see \Drupal\Core\Entity\EntityTypeEvents
 */
class EntityTypeListener implements EntityTypeListenerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The entity last installed schema repository.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $entityLastInstalledSchemaRepository;

  /**
   * Constructs a new EntityTypeListener.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository
   *   The entity last installed schema repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EventDispatcherInterface $event_dispatcher, EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->entityLastInstalledSchemaRepository = $entity_last_installed_schema_repository;
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

    $this->eventDispatcher->dispatch(EntityTypeEvents::CREATE, new EntityTypeEvent($entity_type));

    $this->entityLastInstalledSchemaRepository->setLastInstalledDefinition($entity_type);
    if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      $this->entityLastInstalledSchemaRepository->setLastInstalledFieldStorageDefinitions($entity_type_id, $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id));
    }
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

    $this->eventDispatcher->dispatch(EntityTypeEvents::UPDATE, new EntityTypeEvent($entity_type, $original));

    $this->entityLastInstalledSchemaRepository->setLastInstalledDefinition($entity_type);
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

    $this->eventDispatcher->dispatch(EntityTypeEvents::DELETE, new EntityTypeEvent($entity_type));

    $this->entityLastInstalledSchemaRepository->deleteLastInstalledDefinition($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldableEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original, array $field_storage_definitions, array $original_field_storage_definitions, array &$sandbox = NULL) {
    $entity_type_id = $entity_type->id();

    // @todo Forward this to all interested handlers, not only storage, once
    //   iterating handlers is possible: https://www.drupal.org/node/2332857.
    $storage = $this->entityTypeManager->createHandlerInstance($entity_type->getStorageClass(), $entity_type);
    if ($storage instanceof EntityTypeListenerInterface) {
      $storage->onFieldableEntityTypeUpdate($entity_type, $original, $field_storage_definitions, $original_field_storage_definitions, $sandbox);
    }

    if ($sandbox === NULL || (isset($sandbox['#finished']) && $sandbox['#finished'] == 1)) {
      $this->eventDispatcher->dispatch(EntityTypeEvents::UPDATE, new EntityTypeEvent($entity_type, $original));

      $this->entityLastInstalledSchemaRepository->setLastInstalledDefinition($entity_type);
      if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
        $this->entityLastInstalledSchemaRepository->setLastInstalledFieldStorageDefinitions($entity_type_id, $field_storage_definitions);
      }
    }
  }

}
