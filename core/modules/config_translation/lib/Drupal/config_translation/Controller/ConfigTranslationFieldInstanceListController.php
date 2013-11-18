<?php

/**
 * @file
 * Contains \Drupal\config_translation\Controller\ConfigTranslationFieldInstanceListController.
 */

namespace Drupal\config_translation\Controller;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\field\Field;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the config translation controller for field instance entities.
 */
class ConfigTranslationFieldInstanceListController extends ConfigTranslationEntityListController {

  /**
   * The name of the entity type the field instances are attached to.
   *
   * @var string
   */
  protected $baseEntityType = '';

  /**
   * An array containing the base entity type's definition.
   *
   * @var string
   */
  protected $baseEntityInfo = array();

  /**
   * The bundle info for the base entity type.
   *
   * @var string
   */
  protected $baseEntityBundles = array();

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Instantiates a new instance of this entity controller.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   * @param string $entity_type
   *   The entity type which the controller handles.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param array $definition
   *   The plugin definition of the config translation mapper.
   *
   * @return static
   *   A new instance of the entity controller.
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info, array $definition = array()) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('entity.manager')->getStorageController($entity_type),
      $container->get('module_handler'),
      $container->get('entity.manager'),
      $definition
    );
  }

  /**
   * Constructs a new EntityListController object.
   *
   * @param string $entity_type
   *   The type of entity to be listed.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The entity storage controller class.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param $definition
   *   The plugin definition of the config translation mapper.
   */
  public function __construct($entity_type, array $entity_info, EntityStorageControllerInterface $storage, ModuleHandlerInterface $module_handler, EntityManager $entity_manager, array $definition) {
    parent::__construct($entity_type, $entity_info, $storage, $module_handler);
    $this->entityManager = $entity_manager;
    $this->baseEntityType = $definition['base_entity_type'];
    $this->baseEntityInfo = $this->entityManager->getDefinition($this->baseEntityType);
    $this->baseEntityBundles = $this->entityManager->getBundleInfo($this->baseEntityType);
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = array();
    // It is not possible to use the standard load method, because this needs
    // all field instance entities only for the given baseEntityType.
    foreach (Field::fieldInfo()->getInstances($this->baseEntityType) as $fields) {
      $entities = array_merge($entities, array_values($fields));
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterLabels() {
    $info = parent::getFilterLabels();
    $bundle = isset($this->baseEntityInfo['bundle_label']) ? $this->baseEntityInfo['bundle_label'] : $this->t('Bundle');
    $bundle = Unicode::strtolower($bundle);

    $info['placeholder'] = $this->t('Enter field or @bundle', array('@bundle' => $bundle));
    $info['description'] = $this->t('Enter a part of the field or @bundle to filter by.', array('@bundle' => $bundle));

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = array(
      'data' => $this->getLabel($entity),
      'class' => 'table-filter-text-source',
    );

    if ($this->displayBundle()) {
      $bundle = $entity->get('bundle');
      $row['bundle'] = array(
        'data' => String::checkPlain($this->baseEntityBundles[$bundle]['label']),
        'class' => 'table-filter-text-source',
      );
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Field');
    if ($this->displayBundle()) {
      $header['bundle'] = isset($this->baseEntityInfo['bundle_label']) ? $this->baseEntityInfo['bundle_label'] : $this->t('Bundle');
    }
    return $header + parent::buildHeader();
  }

  /**
   * Controls the visibility of the bundle column on field instance list pages.
   *
   * @return bool
   *   Whenever the bundle is displayed or not.
   */
  public function displayBundle() {
    // The bundle key is explicitly defined in the entity definition.
    if (isset($this->baseEntityInfo['bundle_keys']['bundle'])) {
      return TRUE;
    }

    // There is more than one bundle defined.
    if (count($this->baseEntityBundles) > 1) {
      return TRUE;
    }

    // The defined bundle ones not match the entity type name.
    if (!empty($this->baseEntityBundles) && !isset($this->baseEntityBundles[$this->baseEntityType])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function sortRows($a, $b) {
    return $this->sortRowsMultiple($a, $b, array('bundle', 'label'));
  }

}
