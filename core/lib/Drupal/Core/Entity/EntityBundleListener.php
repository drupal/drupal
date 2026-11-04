<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Reacts to entity bundle CRUD on behalf of the Entity system.
 */
class EntityBundleListener implements EntityBundleListenerInterface {

  /**
   * Constructs a new EntityBundleListener.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function onBundleCreate($bundle, $entity_type_id) {
    $this->entityTypeBundleInfo->clearCachedBundles();
    // Notify the entity storage.
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if ($storage instanceof EntityBundleListenerInterface) {
      $storage->onBundleCreate($bundle, $entity_type_id);
    }
    // Invoke hook_entity_bundle_create() hook.
    $this->moduleHandler->invokeAll('entity_bundle_create', [$entity_type_id, $bundle]);
    $this->entityFieldManager->clearCachedFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function onBundleDelete($bundle, $entity_type_id) {
    $this->entityTypeBundleInfo->clearCachedBundles();
    // Notify the entity storage.
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if ($storage instanceof EntityBundleListenerInterface) {
      $storage->onBundleDelete($bundle, $entity_type_id);
    }
    // Invoke hook_entity_bundle_delete() hook.
    $this->moduleHandler->invokeAll('entity_bundle_delete', [$entity_type_id, $bundle]);
    $this->entityFieldManager->clearCachedFieldDefinitions();
  }

}
