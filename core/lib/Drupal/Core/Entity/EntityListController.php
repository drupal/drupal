<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityListController.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\String;

/**
 * Provides a generic implementation of an entity list controller.
 */
class EntityListController extends EntityControllerBase implements EntityListControllerInterface, EntityControllerInterface {

  /**
   * The entity storage controller class.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storage;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorageController($entity_type->id())
    );
  }

  /**
   * Constructs a new EntityListController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The entity storage controller class.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageControllerInterface $storage) {
    $this->entityTypeId = $entity_type->id();
    $this->storage = $storage;
    $this->entityType = $entity_type;
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
    $operations = array();
    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'weight' => 10,
      ) + $entity->urlInfo('edit-form');
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'weight' => 100,
      ) + $entity->urlInfo('delete-form');
    }

    return $operations;
  }

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see \Drupal\Core\Entity\EntityListController::render()
   */
  public function buildHeader() {
    $row['operations'] = $this->t('Operations');
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
   * @see \Drupal\Core\Entity\EntityListController::render()
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
   * @see \Drupal\Core\Entity\EntityListController::render()
   */
  public function buildOperations(EntityInterface $entity) {
    // Retrieve and sort operations.
    $operations = $this->getOperations($entity);
    $this->moduleHandler()->alter('entity_operation', $operations, $entity);
    uasort($operations, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
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
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => array(),
      '#empty' => $this->t('There is no @label yet.', array('@label' => $this->entityType->getLabel())),
    );
    foreach ($this->load() as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['#rows'][$entity->id()] = $row;
      }
    }
    return $build;
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translationManager()->translate($string, $args, $options);
  }

  /**
   * Returns the title of the page.
   *
   * @return string
   *   A string title of the page.
   *
   */
  protected function getTitle() {
    return;
  }

}
