<?php

/**
 * @file
 * Contains \Drupal\aggregator\ItemViewBuilder.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for aggregator feed items.
 */
class ItemViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildComponents($build, $entities, $displays, $view_mode, $langcode);

    foreach ($entities as $id => $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      if ($display->getComponent('description')) {
        $build[$id]['description'] = array(
          '#markup' => aggregator_filter_xss($entity->getDescription()),
          '#prefix' => '<div class="item-description">',
          '#suffix' => '</div>',
        );
      }
    }
  }

}
