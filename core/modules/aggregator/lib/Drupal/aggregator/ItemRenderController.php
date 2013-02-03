<?php

/**
 * @file
 * Contains \Drupal\aggregator\ItemRenderController.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRenderController;

/**
 * Render controller for aggregator feed items.
 */
class ItemRenderController extends EntityRenderController {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::getBuildDefaults().
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    $defaults = parent::getBuildDefaults($entity, $view_mode, $langcode);

    // Use a different template for the summary view mode.
    if ($view_mode == 'summary') {
      $defaults['#theme'] = 'aggregator_summary_item';
    }
    return $defaults;
  }

}
