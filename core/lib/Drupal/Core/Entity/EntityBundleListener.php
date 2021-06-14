<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Reacts to entity bundle CRUD on behalf of the Entity system.
 */
class EntityBundleListener implements EntityBundleListenerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new EntityBundleListener.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $module_handler;
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
