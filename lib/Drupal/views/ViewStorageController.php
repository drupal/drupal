<?php

/**
 * @file
 * Definition of Drupal\views\ViewStorageController.
 */

namespace Drupal\views;

use Drupal\config\ConfigStorageController;

class ViewStorageController extends ConfigStorageController {

  /**
   * Overrides Drupal\config\ConfigStorageController::attachLoad();
   */
  protected function attachLoad(&$queried_entities, $revision_id = FALSE) {
    foreach ($queried_entities as $id => $entity) {
      foreach ($entity->display as $key => $options) {
        // Create a ViewsDisplay object using the display options.
        $entity->display[$key] = new ViewsDisplay($options);
      }
    }
  }

}