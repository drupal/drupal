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
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('entity.manager')->getStorageController($entity_type),
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
        'title' => $this->t('Edit'),
        'href' => $uri['path'] . '/edit',
        'options' => $uri['options'],
        'weight' => 10,
      );
    }
    if ($entity->access('delete')) {
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
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
      '#title' => $this->getTitle(),
      '#rows' => array(),
      '#empty' => $this->t('There is no @label yet.', array('@label' => $this->entityInfo['label'])),
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
   * Gets the translation manager.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The translation manager.
   */
  protected function translationManager() {
    if (!$this->translationManager) {
      $this->translationManager = \Drupal::translation();
    }
    return $this->translationManager;
  }

  /**
   * Sets the translation manager for this form.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   *
   * @return self
   *   The entity form.
   */
  public function setTranslationManager(TranslationInterface $translation_manager) {
    $this->translationManager = $translation_manager;
    return $this;
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
