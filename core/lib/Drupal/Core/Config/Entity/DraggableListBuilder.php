<?php

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\DraggableListBuilderTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;

/**
 * Defines a class to build a draggable listing of configuration entities.
 */
abstract class DraggableListBuilder extends ConfigEntityListBuilder implements FormInterface {

  use DraggableListBuilderTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);

    // Do not inject the form builder for backwards-compatibility.
    $this->formBuilder = \Drupal::formBuilder();

    // Check if the entity type supports weighting.
    if ($this->entityType->hasKey('weight')) {
      $this->weightKey = $this->entityType->getKey('weight');
    }
    $this->limit = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getWeight(EntityInterface $entity): int|float {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    return $entity->get($this->weightKey) ?: 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function setWeight(EntityInterface $entity, int|float $weight): EntityInterface {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $entity->set($this->weightKey, $weight);
    return $entity;
  }

}
