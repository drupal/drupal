<?php

/**
 * @file
 * Contains \Drupal\system\ActionStorageController.
 */

namespace Drupal\system;

use Drupal\Core\Action\ConfigurableActionInterface;
use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the storage controller class for Action entities.
 */
class ActionStorageController extends ConfigStorageController {

  /**
   * {@inheritdoc}
   */
  protected function preSave(EntityInterface $entity) {
    parent::preSave($entity);

    $plugin = $entity->getPlugin();
    // If this plugin has any configuration, ensure that it is set.
    if ($plugin instanceof ConfigurableActionInterface) {
      $entity->set('configuration', $plugin->getConfiguration());
    }
  }

}
