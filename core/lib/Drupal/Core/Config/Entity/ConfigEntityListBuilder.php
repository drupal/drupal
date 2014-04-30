<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\ConfigEntityListBuilder.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines the default class to build a listing of configuration entities.
 *
 * @ingroup entity_api
 */
class ConfigEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = parent::load();

    // Sort the entities using the entity class's sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, array($this->entityType->getClass(), 'sort'));
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    if ($this->entityType->hasKey('status')) {
      if (!$entity->status() && $entity->hasLinkTemplate('enable')) {
        $operations['enable'] = array(
          'title' => t('Enable'),
          'weight' => -10,
        ) + $entity->urlInfo('enable')->toArray();
      }
      elseif ($entity->hasLinkTemplate('disable')) {
        $operations['disable'] = array(
          'title' => t('Disable'),
          'weight' => 40,
        ) + $entity->urlInfo('disable')->toArray();
      }
    }

    return $operations;
  }

}
