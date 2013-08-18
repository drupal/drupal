<?php

/**
 * @file
 * Contains \Drupal\entity_test\EntityTestRenderController.
 */

namespace Drupal\entity_test;

use Drupal\Core\Entity\EntityRenderController;

/**
 * Defines an entity render controller for a test entity.
 *
 * @see \Drupal\entity_test\Entity\EntityTestRender
 */
class EntityTestRenderController extends EntityRenderController {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::buildContent().
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildContent($entities, $displays, $view_mode, $langcode);

    foreach ($entities as $entity) {
      $entity->content['label'] = array(
        '#markup' => check_plain($entity->label()),
      );
      $entity->content['separator'] = array(
        '#markup' => ' | ',
      );
      $entity->content['view_mode'] = array(
        '#markup' => check_plain($view_mode),
      );
    }
  }

}
