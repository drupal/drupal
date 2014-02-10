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
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the listing for entity display modes.
 */
class EntityDisplayModeListController extends ConfigEntityListController {

  /**
   * All entity types.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $entityTypes;

  /**
   * Constructs a new EntityListController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The entity storage controller class.
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   List of all entity types.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageControllerInterface $storage, array $entity_types) {
    parent::__construct($entity_type, $storage);

    $this->entityTypes = $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_type,
      $entity_manager->getStorageController($entity_type->id()),
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
      if (!isset($this->entityTypes[$entity_type])) {
        continue;
      }

      // Filter entities
      if ($this->entityTypes[$entity_type]->isFieldable() && !$this->isValidEntity($entity_type)) {
        continue;
      }

      $table = array(
        '#prefix' => '<h2>' . $this->entityTypes[$entity_type]->getLabel() . '</h2>',
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

      $short_type = str_replace('_mode', '', $this->entityTypeId);
      $table['#rows']['_add_new'][] = array(
        'data' => array(
          '#type' => 'link',
          '#href' => "admin/structure/display-modes/$short_type/add/$entity_type",
          '#title' => t('Add new %label @entity-type', array('%label' => $this->entityTypes[$entity_type]->getLabel(), '@entity-type' => $this->entityType->getLowercaseLabel())),
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
    return $this->entityTypes[$entity_type]->hasViewBuilderClass();
  }

}
