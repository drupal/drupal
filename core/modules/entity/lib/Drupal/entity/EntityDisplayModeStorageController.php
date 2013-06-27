<?php

/**
 * @file
 * Contains \Drupal\entity\EntityDisplayModeStorageController.
 */

namespace Drupal\entity;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the storage controller class for entity form and view modes.
 */
class EntityDisplayModeStorageController extends ConfigStorageController {

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
