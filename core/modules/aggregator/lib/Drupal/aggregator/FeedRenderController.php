<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedRenderController.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRenderController;

/**
 * Render controller for aggregator feed items.
 */
class FeedRenderController extends EntityRenderController {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::getBuildDefaults().
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    $defaults = parent::getBuildDefaults($entity, $view_mode, $langcode);
    $defaults['#theme'] = 'aggregator_feed_source';
    return $defaults;
  }

}
