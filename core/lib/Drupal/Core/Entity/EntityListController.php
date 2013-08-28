<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityListController.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\String;

/**
 * Provides a generic implementation of an entity list controller.
 */
class EntityListController implements EntityListControllerInterface, EntityControllerInterface {

  /**
   * The entity storage controller class.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storage;

  /**
   * The module handler to invoke hooks on.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('plugin.manager.entity')->getStorageController($entity_type),
      $container->get('module_handler')
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
   */
  public function __construct($entity_type, array $entity_info, EntityStorageControllerInterface $storage, ModuleHandlerInterface $module_handler) {
    $this->entityType = $entity_type;
    $this->storage = $storage;
    $this->entityInfo = $entity_info;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityListControllerInterface::getStorageController().
   */
  public function getStorageController() {
    return $this->storage;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityListControllerInterface::load().
   */
  public function load() {
    return $this->storage->loadMultiple();
  }

  /**
   * Returns the escaped label of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being listed.
   *
   * @return string
   *   The escaped entity label.
   */
  protected function getLabel(EntityInterface $entity) {
    return String::checkPlain($entity->label());
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $uri = $entity->uri();

    $operations = array();
    if ($entity->access('update')) {
      $operations['edit'] = array(
        'title' => t('Edit'),
        'href' => $uri['path'] . '/edit',
        'options' => $uri['options'],
        'weight' => 10,
      );
    }
    if ($entity->access('delete')) {
      $operations['delete'] = array(
        'title' => t('Delete'),
        'href' => $uri['path'] . '/delete',
        'options' => $uri['options'],
        'weight' => 100,
      );
    }

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
    $row['operations'] = t('Operations');
    return $row;
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for this row of the list.
   *
   * @return array
   *   A render array structure of fields for this entity.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildRow(EntityInterface $entity) {
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }

  /**
   * Builds a renderable list of operation links for the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
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
    $this->moduleHandler->alter('entity_operation', $operations, $entity);
    uasort($operations, 'drupal_sort_weight');
    $build = array(
      '#type' => 'operations',
      '#links' => $operations,
    );
    return $build;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityListControllerInterface::render().
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
      if ($row = $this->buildRow($entity)) {
        $build['#rows'][$entity->id()] = $row;
      }
    }
    return $build;
  }

}
