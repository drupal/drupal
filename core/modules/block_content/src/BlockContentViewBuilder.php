<?php

/**
 * @file
 * Contains \Drupal\block_content\BlockContentViewBuilder.
 */

namespace Drupal\block_content;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for custom blocks.
 */
class BlockContentViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    $build = parent::getBuildDefaults($entity, $view_mode, $langcode);
    // The custom block will be rendered in the wrapped block template already
    // and thus has no entity template itself.
    unset($build['#theme']);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode, $langcode = NULL) {
    parent::alterBuild($build, $entity, $display, $view_mode, $langcode);
    // Add contextual links for this custom block.
    if (!$entity->isNew() && $view_mode == 'full') {
      $build['#contextual_links']['block_content'] = array(
        'route_parameters' => array('block_content' => $entity->id()),
        'metadata' => array('changed' => $entity->getChangedTime()),
      );
    }
  }

}
