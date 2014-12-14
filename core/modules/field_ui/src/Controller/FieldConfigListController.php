<?php

/**
 * @file
 * Contains \Drupal\field_ui\Controller\FieldConfigListController.
 */

namespace Drupal\field_ui\Controller;

use Drupal\Core\Entity\Controller\EntityListController;
use Symfony\Component\HttpFoundation\Request;

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
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing($entity_type_id = NULL, $bundle = NULL, Request $request = NULL) {
    if (!$bundle) {
      $entity_info = $this->entityManager()->getDefinition($entity_type_id);
      $bundle = $request->attributes->get('_raw_variables')->get($entity_info->getBundleEntityType());
    }
    return $this->entityManager()->getListBuilder('field_config')->render($entity_type_id, $bundle, $request);
  }

}
