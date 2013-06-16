<?php

/**
 * @file
 * Contains \Drupal\image\ImageStyleStorageController.
 */

namespace Drupal\image;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Config\Config;

/**
 * Defines a controller class for image styles.
 */
class ImageStyleStorageController extends ConfigStorageController {

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::attachLoad().
   */
  protected function attachLoad(&$queried_entities, $revision_id = FALSE) {
    foreach ($queried_entities as $style) {
      if (!empty($style->effects)) {
        foreach ($style->effects as $ieid => $effect) {
          $definition = image_effect_definition_load($effect['name']);
          $effect = array_merge($definition, $effect);
          $style->effects[$ieid] = $effect;
        }
        // Sort effects by weight.
        uasort($style->effects, 'drupal_sort_weight');
      }
    }
    parent::attachLoad($queried_entities, $revision_id);
  }

}
