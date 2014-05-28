<?php

/**
 * @file
 * Contains \Drupal\aggregator\ItemViewBuilder.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for aggregator feed items.
 */
class ItemViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
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
