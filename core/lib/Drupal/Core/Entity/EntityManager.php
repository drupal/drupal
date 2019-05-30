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
    @trigger_error('EntityManagerInterface::clearCachedDefinitions() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeManagerInterface::clearCachedDefinitions() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getDefinition() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getDefinition() instead. See https://www.drupal.org/node/2549139', E_USER_DEPRECATED);
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
  public function hasHandler($entity_type_id, $handler_type) {
    @trigger_error('EntityManagerInterface::hasHandler() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::hasHandler() instead. See https://www.drupal.org/node/2549139', E_USER_DEPRECATED);
    return $this->container->get('entity_type.manager')->hasHandler($entity_type_id, $handler_type);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getStorage() instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getStorage($entity_type_id) {
    @trigger_error('EntityManagerInterface::getStorage() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getStorage() instead. See https://www.drupal.org/node/2549139', E_USER_DEPRECATED);
    return $this->container->get('entity_type.manager')->getStorage($entity_type_id);
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
  public function getListBuilder($entity_type_id) {
    @trigger_error('EntityManagerInterface::getListBuilder() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getListBuilder() instead. See https://www.drupal.org/node/2549139', E_USER_DEPRECATED);
    return $this->container->get('entity_type.manager')->getListBuilder($entity_type_id);
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
  public function getFormObject($entity_type_id, $operation) {
    @trigger_error('EntityManagerInterface::getFormObject() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getFormObject() instead. See https://www.drupal.org/node/2549139', E_USER_DEPRECATED);
    return $this->container->get('entity_type.manager')->getFormObject($entity_type_id, $operation);
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
  public function getRouteProviders($entity_type_id) {
    @trigger_error('EntityManagerInterface::getRouteProviders() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getRouteProviders() instead. See https://www.drupal.org/node/2549139', E_USER_DEPRECATED);
    return $this->container->get('entity_type.manager')->getRouteProviders($entity_type_id);
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
  public function getViewBuilder($entity_type_id) {
    @trigger_error('EntityManagerInterface::getViewBuilder() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getViewBuilder() instead. See https://www.drupal.org/node/2549139', E_USER_DEPRECATED);
    return $this->container->get('entity_type.manager')->getViewBuilder($entity_type_id);
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
  public function getAccessControlHandler($entity_type_id) {
    @trigger_error('EntityManagerInterface::getAccessControlHandler() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getAccessControlHandler() instead. See https://www.drupal.org/node/2549139', E_USER_DEPRECATED);
    return $this->container->get('entity_type.manager')->getAccessControlHandler($entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getHandler() instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getHandler($entity_type_id, $handler_type) {
    @trigger_error('EntityManagerInterface::getHandler() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getHandler() instead. See https://www.drupal.org/node/2549139', E_USER_DEPRECATED);
    return $this->container->get('entity_type.manager')->getHandler($entity_type_id, $handler_type);
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
    @trigger_error('EntityManagerInterface::createHandlerInstance() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::createHandlerInstance() instead. See https://www.drupal.org/node/2549139', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getBaseFieldDefinitions() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getBaseFieldDefinitions() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getFieldDefinitions() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldDefinitions() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:8.0.0, will be removed before drupal:9.0.0.
   *   Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldStorageDefinitions()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getFieldStorageDefinitions($entity_type_id) {
    @trigger_error('EntityManagerInterface::getFieldStorageDefinitions() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldStorageDefinitions() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity_field.manager')->getFieldStorageDefinitions($entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityFieldManagerInterface::getActiveFieldStorageDefinitions()
   *   instead.
   *
   * @see https://www.drupal.org/node/3040966
   */
  public function getActiveFieldStorageDefinitions($entity_type_id) {
    @trigger_error('EntityManagerInterface::getActiveFieldStorageDefinitions() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getActiveFieldStorageDefinitions() instead. See https://www.drupal.org/node/3040966.', E_USER_DEPRECATED);
    return $this->container->get('entity_field.manager')->getActiveFieldStorageDefinitions($entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:8.0.0, will be removed before drupal:9.0.0.
   *   Use \Drupal\Core\Entity\EntityFieldManagerInterface::setFieldMap()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function setFieldMap(array $field_map) {
    @trigger_error('EntityManagerInterface::setFieldMap() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::setFieldMap() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity_field.manager')->setFieldMap($field_map);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:8.0.0, will be removed before drupal:9.0.0.
   *   Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldMap()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getFieldMap() {
    @trigger_error('EntityManagerInterface::getFieldMap() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldMap() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity_field.manager')->getFieldMap();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:8.0.0, will be removed before drupal:9.0.0.
   *   Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldMapByFieldType()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getFieldMapByFieldType($field_type) {
    @trigger_error('EntityManagerInterface::getFieldMapByFieldType() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldMapByFieldType() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity_field.manager')->getFieldMapByFieldType($field_type);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:8.0.0, will be removed before drupal:9.0.0.
   *   Use \Drupal\Core\Field\FieldDefinitionListenerInterface::onFieldDefinitionCreate()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function onFieldDefinitionCreate(FieldDefinitionInterface $field_definition) {
    @trigger_error('EntityManagerInterface::onFieldDefinitionCreate() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Field\FieldDefinitionListenerInterface::onFieldDefinitionCreate() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::onFieldDefinitionUpdate() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Field\FieldDefinitionListenerInterface::onFieldDefinitionUpdate() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::onFieldDefinitionDelete() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Field\FieldDefinitionListenerInterface::onFieldDefinitionDelete() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::clearCachedFieldDefinitions() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::clearCachedFieldDefinitions() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:8.0.0, will be removed before drupal:9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeBundleInfoInterface::clearCachedBundles()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function clearCachedBundles() {
    @trigger_error('EntityManagerInterface::clearCachedBundles() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeBundleInfoInterface::clearCachedBundles() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    $this->container->get('entity_type.bundle.info')->clearCachedBundles();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:8.0.0, will be removed before drupal:9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeBundleInfoInterface::getBundleInfo()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getBundleInfo($entity_type_id) {
    @trigger_error('EntityManagerInterface::getBundleInfo() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeBundleInfoInterface::getBundleInfo() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity_type.bundle.info')->getBundleInfo($entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:8.0.0, will be removed before drupal:9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeBundleInfoInterface::getAllBundleInfo()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getAllBundleInfo() {
    @trigger_error('EntityManagerInterface::getAllBundleInfo() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeBundleInfoInterface::getAllBundleInfo() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity_type.bundle.info')->getAllBundleInfo();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:8.0.0, will be removed before drupal:9.0.0.
   *   Use \Drupal\Core\Entity\EntityFieldManagerInterface::getExtraFields()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getExtraFields($entity_type_id, $bundle) {
    @trigger_error('EntityManagerInterface::getExtraFields() is deprecated in drupal:8.0.0 and will be removed before drupal:9.0.0. Use \Drupal\Core\Entity\EntityFieldManagerInterface::getExtraFields() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getEntityTypeLabels() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeRepositoryInterface::getEntityTypeLabels() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getTranslationFromContext() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepository::getTranslationFromContext() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity.repository')->getTranslationFromContext($entity, $langcode, $context);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.7.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityRepositoryInterface::getActive() instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getActive($entity_type_id, $entity_id, array $contexts = NULL) {
    @trigger_error('EntityManagerInterface::getActive() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepositoryInterface::getActive() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity.repository')->getActive($entity_type_id, $entity_id, $contexts);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.7.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityRepositoryInterface::getActiveMultiple()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getActiveMultiple($entity_type_id, array $entity_ids, array $contexts = NULL) {
    @trigger_error('EntityManagerInterface::getActiveMultiple() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepositoryInterface::getActiveMultiple() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity.repository')->getActiveMultiple($entity_type_id, $entity_ids, $contexts);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.7.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityRepositoryInterface::getCanonical()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getCanonical($entity_type_id, $entity_id, array $contexts = NULL) {
    @trigger_error('EntityManagerInterface::getCanonical() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepositoryInterface::getCanonical() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity.repository')->getCanonical($entity_type_id, $entity_id, $contexts);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.7.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityRepositoryInterface::getCanonicalMultiple()
   *   instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function getCanonicalMultiple($entity_type_id, array $entity_ids, array $contexts = NULL) {
    @trigger_error('EntityManagerInterface::getCanonicalMultiple() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepositoryInterface::getCanonicalMultiple() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity.repository')->getCanonicalMultiple($entity_type_id, $entity_ids, $contexts);
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
    @trigger_error('EntityManagerInterface::getAllViewModes() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getAllViewModes() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getViewModes() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getViewModes() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getAllFormModes() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getAllFormModes() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getFormModes() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getFormModes() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getViewModeOptions() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getViewModeOptions() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getFormModeOptions() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getFormModeOptions() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getViewModeOptionsByBundle() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getViewModeOptionsByBundle() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getFormModeOptionsByBundle() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getFormModeOptionsByBundle() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::clearDisplayModeInfo() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDisplayRepositoryInterface::clearDisplayModeInfo() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::loadEntityByUuid() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepository::loadEntityByUuid() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::loadEntityByConfigTarget() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepository::loadEntityByConfigTarget() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getEntityTypeFromClass() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeRepositoryInterface::getEntityTypeFromClass() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity_type.repository')->getEntityTypeFromClass($class_name);
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    @trigger_error('EntityManagerInterface::onEntityTypeCreate() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeListenerInterface::onEntityTypeCreate() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::onEntityTypeUpdate() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeListenerInterface::onEntityTypeUpdate() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    $this->container->get('entity_type.listener')->onEntityTypeUpdate($entity_type, $original);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.7.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeListenerInterface::onFieldableEntityTypeUpdate()
   *   instead.
   *
   * @see https://www.drupal.org/project/drupal/issues/2984782
   */
  public function onFieldableEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original, array $field_storage_definitions, array $original_field_storage_definitions, array &$sandbox = NULL) {
    $this->container->get('entity_type.listener')->onFieldableEntityTypeUpdate($entity_type, $original, $field_storage_definitions, $original_field_storage_definitions, $sandbox);
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
    @trigger_error('EntityManagerInterface::onEntityTypeDelete() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeListenerInterface::onEntityTypeDelete() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::onFieldStorageDefinitionCreate() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Field\FieldStorageDefinitionListenerInterface::onFieldStorageDefinitionCreate() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::onFieldStorageDefinitionUpdate() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Field\FieldStorageDefinitionListenerInterface::onFieldStorageDefinitionUpdate() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::onFieldStorageDefinitionDelete() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Field\FieldStorageDefinitionListenerInterface::onFieldStorageDefinitionDelete() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::onBundleCreate() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityBundleListenerInterface::onBundleCreate() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::onBundleDelete() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityBundleListenerInterface::onBundleDelete() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getLastInstalledDefinition() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface::getLastInstalledDefinition() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity.last_installed_schema.repository')->getLastInstalledDefinition($entity_type_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated EntityManagerInterface::useCaches() is deprecated in 8.0.0 and
   *   will be removed before Drupal 9.0.0. Use
   *   \Drupal\Core\Entity\EntityTypeManagerInterface::useCaches() and/or
   *   Drupal\Core\Entity\EntityFieldManagerInterface::useCaches() instead.
   *
   * @see https://www.drupal.org/node/2549139
   */
  public function useCaches($use_caches = FALSE) {
    @trigger_error('EntityManagerInterface::useCaches() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeManagerInterface::useCaches() and/or Drupal\Core\Entity\EntityFieldManagerInterface::useCaches() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getLastInstalledFieldStorageDefinitions() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface::getLastInstalledFieldStorageDefinitions() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getDefinitions() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::getDefinitions() instead. See https://www.drupal.org/node/2549139', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::hasDefinition() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\EntityTypeManager::hasDefinition() instead. See https://www.drupal.org/node/2549139', E_USER_DEPRECATED);
    return $this->container->get('entity_type.manager')->hasDefinition($plugin_id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getActiveDefinition()
   *   instead.
   *
   * @see https://www.drupal.org/node/3040966
   */
  public function getActiveDefinition($entity_type_id) {
    @trigger_error('EntityManagerInterface::getActiveDefinition() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeManagerInterface::getActiveDefinition() instead. See https://www.drupal.org/node/3040966.', E_USER_DEPRECATED);
    return $this->container->get('entity_type.manager')->getActiveDefinition($entity_type_id);
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
    @trigger_error('EntityManagerInterface::createInstance() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeManagerInterface::createInstance() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
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
    @trigger_error('EntityManagerInterface::getInstance() is deprecated in 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityTypeManagerInterface::getInstance() instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
    return $this->container->get('entity_type.manager')->getInstance($options);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:8.8.0, will be removed before drupal:9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getViewDisplay()
   *   instead.
   */
  public function getViewDisplay($entity_type, $bundle, $view_mode = self::DEFAULT_DISPLAY_MODE) {
    @trigger_error('EntityManager::getViewDisplay() is deprecated in drupal:8.8.0 and will be removed before Drupal 9.0.0. Use \Drupal::service(\'entity_display.repository\')->getViewDisplay() instead.', E_USER_DEPRECATED);
    return $this->container->get('entity_display.repository')->getViewDisplay($entity_type, $bundle, $view_mode);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:8.8.0, will be removed before drupal:9.0.0.
   *   Use \Drupal\Core\Entity\EntityTypeManagerInterface::getFormwDisplay()
   *   instead.
   */
  public function getFormDisplay($entity_type, $bundle, $form_mode = self::DEFAULT_DISPLAY_MODE) {
    @trigger_error('EntityManager::getFormDisplay() is deprecated in drupal:8.8.0 and will be removed before Drupal 9.0.0. Use \Drupal::service(\'entity_display.repository\')->getFormDisplay() instead.', E_USER_DEPRECATED);
    return $this->container->get('entity_display.repository')->getFormDisplay($entity_type, $bundle, $form_mode);
  }

}
