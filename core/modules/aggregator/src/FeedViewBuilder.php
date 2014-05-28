<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedViewBuilder.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for aggregator feed items.
 */
class FeedViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    $defaults = parent::getBuildDefaults($entity, $view_mode, $langcode);
    $defaults['#theme'] = 'aggregator_feed_source';
    return $defaults;
  }

}
