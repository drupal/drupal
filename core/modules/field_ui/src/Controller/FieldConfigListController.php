<?php

namespace Drupal\field_ui\Controller;

use Drupal\Core\Entity\Controller\EntityListController;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines a controller to list field instances.
 */
class FieldConfigListController extends EntityListController {

  /**
   * Shows the 'Manage fields' page.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function listing($entity_type_id = NULL, $bundle = NULL, RouteMatchInterface $route_match = NULL) {
    return $this->entityManager()->getListBuilder('field_config')->render($entity_type_id, $bundle);
  }

}
