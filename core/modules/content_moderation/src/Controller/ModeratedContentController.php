<?php

namespace Drupal\content_moderation\Controller;

use Drupal\content_moderation\ModeratedNodeListBuilder;
use Drupal\Core\Controller\ControllerBase;

/**
 * Defines a controller to list moderated nodes.
 */
class ModeratedContentController extends ControllerBase {

  /**
   * Provides the listing page for moderated nodes.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function nodeListing() {
    $entity_type = $this->entityTypeManager()->getDefinition('node');

    return $this->entityTypeManager()->createHandlerInstance(ModeratedNodeListBuilder::class, $entity_type)->render();
  }

}
