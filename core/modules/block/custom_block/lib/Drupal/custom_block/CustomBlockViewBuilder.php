<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockViewBuilder.
 */

namespace Drupal\custom_block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\entity\Entity\EntityDisplay;

/**
 * Render controller for custom blocks.
 */
class CustomBlockViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityDisplay $display, $view_mode, $langcode = NULL) {
    parent::alterBuild($build, $entity, $display, $view_mode, $langcode);
    // Add contextual links for this custom block.
    if (!empty($entity->id->value) && $view_mode == 'full') {
      $build['#contextual_links']['custom_block'] = array(
        'route_parameters' => array('custom_block' => $entity->id()),
      );
    }
  }

}
