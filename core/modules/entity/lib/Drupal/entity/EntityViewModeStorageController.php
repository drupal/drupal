<?php

/**
 * @file
 * Contains \Drupal\entity\EntityViewModeStorageController.
 */

namespace Drupal\entity;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the storage controller class for entity view modes.
 */
class EntityViewModeStorageController extends ConfigStorageController {

  /**
   * {@inheritdoc}
   */
  protected function preSave(EntityInterface $view_mode) {
    entity_info_cache_clear();
  }

  /**
   * {@inheritdoc}
   */
  protected function preDelete($view_modes) {
    entity_info_cache_clear();
  }

}
