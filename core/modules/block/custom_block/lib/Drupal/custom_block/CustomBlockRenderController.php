<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockRenderController.
 */

namespace Drupal\custom_block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRenderController;
use Drupal\entity\Entity\EntityDisplay;

/**
 * Render controller for custom blocks.
 */
class CustomBlockRenderController extends EntityRenderController {

  /**
   * Overrides \Drupal\Core\Entity\EntityRenderController::alterBuild().
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityDisplay $display, $view_mode, $langcode = NULL) {
    parent::alterBuild($build, $entity, $display, $view_mode, $langcode);
    // Add contextual links for this custom block.
    if (!empty($entity->id->value) && $view_mode == 'full') {
      $build['#contextual_links']['custom_block'] = array('block', array($entity->id()));
    }
  }

}
