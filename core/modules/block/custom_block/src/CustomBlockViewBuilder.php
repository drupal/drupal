<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockViewBuilder.
 */

namespace Drupal\custom_block;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for custom blocks.
 */
class CustomBlockViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode, $langcode = NULL) {
    parent::alterBuild($build, $entity, $display, $view_mode, $langcode);
    // Add contextual links for this custom block.
    if (!$entity->isNew() && $view_mode == 'full') {
      $build['#contextual_links']['custom_block'] = array(
        'route_parameters' => array('custom_block' => $entity->id()),
        'metadata' => array('changed' => $entity->getChangedTime()),
      );
    }
  }

}
