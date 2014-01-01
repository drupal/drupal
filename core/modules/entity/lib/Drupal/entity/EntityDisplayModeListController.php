<?php

/**
 * @file
 * Contains \Drupal\entity\EntityDisplayModeListController.
 */

namespace Drupal\entity;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the listing for entity display modes.
 */
class EntityDisplayModeListController extends ConfigEntityListController {

  /**
   * The entity info for all entity types.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $entityInfoComplete;

  /**
   * Constructs a new EntityListController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_info
   *   The entity info for the entity type.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The entity storage controller class.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_info_complete
   *   The entity info for all entity types.
   */
  public function __construct(EntityTypeInterface $entity_info, EntityStorageControllerInterface $storage, ModuleHandlerInterface $module_handler, array $entity_info_complete) {
    parent::__construct($entity_info, $storage, $module_handler);

    $this->entityInfoComplete = $entity_info_complete;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_info) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_info,
      $entity_manager->getStorageController($entity_info->id()),
      $container->get('module_handler'),
      $entity_manager->getDefinitions()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = array();
    foreach (parent::load() as $entity) {
      $entities[$entity->getTargetType()][] = $entity;
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = array();
    foreach ($this->load() as $entity_type => $entities) {
      if (!isset($this->entityInfoComplete[$entity_type])) {
        continue;
      }

      // Filter entities
      if ($this->entityInfoComplete[$entity_type]->isFieldable() && !$this->isValidEntity($entity_type)) {
        continue;
      }

      $table = array(
        '#prefix' => '<h2>' . $this->entityInfoComplete[$entity_type]->getLabel() . '</h2>',
        '#type' => 'table',
        '#header' => $this->buildHeader(),
        '#rows' => array(),
      );
      foreach ($entities as $entity) {
        if ($row = $this->buildRow($entity)) {
          $table['#rows'][$entity->id()] = $row;
        }
      }

      // Move content at the top.
      if ($entity_type == 'node') {
        $table['#weight'] = -10;
      }

      $short_type = str_replace('_mode', '', $this->entityType);
      $table['#rows']['_add_new'][] = array(
        'data' => array(
          '#type' => 'link',
          '#href' => "admin/structure/display-modes/$short_type/add/$entity_type",
          '#title' => t('Add new %label @entity-type', array('%label' => $this->entityInfoComplete[$entity_type]->getLabel(), '@entity-type' => $this->entityInfo->getLowercaseLabel())),
          '#options' => array(
            'html' => TRUE,
          ),
        ),
        'colspan' => count($table['#header']),
      );
      $build[$entity_type] = $table;
    }
    return $build;
  }

  /**
   * Filters entities based on their controllers.
   *
   * @param $entity_type
   *   The entity type of the entity that needs to be validated.
   *
   * @return bool
   *   TRUE if the entity has the correct controller, FALSE if the entity
   *   doesn't has the correct controller.
   */
  protected function isValidEntity($entity_type) {
    return $this->entityInfoComplete[$entity_type]->hasController('view_builder');
  }

}
