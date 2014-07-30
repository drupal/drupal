<?php

/**
 * @file
 * Contains \Drupal\entity\Controller\EntityDisplayModeController.
 */

namespace Drupal\entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Provides methods for entity display mode routes.
 */
class EntityDisplayModeController extends ControllerBase {

  /**
   * Provides a list of eligible entity types for adding view modes.
   *
   * @return array
   *   A list of entity types to add a view mode for.
   */
  public function viewModeTypeSelection() {
    $entity_types = array();
    foreach ($this->entityManager()->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->isFieldable() && $entity_type->hasViewBuilderClass()) {
        $entity_types[$entity_type_id] = array(
          'title' => $entity_type->getLabel(),
          'url' => new Url('entity.view_mode_add_type', array('entity_type_id' => $entity_type_id)),
          'localized_options' => array(),
        );
      }
    }
    return array(
      '#theme' => 'admin_block_content',
      '#content' => $entity_types,
    );
  }

  /**
   * Provides a list of eligible entity types for adding form modes.
   *
   * @return array
   *   A list of entity types to add a form mode for.
   */
  public function formModeTypeSelection() {
    $entity_types = array();
    foreach ($this->entityManager()->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->isFieldable() && $entity_type->hasFormClasses()) {
        $entity_types[$entity_type_id] = array(
          'title' => $entity_type->getLabel(),
          'url' => new Url('entity.form_mode_add_type', array('entity_type_id' => $entity_type_id)),
          'localized_options' => array(),
        );
      }
    }
    return array(
      '#theme' => 'admin_block_content',
      '#content' => $entity_types,
    );
  }

}
