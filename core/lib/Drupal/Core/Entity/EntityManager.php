<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Provides a wrapper around many other services relating to entities.
 *
 * Deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0. We cannot
 * use the deprecated PHPDoc tag because this service class is still used in
 * legacy code paths. Symfony would fail test cases with deprecation warnings.
 *
 * @todo Enforce the deprecation of each method once
 *   https://www.drupal.org/node/2578361 is in.
 */
class EntityManager implements EntityManagerInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::clearCachedDefinitions()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function clearCachedDefinitions() {
    $this->container->get('entity_type.manager')->clearCachedDefinitions();

    // @todo None of these are plugin managers, and they should not co-opt
    //   this method for managing its caches. Remove in
    //   https://www.drupal.org/node/2549143.
    $this->container->get('entity_type.bundle.info')->clearCachedBundles();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
    $this->container->get('entity_type.repository')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getDefinition()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getDefinition($entity_type_id, $exception_on_invalid = TRUE) {
    return $this->container->get('entity_type.manager')->getDefinition($entity_type_id, $exception_on_invalid);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::hasHandler()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function hasHandler($entity_type, $handler_type) {
    return $this->container->get('entity_type.manager')->hasHandler($entity_type, $handler_type);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getStorage() instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getStorage($entity_type) {
    return $this->container->get('entity_type.manager')->getStorage($entity_type);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getListBuilder()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getListBuilder($entity_type) {
    return $this->container->get('entity_type.manager')->getListBuilder($entity_type);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getFormObject()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getFormObject($entity_type, $operation) {
    return $this->container->get('entity_type.manager')->getFormObject($entity_type, $operation);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getRouteProviders()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getRouteProviders($entity_type) {
    return $this->container->get('entity_type.manager')->getRouteProviders($entity_type);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getViewBuilder()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getViewBuilder($entity_type) {
    return $this->container->get('entity_type.manager')->getViewBuilder($entity_type);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getAccessControlHandler()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getAccessControlHandler($entity_type) {
    return $this->container->get('entity_type.manager')->getAccessControlHandler($entity_type);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getHandler() instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getHandler($entity_type, $handler_type) {
    return $this->container->get('entity_type.manager')->getHandler($entity_type, $handler_type);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::createHandlerInstance()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function createHandlerInstance($class, EntityTypeInterface $definition = NULL) {
    return $this->container->get('entity_type.manager')->createHandlerInstance($class, $definition);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityFieldManagerInterface::getBaseFieldDefinitions()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getBaseFieldDefinitions($entity_type_id) {
    return $this->container->get('entity_field.manager')->getBaseFieldDefinitions($entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldDefinitions()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getFieldDefinitions($entity_type_id, $bundle) {
    return $this->container->get('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldStorageDefinitions()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getFieldStorageDefinitions($entity_type_id) {
    return $this->container->get('entity_field.manager')->getFieldStorageDefinitions($entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityFieldManagerInterface::setFieldMap()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function setFieldMap(array $field_map) {
    return $this->container->get('entity_field.manager')->setFieldMap($field_map);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldMap()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getFieldMap() {
    return $this->container->get('entity_field.manager')->getFieldMap();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldMapByFieldType()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getFieldMapByFieldType($field_type) {
    return $this->container->get('entity_field.manager')->getFieldMapByFieldType($field_type);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Field\FieldDefinitionListenerInterface::onFieldDefinitionCreate()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function onFieldDefinitionCreate(FieldDefinitionInterface $field_definition) {
    $this->container->get('field_definition.listener')->onFieldDefinitionCreate($field_definition);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Field\FieldDefinitionListenerInterface::onFieldDefinitionUpdate()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function onFieldDefinitionUpdate(FieldDefinitionInterface $field_definition, FieldDefinitionInterface $original) {
    $this->container->get('field_definition.listener')->onFieldDefinitionUpdate($field_definition, $original);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Field\FieldDefinitionListenerInterface::onFieldDefinitionDelete()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function onFieldDefinitionDelete(FieldDefinitionInterface $field_definition) {
    $this->container->get('field_definition.listener')->onFieldDefinitionDelete($field_definition);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityFieldManagerInterface::clearCachedFieldDefinitions()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function clearCachedFieldDefinitions() {
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeBundleManagerInterface::clearCachedBundles()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function clearCachedBundles() {
    $this->container->get('entity_type.bundle.info')->clearCachedBundles();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeBundleManagerInterface::getBundleInfo()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getBundleInfo($entity_type) {
    return $this->container->get('entity_type.bundle.info')->getBundleInfo($entity_type);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeBundleManagerInterface::getAllBundleInfo()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getAllBundleInfo() {
    return $this->container->get('entity_type.bundle.info')->getAllBundleInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraFields($entity_type_id, $bundle) {
    return $this->container->get('entity_field.manager')->getExtraFields($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeRepositoryInterface::getEntityTypeLabels()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getEntityTypeLabels($group = FALSE) {
    return $this->container->get('entity_type.repository')->getEntityTypeLabels($group);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityRepositoryInterface::getTranslationFromContext()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getTranslationFromContext(EntityInterface $entity, $langcode = NULL, $context = []) {
    return $this->container->get('entity.repository')->getTranslationFromContext($entity, $langcode, $context);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getAllViewModes()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getAllViewModes() {
    return $this->container->get('entity_display.repository')->getAllViewModes();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getViewModes()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getViewModes($entity_type_id) {
    return $this->container->get('entity_display.repository')->getViewModes($entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getAllFormModes()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getAllFormModes() {
    return $this->container->get('entity_display.repository')->getAllFormModes();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getFormModes()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getFormModes($entity_type_id) {
    return $this->container->get('entity_display.repository')->getFormModes($entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getViewModeOptions()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getViewModeOptions($entity_type_id) {
    return $this->container->get('entity_display.repository')->getViewModeOptions($entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getFormModeOptions()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getFormModeOptions($entity_type_id) {
    return $this->container->get('entity_display.repository')->getFormModeOptions($entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getViewModeOptionsByBundle()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getViewModeOptionsByBundle($entity_type_id, $bundle) {
    return $this->container->get('entity_display.repository')->getViewModeOptionsByBundle($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getFormModeOptionsByBundle()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getFormModeOptionsByBundle($entity_type_id, $bundle) {
    return $this->container->get('entity_display.repository')->getFormModeOptionsByBundle($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::clearDisplayModeInfo()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function clearDisplayModeInfo() {
    $this->container->get('entity_display.repository')->clearDisplayModeInfo();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityRepositoryInterface::loadEntityByUuid()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function loadEntityByUuid($entity_type_id, $uuid) {
    return $this->container->get('entity.repository')->loadEntityByUuid($entity_type_id, $uuid);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityRepositoryInterface::loadEntityByConfigTarget()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function loadEntityByConfigTarget($entity_type_id, $target) {
    return $this->container->get('entity.repository')->loadEntityByConfigTarget($entity_type_id, $target);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeRepositoryInterface::getEntityTypeFromClass()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getEntityTypeFromClass($class_name) {
    return $this->container->get('entity_type.repository')->getEntityTypeFromClass($class_name);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    $this->container->get('entity_type.listener')->onEntityTypeCreate($entity_type);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeListenerInterface::onEntityTypeUpdate()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    $this->container->get('entity_type.listener')->onEntityTypeUpdate($entity_type, $original);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeListenerInterface::onEntityTypeDelete()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    $this->container->get('entity_type.listener')->onEntityTypeDelete($entity_type);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Field\FieldStorageDefinitionListenerInterface::onFieldStorageDefinitionCreate()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
    $this->container->get('field_storage_definition.listener')->onFieldStorageDefinitionCreate($storage_definition);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Field\FieldStorageDefinitionListenerInterface::onFieldStorageDefinitionUpdate()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    $this->container->get('field_storage_definition.listener')->onFieldStorageDefinitionUpdate($storage_definition, $original);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Field\FieldStorageDefinitionListenerInterface::onFieldStorageDefinitionDelete()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
    $this->container->get('field_storage_definition.listener')->onFieldStorageDefinitionDelete($storage_definition);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityBundleListenerInterface::onBundleCreate()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function onBundleCreate($bundle, $entity_type_id) {
    $this->container->get('entity_bundle.listener')->onBundleCreate($bundle, $entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityBundleListenerInterface::onBundleDelete()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function onBundleDelete($bundle, $entity_type_id) {
    $this->container->get('entity_bundle.listener')->onBundleDelete($bundle, $entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface::getLastInstalledDefinition()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getLastInstalledDefinition($entity_type_id) {
    return $this->container->get('entity.last_installed_schema.repository')->getLastInstalledDefinition($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) {
    $this->container->get('entity_type.manager')->useCaches($use_caches);

    // @todo EntityFieldManager is not a plugin manager, and should not co-opt
    //   this method for managing its caches.
    $this->container->get('entity_field.manager')->useCaches($use_caches);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface::getLastInstalledFieldStorageDefinitions()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getLastInstalledFieldStorageDefinitions($entity_type_id) {
    return $this->container->get('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions($entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getDefinitions()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getDefinitions() {
    return $this->container->get('entity_type.manager')->getDefinitions();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::hasDefinition()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function hasDefinition($plugin_id) {
    return $this->container->get('entity_type.manager')->hasDefinition($plugin_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::createInstance()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function createInstance($plugin_id, array $configuration = []) {
    return $this->container->get('entity_type.manager')->createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getInstance()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getInstance(array $options) {
    return $this->container->get('entity_type.manager')->getInstance($options);
  }

}
