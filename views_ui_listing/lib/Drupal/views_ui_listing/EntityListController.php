<?php

/**
 * @file
 * Definition of Drupal\views_ui_listing\EntityListController.
 */

namespace Drupal\views_ui_listing;

use Drupal\Core\Entity\EntityInterface;

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
   */
  public function __construct($entity_type) {
    $this->entityType = $entity_type;
    $this->storage = entity_get_controller($this->entityType);
    $this->entityInfo = entity_get_info($this->entityType);
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::getStorageController().
   */
  public function getStorageController() {
    return $this->storage;
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::load().
   */
  public function load() {
    return $this->storage->load();
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::getOperations().
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
   * Retrieves the entity list path from the entity information.
   *
   * @return string
   *   The internal system path where the entity list will be rendered.
   *
   * @todo What is this method for, other than fetching the list path? Is this
   *  for http://drupal.org/node/1783964 ? Should it be on the interface?
   */
  public function getPath() {
    return $this->entityInfo['list path'];
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::buildHeader().
   */
  public function buildHeader() {
    $row['label'] = t('Label');
    $row['id'] = t('Machine name');
    $row['operations'] = t('Operations');
    return $row;
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $operations = $this->buildOperations($entity);
    $row['operations'] = drupal_render($operations);
    return $row;
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::buildOperations().
   */
  public function buildOperations(EntityInterface $entity) {
    // Retrieve and sort operations.
    $operations = $this->getOperations($entity);
    uasort($operations, 'drupal_sort_weight');
    $build = array(
      '#theme' => 'links',
      '#links' => $operations,
    );
    return $build;
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::render().
   */
  public function render() {
    $build = array(
      '#theme' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('There is no @label yet. <a href="@add-url">Add one</a>.', array(
        '@label' => $this->entityInfo['label'],
        '@add-url' => url($this->getPath() . '/add'),
      )),
    );
    foreach ($this->load() as $entity) {
      $build['#rows'][$entity->id()] = $this->buildRow($entity);
    }
    return $build;
  }

}
