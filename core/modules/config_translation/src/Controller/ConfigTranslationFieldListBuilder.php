<?php

/**
 * @file
 * Contains \Drupal\config_translation\Controller\ConfigTranslationFieldListBuilder.
 */

namespace Drupal\config_translation\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the config translation list builder for field entities.
 */
class ConfigTranslationFieldListBuilder extends ConfigTranslationEntityListBuilder {

  /**
   * The name of the entity type the fields are attached to.
   *
   * @var string
   */
  protected $baseEntityType = '';

  /**
   * An array containing the base entity type's definition.
   *
   * @var array
   */
  protected $baseEntityInfo = array();

  /**
   * The bundle info for the base entity type.
   *
   * @var array
   */
  protected $baseEntityBundles = array();

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_type,
      $entity_manager->getStorage($entity_type->id()),
      $entity_manager
    );
  }

  /**
   * Constructs a new ConfigTranslationFieldListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityManagerInterface $entity_manager) {
    parent::__construct($entity_type, $storage);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function setMapperDefinition($mapper_definition) {
    $this->baseEntityType = $mapper_definition['base_entity_type'];
    $this->baseEntityInfo = $this->entityManager->getDefinition($this->baseEntityType);
    $this->baseEntityBundles = $this->entityManager->getBundleInfo($this->baseEntityType);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    // It is not possible to use the standard load method, because this needs
    // all field entities only for the given baseEntityType.
    $ids = \Drupal::entityQuery('field_config')
      ->condition('id', $this->baseEntityType . '.', 'STARTS_WITH')
      ->execute();
    return $this->storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterLabels() {
    $info = parent::getFilterLabels();
    $bundle = $this->baseEntityInfo->getBundleLabel() ?: $this->t('Bundle');
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
        'data' => SafeMarkup::checkPlain($this->baseEntityBundles[$bundle]['label']),
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
      $header['bundle'] = $this->baseEntityInfo->getBundleLabel() ?: $this->t('Bundle');
    }
    return $header + parent::buildHeader();
  }

  /**
   * Controls the visibility of the bundle column on field list pages.
   *
   * @return bool
   *   Whenever the bundle is displayed or not.
   */
  public function displayBundle() {
    // The bundle key is explicitly defined in the entity definition.
    if ($this->baseEntityInfo->getKey('bundle')) {
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
