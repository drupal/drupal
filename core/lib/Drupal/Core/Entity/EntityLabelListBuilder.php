<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a listing of entities with ID, Label, Bundle columns.
 *
 * @ingroup entity_api
 */
class EntityLabelListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    if ($this->entityType->hasKey('label')) {
      $header['label'] = $this->t('Label');
    }
    if ($this->entityType->hasKey('bundle')) {
      $header['bundle'] = $this->t('Bundle');
    }

    return  array_merge($header, parent::buildHeader());
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    if ($this->entityType->hasKey('label')) {
      $row['label'] = new Link($entity->label() ?? '[' . $entity->id() . ']', $entity->toUrl());
    }
    if ($this->entityType->hasKey('bundle')) {
      $row['bundle'] = \Drupal::entityTypeManager()->getDefinition($entity->getEntityTypeId())->getLabel();
    }

    return array_merge($row, parent::buildRow($entity));
  }

}
