<?php

/**
 * @file
 * Contains \Drupal\field_ui\EntityDisplayModeListBuilder.
 */

namespace Drupal\field_ui;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of view mode entities.
 *
 * @see \Drupal\Core\Entity\Entity\EntityViewMode
 */
class EntityDisplayModeListBuilder extends ConfigEntityListBuilder {

  /**
   * All entity types.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $entityTypes;

  /**
   * Constructs a new EntityDisplayModeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   List of all entity types.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, array $entity_types) {
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
      $entity_manager->getStorage($entity_type->id()),
      $entity_manager->getDefinitions()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Name');
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

      // Filter entities.
      if (!$this->isValidEntity($entity_type)) {
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

      $short_type = str_replace(array('entity_', '_mode'), '', $this->entityTypeId);
      $table['#rows']['_add_new'][] = array(
        'data' => array(
          '#type' => 'link',
          '#url' => Url::fromRoute($short_type == 'view' ? 'entity.entity_view_mode.add_form' : 'entity.entity_form_mode.add_form', ['entity_type_id' => $entity_type]),
          '#title' => t('Add new %label @entity-type', array('%label' => $this->entityTypes[$entity_type]->getLabel(), '@entity-type' => $this->entityType->getLowercaseLabel())),
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
    return $this->entityTypes[$entity_type]->get('field_ui_base_route') && $this->entityTypes[$entity_type]->hasViewBuilderClass();
  }

}
