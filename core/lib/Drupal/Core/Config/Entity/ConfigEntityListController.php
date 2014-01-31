<?php

/**
 * @file
 * Definition of Drupal\Core\Config\Entity\ConfigEntityListController.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListController;

/**
 * Defines the default list controller for ConfigEntity objects.
 */
class ConfigEntityListController extends EntityListController {

  /**
   * Overrides Drupal\Core\Entity\EntityListController::load().
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
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    if ($this->entityType->hasKey('status')) {
      if (!$entity->status() && $entity->hasLinkTemplate('enable')) {
        $operations['enable'] = array(
          'title' => t('Enable'),
          'weight' => -10,
        ) + $entity->urlInfo('enable');
      }
      elseif ($entity->hasLinkTemplate('disable')) {
        $operations['disable'] = array(
          'title' => t('Disable'),
          'weight' => 40,
        ) + $entity->urlInfo('disable');
      }
    }

    return $operations;
  }

}
