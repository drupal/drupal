<?php

namespace Drupal\field_ui\Controller;

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
    $entity_types = [];
    foreach ($this->entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->get('field_ui_base_route') && $entity_type->hasViewBuilderClass()) {
        $entity_types[$entity_type_id] = [
          'title' => $entity_type->getLabel(),
          'url' => Url::fromRoute('entity.entity_view_mode.add_form', ['entity_type_id' => $entity_type_id]),
          'localized_options' => [],
        ];
      }
    }
    return [
      '#theme' => 'admin_block_content',
      '#content' => $entity_types,
    ];
  }

  /**
   * Provides a list of eligible entity types for adding form modes.
   *
   * @return array
   *   A list of entity types to add a form mode for.
   */
  public function formModeTypeSelection() {
    $entity_types = [];
    foreach ($this->entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->get('field_ui_base_route') && $entity_type->hasFormClasses()) {
        $entity_types[$entity_type_id] = [
          'title' => $entity_type->getLabel(),
          'url' => Url::fromRoute('entity.entity_form_mode.add_form', ['entity_type_id' => $entity_type_id]),
          'localized_options' => [],
        ];
      }
    }
    return [
      '#theme' => 'admin_block_content',
      '#content' => $entity_types,
    ];
  }

}
