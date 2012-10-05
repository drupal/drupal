<?php

/**
 * @file
 * Definition of Drupal\Core\Entity\EntityListController.
 */

namespace Drupal\Core\Entity;

/**
 * Provides a generic implementation of an entity list controller.
 */
class EntityListController implements EntityListControllerInterface {

  /**
   * The entity storage controller class.
   *
   * @var Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storage;

  /**
   * The entity type name.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity info array.
   *
   * @var array
   *
   * @see entity_get_info()
   */
  protected $entityInfo;

  /**
   * Constructs a new EntityListController object.
   *
   * @param string $entity_type.
   *   The type of entity to be listed.
   * @param Drupal\Core\Entity\EntityStorageControllerInterface $storage.
   *   The entity storage controller class.
   */
  public function __construct($entity_type, EntityStorageControllerInterface $storage) {
    $this->entityType = $entity_type;
    $this->storage = $storage;
    $this->entityInfo = entity_get_info($this->entityType);
  }

  /**
   * Implements Drupal\Core\Entity\EntityListControllerInterface::getStorageController().
   */
  public function getStorageController() {
    return $this->storage;
  }

  /**
   * Implements Drupal\Core\Entity\EntityListControllerInterface::load().
   */
  public function load() {
    return $this->storage->load();
  }

  /**
   * Implements Drupal\Core\Entity\EntityListControllerInterface::getOperations().
   */
  public function getOperations(EntityInterface $entity) {
    $uri = $entity->uri();
    $operations['edit'] = array(
      'title' => t('Edit'),
      'href' => $uri['path'] . '/edit',
      'options' => $uri['options'],
      'weight' => 10,
    );
    $operations['delete'] = array(
      'title' => t('Delete'),
      'href' => $uri['path'] . '/delete',
      'options' => $uri['options'],
      'weight' => 100,
    );
    return $operations;
  }

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildHeader() {
    $row['label'] = t('Label');
    $row['id'] = t('Machine name');
    $row['operations'] = t('Operations');
    return $row;
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity for this row of the list.
   *
   * @return array
   *   A render array structure of fields for this entity.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $operations = $this->buildOperations($entity);
    $row['operations']['data'] = $operations;
    return $row;
  }

  /**
   * Builds a renderable list of operation links for the entity.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which the linked operations will be performed.
   *
   * @return array
   *   A renderable array of operation links.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildOperations(EntityInterface $entity) {
    // Retrieve and sort operations.
    $operations = $this->getOperations($entity);
    uasort($operations, 'drupal_sort_weight');
    $build = array(
      '#type' => 'operations',
      '#links' => $operations,
    );
    return $build;
  }

  /**
   * Implements Drupal\Core\Entity\EntityListControllerInterface::render().
   *
   * Builds the entity list as renderable array for theme_table().
   *
   * @todo Add a link to add a new item to the #empty text.
   */
  public function render() {
    $build = array(
      '#theme' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('There is no @label yet.', array('@label' => $this->entityInfo['label'])),
    );
    foreach ($this->load() as $entity) {
      $build['#rows'][$entity->id()] = $this->buildRow($entity);
    }
    return $build;
  }

}
