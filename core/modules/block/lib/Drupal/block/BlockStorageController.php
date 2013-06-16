<?php

/**
 * @file
 * Contains \Drupal\block\BlockStorageController.
 */

namespace Drupal\block;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the storage controller class for Block entities.
 */
class BlockStorageController extends ConfigStorageController {

  /**
   * {@inheritdoc}
   */
  public function load(array $ids = NULL) {
    $entities = parent::load($ids);
    // Only blocks with a valid plugin should be loaded.
    return array_filter($entities, function ($entity) {
      return $entity->getPlugin();
    });
  }

}
